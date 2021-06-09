<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class telUser extends Model
{
    use HasFactory;
    protected $primaryKey = "user_id";

    function currentProcess() {
        return $this->belongsToMany(telProcess::class, 'tel_process_tel_users', 'tel_user_id', 'tel_process_id', 'user_id')->withPivot(['sub_process', 'process_state', 'tel_process_id', 'tel_user_id']);
    }
}
