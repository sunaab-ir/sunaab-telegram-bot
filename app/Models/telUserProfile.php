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

    function workCategory() {
        return $this->belongsTo(workCategory::class, 'work_category');
    }

    function county() {
        return $this->belongsTo(County::class, 'county_id');
    }
    function city() {
        return $this->belongsTo(City::class, 'city_id');
    }
    function village() {
        return $this->belongsTo(Village::class, 'village_id');
    }
}
