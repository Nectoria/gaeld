<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Money\Currency;
use Money\Money;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'name',
        'description',
        'sku',
        'quantity',
        'unit',
        'unit_price',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total',
        'discount_percent',
        'discount_amount',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'tax_rate' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'unit_price' => 'integer',
        'subtotal' => 'integer',
        'tax_amount' => 'integer',
        'total' => 'integer',
        'discount_amount' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Get the invoice that owns this item
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get unit price as Money object
     */
    public function getUnitPriceMoneyAttribute(): Money
    {
        return new Money($this->unit_price, new Currency($this->invoice->currency));
    }

    /**
     * Get subtotal as Money object
     */
    public function getSubtotalMoneyAttribute(): Money
    {
        return new Money($this->subtotal, new Currency($this->invoice->currency));
    }

    /**
     * Get tax as Money object
     */
    public function getTaxMoneyAttribute(): Money
    {
        return new Money($this->tax_amount, new Currency($this->invoice->currency));
    }

    /**
     * Get total as Money object
     */
    public function getTotalMoneyAttribute(): Money
    {
        return new Money($this->total, new Currency($this->invoice->currency));
    }

    /**
     * Calculate and set all monetary values
     * This should be called whenever quantity, unit_price, tax_rate, or discount changes
     */
    public function calculateAmounts(): void
    {
        // Calculate subtotal: quantity * unit_price
        $subtotal = (int) ($this->quantity * $this->unit_price);

        // Apply discount if any
        if ($this->discount_percent > 0) {
            $this->discount_amount = (int) ($subtotal * ($this->discount_percent / 100));
            $subtotal -= $this->discount_amount;
        } else {
            $this->discount_amount = 0;
        }

        $this->subtotal = $subtotal;

        // Calculate tax
        if ($this->tax_rate > 0) {
            $this->tax_amount = (int) ($this->subtotal * ($this->tax_rate / 100));
        } else {
            $this->tax_amount = 0;
        }

        // Calculate total
        $this->total = $this->subtotal + $this->tax_amount;
    }

    /**
     * Boot method to automatically calculate amounts
     */
    protected static function booted(): void
    {
        static::saving(function (InvoiceItem $item) {
            $item->calculateAmounts();
        });

        static::saved(function (InvoiceItem $item) {
            // Recalculate invoice totals when item is saved
            if ($item->invoice) {
                $item->invoice->calculateTotals();
                $item->invoice->saveQuietly(); // Save without triggering events
            }
        });

        static::deleted(function (InvoiceItem $item) {
            // Recalculate invoice totals when item is deleted
            if ($item->invoice) {
                $item->invoice->calculateTotals();
                $item->invoice->saveQuietly();
            }
        });
    }
}
