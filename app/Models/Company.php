<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    /** @use HasFactory<\Database\Factories\CompanyFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'legal_name',
        'vat_number',
        'registration_number',
        'street',
        'street_number',
        'postal_code',
        'city',
        'country',
        'email',
        'phone',
        'website',
        'iban',
        'bank_name',
        'logo_path',
        'primary_color',
        'currency',
        'locale',
        'timezone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the users that belong to this company
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(CompanyUser::class)
            ->withPivot('role', 'is_active', 'joined_at')
            ->withTimestamps();
    }

    /**
     * Get all contacts for this company
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Get all invoices for this company
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the company's full address as a string
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
     * Get the company's display name (prefers legal_name if available)
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->legal_name ?: $this->name;
    }
}
