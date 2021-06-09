<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class telProcess extends Model
{
    use HasFactory;

    function _user() {
        return $this->belongsToMany(telUser::class, 'tel_process_tel_users','tel_process_id')->withPivot(['sub_process', 'process_state', 'tel_process_id', 'tel_user_id']);
    }
    function getUserAttribute() {
        return $this->_user()->first();
    }
    function Tracks() {
        return $this->hasMany(telProcess::class);
    }
}
