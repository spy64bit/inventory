<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'supplier_id',
    'status',
    'created_by',
    'approved_by',
    'approved_at',
    'submitted_at',
    'received_at',
    'notes',
])]
class PurchaseOrder extends Model
{
    use SoftDeletes;

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function isFullyReceived(): bool
    {
        return $this->items->every(
            fn ($item) => $item->quantity_received >= $item->quantity_ordered
        );
    }

    public function isPartiallyReceived(): bool
    {
        return $this->items->some(
            fn ($item) => $item->quantity_received > 0
        ) && ! $this->isFullyReceived();
    }
}
