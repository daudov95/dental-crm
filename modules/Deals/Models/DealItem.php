<?php

declare(strict_types=1);

namespace Modules\Deals\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Catalog\Models\Service;

class DealItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'deal_id',
        'service_id',
        'quantity',
        'unit_price',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
        ];
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class, 'deal_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function lineTotal(): float
    {
        return round((float) $this->unit_price * $this->quantity, 2);
    }
}
