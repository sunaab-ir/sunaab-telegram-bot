<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class telUser extends Model
{
    use HasFactory;
    protected $primaryKey = "user_id";

    function Process() {
        return $this->belongsToMany(telProcess::class, 'tel_process_tel_users', 'tel_user_id', 'tel_process_id', 'user_id')->withPivot(['sub_process', 'process_state', 'tel_process_id', 'tel_user_id']);
    }
    function getCurrentProcessAttribute() {
        return $this->Process()->first();
    }
    function Profile() {
        return $this->hasOne(telUserProfile::class, 'user_id');
    }
    function Tracks() {
        return $this->hasMany(telUserTrack::class, 'tel_user_id', 'user_id');
    }
}
