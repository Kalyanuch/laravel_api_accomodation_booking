<?php

namespace App\Models;

use App\Enums\ImportStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Import extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'external_import_id',
        'sent_at',
        'status',
        'total_offers',
        'processed_offers',
        'payload',
        'error',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'immutable_datetime',
            'status' => ImportStatus::class,
            'total_offers' => 'integer',
            'processed_offers' => 'integer',
            'payload' => 'array',
            'completed_at' => 'immutable_datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }
}
