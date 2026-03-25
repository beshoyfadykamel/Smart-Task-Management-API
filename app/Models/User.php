<?php

namespace App\Models;

use App\Notifications\Api\Auth\ResetPasswordQueued;
use App\Notifications\Api\Auth\VerifyEmailQueued;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
    ];

    /**
     * Default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => true,
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'boolean',
        ];
    }

    /**
     * Get the groups the user belongs to.
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class)->withTimestamps();
    }
    /**
     * Get tasks assigned to the user through groups.
     */
    public function groupTasks()
    {
        return $this->belongsToMany(Task::class, 'task_user')
            ->withPivot('status')
            ->withTimestamps();
    }

    /**
     * Get tasks created by the user.
     */
    public function ownTasks()
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    /**
     * Backward-compatible alias.
     */
    public function owenTasks()
    {
        return $this->ownTasks();
    }

    /**
     * Load roles and permissions with selected columns.
     *
     * @return $this
     */
    public function loadRolesAndPermissions()
    {
        return $this->load(['roles:id,name', 'roles.permissions:id,name', 'permissions:id,name']);
    }

    /**
     * Send queued email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmailQueued());
    }

    /**
     * Send queued password reset notification.
     *
     * @param string $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordQueued($token));
    }

    /**
     * Filter users by active/inactive status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStatus($query, $status)
    {
        return $query->when($status !== null, fn($q) => $q->where('status', $status));
    }

    /**
     * Filter users created on or after a given datetime.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCreatedFrom($query, $date)
    {
        return $query->when(!empty($date), function ($q) use ($date) {
            return $q->where('created_at', '>=', $date);
        });
    }

    /**
     * Filter users by email verification state.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $verified
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEmailVerified($query, $verified)
    {
        return $query->when($verified !== null, function ($q) use ($verified) {
            return $verified
                ? $q->whereNotNull('email_verified_at')
                : $q->whereNull('email_verified_at');
        });
    }

    /**
     * Search users by id, name, or email.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $term
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, $term)
    {
        return $query->when(!empty($term), function ($q) use ($term) {
            return $q->where(function ($subQuery) use ($term) {
                $subQuery->where('id', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        });
    }

    /**
     * Sort users by creation date.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $sort
     * @return \Illuminate\Database\Eloquent\Builder
     */
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
            ->emailVerified($request->input('email_verified'))
            ->search($request->input('search'))
            ->sortByCreated($request->input('sort'));
    }
}
