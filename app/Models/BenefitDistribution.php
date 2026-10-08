<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BenefitDistribution extends Model
{
    use HasFactory;

    protected $fillable = ['organization_id', 'type', 'description', 'distributed_at', 'created_by'];

    protected function casts(): array
    {
        return ['distributed_at' => 'date'];
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function families()
    {
        return $this->belongsToMany(Family::class, 'benefit_distribution_family')->withTimestamps();
    }
}
