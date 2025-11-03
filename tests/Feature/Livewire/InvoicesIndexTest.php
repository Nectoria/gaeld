<?php

use App\Livewire\Invoices\Index;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create(['email_verified_at' => now()]);
    $this->company = Company::factory()->create();
    $this->user->current_company_id = $this->company->id;
    $this->user->companies()->attach($this->company->id, ['role' => 'owner']);
    $this->user->save();

    $this->contact = Contact::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->actingAs($this->user);
});

test('invoices index page renders successfully', function () {
    Livewire::test(Index::class)
        ->assertStatus(200);
});

test('displays invoices for current company', function () {
    $invoice1 = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'contact_id' => $this->contact->id,
        'created_by' => $this->user->id,
        'invoice_number' => 'INV-001',
    ]);

    $invoice2 = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'contact_id' => $this->contact->id,
        'created_by' => $this->user->id,
        'invoice_number' => 'INV-002',
    ]);

    // Invoice from another company (should not be shown)
    $otherCompany = Company::factory()->create();
    $otherContact = Contact::factory()->create(['company_id' => $otherCompany->id]);
    Invoice::factory()->create([
        'company_id' => $otherCompany->id,
        'contact_id' => $otherContact->id,
        'created_by' => $this->user->id,
        'invoice_number' => 'INV-999',
    ]);

    Livewire::test(Index::class)
        ->assertSee('INV-001')
        ->assertSee('INV-002')
        ->assertDontSee('INV-999');
});

test('can search invoices by number', function () {
    Invoice::factory()->create([
        'company_id' => $this->company->id,
        'contact_id' => $this->contact->id,
        'created_by' => $this->user->id,
        'invoice_number' => 'INV-001',
    ]);

    Invoice::factory()->create([
        'company_id' => $this->company->id,
        'contact_id' => $this->contact->id,
        'created_by' => $this->user->id,
        'invoice_number' => 'INV-002',
    ]);

    Livewire::test(Index::class)
        ->set('search', 'INV-001')
        ->assertSee('INV-001')
        ->assertDontSee('INV-002');
});

test('can search invoices by customer name', function () {
    $customer1 = Contact::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Acme Corp',
    ]);

    $customer2 = Contact::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'XYZ Inc',
    ]);

    Invoice::factory()->create([
        'company_id' => $this->company->id,
        'contact_id' => $customer1->id,
        'created_by' => $this->user->id,
        'invoice_number' => 'INV-001',
    ]);

    Invoice::factory()->create([
        'company_id' => $this->company->id,
        'contact_id' => $customer2->id,
        'created_by' => $this->user->id,
        'invoice_number' => 'INV-002',
    ]);

    Livewire::test(Index::class)
        ->set('search', 'Acme')
        ->assertSee('INV-001')
        ->assertDontSee('INV-002');
});

test('can filter invoices by status', function () {
    Invoice::factory()->create([
        'company_id' => $this->company->id,
        'contact_id' => $this->contact->id,
        'created_by' => $this->user->id,
        'invoice_number' => 'INV-001',
        'status' => 'draft',
    ]);

    Invoice::factory()->create([
        'company_id' => $this->company->id,
        'contact_id' => $this->contact->id,
        'created_by' => $this->user->id,
        'invoice_number' => 'INV-002',
        'status' => 'paid',
    ]);

    Livewire::test(Index::class)
        ->set('status', 'draft')
        ->assertSee('INV-001')
        ->assertDontSee('INV-002');
});

test('can filter invoices by date range', function () {
    Invoice::factory()->create([
        'company_id' => $this->company->id,
        'contact_id' => $this->contact->id,
        'created_by' => $this->user->id,
        'invoice_number' => 'INV-001',
        'invoice_date' => '2024-01-15',
    ]);

    Invoice::factory()->create([
        'company_id' => $this->company->id,
        'contact_id' => $this->contact->id,
        'created_by' => $this->user->id,
        'invoice_number' => 'INV-002',
        'invoice_date' => '2024-03-15',
    ]);

    Livewire::test(Index::class)
        ->set('dateFrom', '2024-01-01')
        ->set('dateTo', '2024-02-01')
        ->assertSee('INV-001')
        ->assertDontSee('INV-002');
});

test('can sort invoices by date', function () {
    Invoice::factory()->create([
        'company_id' => $this->company->id,
        'contact_id' => $this->contact->id,
        'created_by' => $this->user->id,
        'invoice_number' => 'INV-001',
        'invoice_date' => '2024-01-01',
    ]);

    Invoice::factory()->create([
        'company_id' => $this->company->id,
        'contact_id' => $this->contact->id,
        'created_by' => $this->user->id,
        'invoice_number' => 'INV-002',
        'invoice_date' => '2024-02-01',
    ]);

    $component = Livewire::test(Index::class);

    // Default sort should be by invoice_date descending
    $invoices = $component->get('invoices');
    expect($invoices->first()->invoice_number)->toBe('INV-002');

    // Toggle sort direction
    $component->call('updateSorting', 'invoice_date');
    $invoices = $component->get('invoices');
    expect($invoices->first()->invoice_number)->toBe('INV-001');
});

test('can clear all filters', function () {
    Livewire::test(Index::class)
        ->set('search', 'test')
        ->set('status', 'draft')
        ->set('dateFrom', '2024-01-01')
        ->set('dateTo', '2024-12-31')
        ->call('clearFilters')
        ->assertSet('search', '')
        ->assertSet('status', '')
        ->assertSet('dateFrom', '')
        ->assertSet('dateTo', '')
        ->assertSet('sortBy', 'invoice_date')
        ->assertSet('sortDirection', 'desc');
});

test('hasActiveFilters returns true when filters are set', function () {
    $component = Livewire::test(Index::class)
        ->set('search', 'test');

    expect($component->get('hasActiveFilters'))->toBeTrue();
});

test('hasActiveFilters returns false when no filters are set', function () {
    $component = Livewire::test(Index::class);

    expect($component->get('hasActiveFilters'))->toBeFalse();
});

test('displays empty state when no invoices', function () {
    Livewire::test(Index::class)
        ->assertSee('No invoices found');
});
