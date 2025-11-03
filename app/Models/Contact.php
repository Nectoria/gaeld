<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'type',
        'name',
        'contact_person',
        'email',
        'phone',
        'mobile',
        'website',
        'vat_number',
        'tax_id',
        'street',
        'street_number',
        'postal_code',
        'city',
        'country',
        'iban',
        'bank_name',
        'notes',
        'reference_number',
        'payment_term_days',
        'currency',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'payment_term_days' => 'integer',
    ];

    /**
     * Get the company that owns this contact
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get all invoices for this contact
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the contact's full address as a string
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            trim($this->street.' '.$this->street_number),
            $this->postal_code.' '.$this->city,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Check if this is a customer
     */
    public function isCustomer(): bool
    {
        return in_array($this->type, ['customer', 'both']);
    }

    /**
     * Check if this is a vendor
     */
    public function isVendor(): bool
    {
        return in_array($this->type, ['vendor', 'both']);
    }

    /**
     * Get the contact's display name with person if available
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->contact_person) {
            return $this->name.' ('.$this->contact_person.')';
        }

        return $this->name;
    }

    /**
     * Scope to only customers
     */
    public function scopeCustomers($query)
    {
        return $query->whereIn('type', ['customer', 'both']);
    }

    /**
     * Scope to only vendors
     */
    public function scopeVendors($query)
    {
        return $query->whereIn('type', ['vendor', 'both']);
    }

    /**
     * Scope to only active contacts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Resolve route model binding with explicit tenant validation
     *
     * This provides defense-in-depth by validating tenant ownership
     * at the route binding level, before reaching controllers/policies.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('company_id', tenant()->currentId())
            ->firstOrFail();
    }
}
