<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Group extends Model
{
    use HasSlug;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'owner_id',
        'active',
        'max_members',
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
            'max_members' => 'integer',
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
            ->generateSlugsFrom(function ($model) {
                $ownerName = $model->owner?->name
                    ?? auth()->user()?->name
                    ?? '';

                return $model->name . ' ' . $ownerName;
            })
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
    }

    public function inviteLinks()
    {
        return $this->hasMany(GroupInviteLink::class);
    }

    public function isOwner(int $userId): bool
    {
        return $this->owner_id === $userId;
    }

    public function isAdmin(int $userId): bool
    {
        if ($this->isOwner($userId)) {
            return true;
        }

        return $this->users()
            ->where('users.id', $userId)
            ->wherePivot('role', 'admin')
            ->exists();
    }

    public function isMember(int $userId): bool
    {
        if ($this->isOwner($userId)) {
            return true;
        }

        return $this->users()->where('users.id', $userId)->exists();
    }

    public function hasCapacity(): bool
    {
        return $this->users()->count() < $this->max_members;
    }

    public function currentUserRole(int $userId): ?string
    {
        if ($this->isOwner($userId)) {
            return 'owner';
        }

        return $this->users()
            ->where('users.id', $userId)
            ->value('group_user.role');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where(function (Builder $subQuery) use ($userId) {
            $subQuery->where('owner_id', $userId)
                ->orWhereHas('users', fn(Builder $members) => $members->where('users.id', $userId));
        });
    }

    public function scopeOwnerOnly(Builder $query, int $userId, bool $ownerOnly): Builder
    {
        return $query->when($ownerOnly, fn(Builder $q) => $q->where('owner_id', $userId));
    }

    public function scopeRoleForUser(Builder $query, int $userId, ?string $role): Builder
    {
        if (empty($role)) {
            return $query;
        }

        if ($role === 'owner') {
            return $query->where('owner_id', $userId);
        }

        return $query->whereHas('users', function (Builder $members) use ($userId, $role) {
            $members->where('users.id', $userId)
                ->where('group_user.role', $role);
        });
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
                $subQuery->where('name', 'like', "%{$term}%")
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
            ->ownerOnly($userId, $request->boolean('owner_only'))
            ->roleForUser($userId, $request->input('role'))
            ->search($request->input('search'))
            ->sortByCreated($request->input('sort'));
    }
}
