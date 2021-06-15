<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class teAd extends Model
{
    use HasFactory;


    function creator() {
        return $this->belongsTo(telUser::class, 'creator_user_id', 'user_id');
    }
}
