<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Task extends Model
{
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

    public function scopeFilter($query, $request)
    {
        return $query
            ->status($request->input('status'))
            ->createdFrom($request->input('created_from'))
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
