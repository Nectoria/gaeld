<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Money\Currency;
use Money\Money;

class Invoice extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'contact_id',
        'created_by',
        'invoice_number',
        'reference_number',
        'invoice_date',
        'due_date',
        'paid_at',
        'subtotal_amount',
        'tax_amount',
        'total_amount',
        'paid_amount',
        'currency',
        'tax_rate',
        'tax_inclusive',
        'status',
        'payment_method',
        'payment_reference',
        'qr_reference',
        'qr_additional_info',
        'qr_iban',
        'notes',
        'terms',
        'footer',
        'pdf_path',
        'qr_code_path',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'date',
        'tax_inclusive' => 'boolean',
        'subtotal_amount' => 'integer',
        'tax_amount' => 'integer',
        'total_amount' => 'integer',
        'paid_amount' => 'integer',
        'tax_rate' => 'decimal:2',
    ];

    /**
     * Get the company that owns this invoice
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the contact (customer) for this invoice
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the user who created this invoice
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all items for this invoice
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    /**
     * Get subtotal as Money object
     */
    public function getSubtotalAttribute(): Money
    {
        return new Money($this->subtotal_amount, new Currency($this->currency));
    }

    /**
     * Get tax as Money object
     */
    public function getTaxAttribute(): Money
    {
        return new Money($this->tax_amount, new Currency($this->currency));
    }

    /**
     * Get total as Money object
     */
    public function getTotalAttribute(): Money
    {
        return new Money($this->total_amount, new Currency($this->currency));
    }

    /**
     * Get paid amount as Money object
     */
    public function getPaidAttribute(): Money
    {
        return new Money($this->paid_amount, new Currency($this->currency));
    }

    /**
     * Get balance due as Money object
     */
    public function getBalanceDueAttribute(): Money
    {
        return new Money($this->total_amount - $this->paid_amount, new Currency($this->currency));
    }

    /**
     * Check if invoice is fully paid
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid' || $this->paid_amount >= $this->total_amount;
    }

    /**
     * Check if invoice is overdue
     */
    public function isOverdue(): bool
    {
        return $this->status !== 'paid'
            && $this->status !== 'cancelled'
            && $this->due_date
            && $this->due_date->isPast();
    }

    /**
     * Check if invoice is draft
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Calculate totals from items
     */
    public function calculateTotals(): void
    {
        $this->subtotal_amount = $this->items->sum('subtotal');
        $this->tax_amount = $this->items->sum('tax_amount');
        $this->total_amount = $this->items->sum('total');
    }

    /**
     * Generate next invoice number for a company
     */
    public static function generateInvoiceNumber(int $companyId, string $prefix = 'INV-'): string
    {
        $year = now()->year;
        $lastInvoice = static::where('company_id', $companyId)
            ->where('invoice_number', 'like', $prefix.$year.'%')
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, strlen($prefix.$year.'-'));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix.$year.'-'.str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate Swiss QR reference number (27 digits with check digit)
     */
    public function generateQrReference(): string
    {
        // Swiss QR reference: 26 digits + 1 check digit
        // Format: XXXXXXXXXXXXXXXXXXXXXXXXXXC
        // Where C is a modulo 10 recursive check digit

        $base = str_pad($this->company_id, 6, '0', STR_PAD_LEFT)
            .str_pad($this->id, 19, '0', STR_PAD_LEFT);

        // Calculate check digit using modulo 10 recursive algorithm
        $checkDigit = $this->calculateMod10Recursive($base);

        return $base.$checkDigit;
    }

    /**
     * Calculate modulo 10 recursive check digit
     */
    private function calculateMod10Recursive(string $number): int
    {
        $table = [0, 9, 4, 6, 8, 2, 7, 1, 3, 5];
        $carry = 0;

        for ($i = 0; $i < strlen($number); $i++) {
            $carry = $table[($carry + intval($number[$i])) % 10];
        }

        return (10 - $carry) % 10;
    }

    /**
     * Scope to only invoices for a specific company
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope to only overdue invoices
     */
    public function scopeOverdue($query)
    {
        return $query->whereNotIn('status', ['paid', 'cancelled'])
            ->whereDate('due_date', '<', now());
    }

    /**
     * Scope to only paid invoices
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope to only draft invoices
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }
}
