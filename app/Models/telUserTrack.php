<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class telUserTrack extends Model
{
    use HasFactory;

    function user() {
        return $this->belongsTo(telUser::class, 'tel_user_id', 'user_id');
    }

    function process() {
        return $this->belongsTo(telProcess::class);
    }
}
