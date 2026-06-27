<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalDocument extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'content',
        'version',
        'effective_date',
    ];

    protected $casts = [
        'effective_date' => 'date',
    ];

    public function toApiArray(): array
    {
        return [
            'id'            => (string) $this->id,
            'slug'          => $this->slug,
            'title'         => $this->title,
            'content'       => $this->content,
            'version'       => $this->version,
            'effectiveDate' => $this->effective_date->toDateString(),
            'lastUpdated'   => $this->updated_at->toIso8601String(),
        ];
    }
}
