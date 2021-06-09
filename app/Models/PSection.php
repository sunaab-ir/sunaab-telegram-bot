<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PSection extends Model
{
    use HasFactory;

    public function province() {
        return $this->belongsTo(Province::class);
    }
    public function county() {
        return $this->belongsTo(County::class);
    }
    public function cities() {
        return $this->hasMany(City::class);
    }
    public function rurals() {
        return $this->hasMany(Rural::class);
    }
    public function villages() {
        return $this->hasMany(Village::class);
    }
}
