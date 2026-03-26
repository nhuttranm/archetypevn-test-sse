<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'description',
        'quantity',
        'unit_price',
        'total_price',
        'unit',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    /* ── Relationships ────────────────────────── */

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /* ── Mutators ──────────────────────────────── */

    protected static function booted(): void
    {
        static::saving(function (PoItem $item) {
            $item->total_price = $item->quantity * $item->unit_price;
        });
    }
}
