<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Task extends Model
{
    use HasSlug;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'group_id',
        'created_by',
        'image_path',
        'active',
        'due_date',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'due_date' => 'date',
        ];
    }

    /**
     * Use slug for implicit route model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'task_user')->withPivot('status')->withTimestamps();
    }

    /**
     * Backward-compatible alias.
     */
    public function groupUsers()
    {
        return $this->users();
    }

    /**
     * Backward-compatible alias.
     */
    public function taskUsers()
    {
        return $this->users();
    }

    public function scopeStatus($query, $status)
    {
        return $query->when($status !== null, fn($q) => $q->where('active', $status));
    }

    public function scopeCreatedFrom($query, $date)
    {
        return $query->when(!empty($date), function ($q) use ($date) {
            return $q->where('created_at', '>=', $date);
        });
    }

    public function scopeDueFrom($query, $date)
    {
        return $query->when(!empty($date), function ($q) use ($date) {
            return $q->whereDate('due_date', '>=', $date);
        });
    }

    public function scopeDueTo($query, $date)
    {
        return $query->when(!empty($date), function ($q) use ($date) {
            return $q->whereDate('due_date', '<=', $date);
        });
    }

    public function scopeGroupSlug($query, $groupSlug)
    {
        return $query->when(!empty($groupSlug), function ($q) use ($groupSlug) {
            return $q->whereHas('group', fn(Builder $groupQuery) => $groupQuery->where('slug', $groupSlug));
        });
    }

    public function scopeCreatedByUser($query, int $userId, $mine)
    {
        return $query->when($mine, fn($q) => $q->where('created_by', $userId));
    }

    public function scopeAssignedToUser($query, int $userId, $assignedToMe)
    {
        return $query->when($assignedToMe, function ($q) use ($userId) {
            return $q->whereHas('users', fn(Builder $usersQuery) => $usersQuery->where('users.id', $userId));
        });
    }

    public function scopeSearch($query, $term)
    {
        return $query->when(!empty($term), function ($q) use ($term) {
            return $q->where(function ($subQuery) use ($term) {
                $subQuery->where('title', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%");
            });
        });
    }

    public function scopeSortByCreated($query, $sort)
    {
        $direction = in_array($sort, ['asc', 'desc']) ? $sort : 'desc';
        return $query->orderBy('created_at', $direction);
    }

    public function scopeFilter($query, $request, int $userId)
    {
        return $query
            ->status($request->input('status'))
            ->createdFrom($request->input('created_from'))
            ->dueFrom($request->input('due_from'))
            ->dueTo($request->input('due_to'))
            ->groupSlug($request->input('group_slug'))
            ->createdByUser($userId, $request->boolean('mine'))
            ->assignedToUser($userId, $request->boolean('assigned_to_me'))
            ->search($request->input('search'))
            ->sortByCreated($request->input('sort'));
    }

    public function scopeVisibleTo(Builder $query, int $userId): Builder
    {
        return $query->where(function (Builder $subQuery) use ($userId) {
            $subQuery->where('created_by', $userId)
                ->orWhereHas('group', function (Builder $groupQuery) use ($userId) {
                    $groupQuery->where('owner_id', $userId)
                        ->orWhereHas('users', fn(Builder $members) => $members->where('users.id', $userId));
                });
        });
    }
}
