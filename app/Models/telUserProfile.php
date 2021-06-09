<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class telUserProfile extends Model
{
    use HasFactory;

    function user() {
        return $this->belongsTo(telUser::class, 'user_id');
    }
}
