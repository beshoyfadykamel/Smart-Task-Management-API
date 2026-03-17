<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('status')->withTimestamps();
    }
}
