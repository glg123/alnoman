<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'logo_path', 'phone', 'address', 'description'];

    public function distributions()
    {
        return $this->hasMany(BenefitDistribution::class);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->logo_path)
            : null;
    }
}
