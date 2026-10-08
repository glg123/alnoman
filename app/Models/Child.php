<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Child extends Model
{
    use HasFactory;

    protected $fillable = [
        'family_id',
        'full_name',
        'national_id',
        'age',
    ];

    public function family()
    {
        return $this->belongsTo(Family::class);
    }

    public function specialCases()
    {
        return $this->hasMany(SpecialCase::class);
    }
}
