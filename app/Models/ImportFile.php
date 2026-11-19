<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportFile extends Model
{
    protected $fillable = [
        'import_id',
        'mime_type',
        'file_size',
        'file_content',
        'parsed_payload',
    ];

    protected $casts = [
        'parsed_payload' => 'array',
    ];

    /**
     * Get the parent import metadata record.
     */
    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }
}
