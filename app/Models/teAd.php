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

    function sents() {
        return $this->hasMany(sentAd::class, 'ad_id');
    }
    function city() {
        return $this->belongsTo(City::class, 'city_id');
    }
    function village() {
        return $this->belongsTo(Village::class, 'village_id');
    }
}
