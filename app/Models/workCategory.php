<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class workCategory extends Model
{
    use HasFactory;

    function users() {
        return $this->hasManyThrough(telUser::class, telUserProfile::class, 'work_category', 'user_id');
    }

    function profiles() {
        return $this->hasMany(telUserProfile::class, 'work_category');
    }
}
