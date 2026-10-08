<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Family extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'full_name',
        'national_id',
        'national_id_photo_path',
        'wife_name',
        'wife_national_id',
        'members_count',
        'status',
        'rejection_reason',
        'approved_by',
        'approved_at',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function children()
    {
        return $this->hasMany(Child::class);
    }

    public function specialCases()
    {
        return $this->hasMany(SpecialCase::class);
    }

    public function editRequests()
    {
        return $this->hasMany(FamilyEditRequest::class);
    }

    public function benefitDistributions()
    {
        return $this->belongsToMany(BenefitDistribution::class, 'benefit_distribution_family')->withTimestamps();
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function getNationalIdPhotoUrlAttribute(): ?string
    {
        return $this->national_id_photo_path
            ? route('files.id-photo', $this)
            : null;
    }
}
