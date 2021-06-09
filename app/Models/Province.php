<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    use HasFactory;

    public function counties() {
        return $this->hasMany(County::class);
    }
    public function sections() {
        return $this->hasMany(PSection::class);
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
