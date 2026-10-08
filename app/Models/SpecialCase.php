<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpecialCase extends Model
{
    use HasFactory;

    protected $fillable = [
        'family_id',
        'child_id',
        'type',
        'description',
        'document_path',
    ];

    public function family()
    {
        return $this->belongsTo(Family::class);
    }

    public function child()
    {
        return $this->belongsTo(Child::class);
    }

    public function getDocumentUrlAttribute(): ?string
    {
        return $this->document_path
            ? route('files.case-document', $this)
            : null;
    }
}
