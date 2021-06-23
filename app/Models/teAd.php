<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class teAd extends Model
{
    use HasFactory, SoftDeletes;


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
    function workerCategory() {
        return $this->belongsTo(workCategory::class, 'work_category');
    }
}
