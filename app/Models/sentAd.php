<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class sentAd extends Model
{
    use HasFactory;

    function user() {
        return $this->belongsTo(telUser::class, 'user_id', 'user_id');
    }

    function ad() {
        return $this->belongsTo(teAd::class, 'ad_id');
    }
}
