<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class telBotMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id',
        'message_id',
        'message_type',
        'time',
        'meta_data'
    ];
}
