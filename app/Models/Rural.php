<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rural extends Model
{
    use HasFactory;

    public function province() {
        return $this->belongsTo(Province::class);
    }
    public function county() {
        return $this->belongsTo(County::class);
    }
    public function section() {
        return $this->belongsTo(PSection::class);
    }
    public function villages() {
        return $this->hasMany(Village::class);
    }
}
