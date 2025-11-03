<?php

use App\Models\Company;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->company = Company::factory()->create();
    $this->user->companies()->attach($this->company->id, ['role' => 'owner']);

    $this->contact = Contact::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->actingAs($this->user);
    tenant()->switch($this->company->id);
});

test('resolveRouteBinding returns invoice from current company', function () {
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'contact_id' => $this->contact->id,
        'created_by' => $this->user->id,
    ]);

    $resolved = (new Invoice)->resolveRouteBinding($invoice->id);

    expect($resolved)->toBeInstanceOf(Invoice::class)
        ->and($resolved->id)->toBe($invoice->id)
        ->and($resolved->company_id)->toBe($this->company->id);
});

test('resolveRouteBinding throws exception for invoice from different company', function () {
    $otherCompany = Company::factory()->create();
    $otherContact = Contact::factory()->create([
        'company_id' => $otherCompany->id,
    ]);
    $otherInvoice = Invoice::factory()->create([
        'company_id' => $otherCompany->id,
        'contact_id' => $otherContact->id,
        'created_by' => $this->user->id,
    ]);

    // Should throw ModelNotFoundException
    (new Invoice)->resolveRouteBinding($otherInvoice->id);
})->throws(ModelNotFoundException::class);

test('resolveRouteBinding throws exception for non-existent invoice', function () {
    (new Invoice)->resolveRouteBinding(99999);
})->throws(ModelNotFoundException::class);

test('resolveRouteBinding works with invoice_number field', function () {
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'contact_id' => $this->contact->id,
        'created_by' => $this->user->id,
        'invoice_number' => 'INV-2025-0001',
    ]);

    $resolved = (new Invoice)->resolveRouteBinding('INV-2025-0001', 'invoice_number');

    expect($resolved)->toBeInstanceOf(Invoice::class)
        ->and($resolved->id)->toBe($invoice->id)
        ->and($resolved->invoice_number)->toBe('INV-2025-0001');
});
