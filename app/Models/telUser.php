<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class telUser extends Model
{
    use HasFactory;

    protected $primaryKey = "user_id";
//    protected $with = ['Profile'];

    function Process ()
    {
        return $this->belongsToMany(telProcess::class, 'tel_process_tel_users', 'tel_user_id', 'tel_process_id', 'user_id')->withPivot(['sub_process', 'process_state', 'tel_process_id', 'tel_user_id', 'tmp_data', 'process_type']);
    }

    function getCurrentProcessAttribute ()
    {
        $cProcess = clone($this->Process());
        return $cProcess->where('process_type', 'normal')->first();
    }

    function getCommandProcessAttribute ()
    {
        $coProcess = clone($this->Process());
        return $coProcess->where('process_type', 'command')->first();
    }

    function Profile ()
    {
        return $this->hasOne(telUserProfile::class, 'user_id');
    }

    function Tracks ()
    {
        return $this->hasMany(telUserTrack::class, 'tel_user_id', 'user_id');
    }

    function setting ()
    {
        return $this->hasOne(telUserSetting::class, 'tel_user_id', 'user_id');
    }

    function ads() {
        return $this->hasMany(teAd::class, 'creator_user_id', 'user_id');
    }

    function receiveAds() {
        return $this->hasMany(sentAd::class, 'user_id', 'user_id');
    }
}
