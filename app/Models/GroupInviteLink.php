<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class GroupInviteLink extends Model
{
    protected $fillable = [
        'group_id',
        'created_by',
        'token',
        'role',
        'max_uses',
        'current_uses',
        'expires_at',
        'active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'max_uses' => 'integer',
            'current_uses' => 'integer',
            'active' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'token';
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isExpired(): bool
    {
        return !is_null($this->expires_at) && Carbon::now()->greaterThan($this->expires_at);
    }

    public function hasRemainingUses(): bool
    {
        if (is_null($this->max_uses)) {
            return true;
        }

        return $this->current_uses < $this->max_uses;
    }

    public function isUsable(): bool
    {
        return $this->active && !$this->isExpired() && $this->hasRemainingUses();
    }
}
