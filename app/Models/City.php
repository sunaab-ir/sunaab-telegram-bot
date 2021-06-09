<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    function province ()
    {
        return $this->belongsTo(Province::class);
    }

    function county ()
    {
        return $this->belongsTo(County::class);
    }

    function section ()
    {
        return $this->belongsTo(PSection::class);
    }

    function rurals ()
    {
        return $this->hasMany(PSection::class);
    }

    function villages ()
    {
        return $this->hasMany(Village::class);
    }
}
