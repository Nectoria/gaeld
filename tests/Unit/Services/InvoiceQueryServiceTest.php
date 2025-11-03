<?php

use App\Models\Company;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\User;
use App\Services\InvoiceQueryService;

beforeEach(function () {
    $this->service = new InvoiceQueryService();
    $this->user = User::factory()->create();
    $this->company = Company::factory()->create();
    $this->contact = Contact::factory()->create(['company_id' => $this->company->id]);
});

test('buildQuery returns a query builder instance', function () {
    $query = $this->service->buildQuery();

    expect($query)->toBeInstanceOf(\Illuminate\Database\Eloquent\Builder::class);
});

test('buildQuery filters by invoice number', function () {
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

    $results = $this->service->buildQuery(search: 'INV-001')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->invoice_number)->toBe('INV-001');
});

test('buildQuery filters by reference number', function () {
    Invoice::factory()->create([
        'company_id' => $this->company->id,
        'contact_id' => $this->contact->id,
        'created_by' => $this->user->id,
        'invoice_number' => 'INV-001',
        'reference_number' => 'REF-123',
    ]);

    Invoice::factory()->create([
        'company_id' => $this->company->id,
        'contact_id' => $this->contact->id,
        'created_by' => $this->user->id,
        'invoice_number' => 'INV-002',
        'reference_number' => 'REF-456',
    ]);

    $results = $this->service->buildQuery(search: 'REF-123')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->reference_number)->toBe('REF-123');
});

test('buildQuery filters by customer name', function () {
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

    $results = $this->service->buildQuery(search: 'Acme')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->invoice_number)->toBe('INV-001');
});

test('buildQuery filters by status', function () {
    Invoice::factory()->create([
        'company_id' => $this->company->id,
        'contact_id' => $this->contact->id,
        'created_by' => $this->user->id,
        'status' => 'draft',
    ]);

    Invoice::factory()->create([
        'company_id' => $this->company->id,
        'contact_id' => $this->contact->id,
        'created_by' => $this->user->id,
        'status' => 'paid',
    ]);

    $results = $this->service->buildQuery(status: 'draft')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->status)->toBe('draft');
});

test('buildQuery filters by date range', function () {
    Invoice::factory()->create([
        'company_id' => $this->company->id,
        'contact_id' => $this->contact->id,
        'created_by' => $this->user->id,
        'invoice_date' => '2024-01-15',
    ]);

    Invoice::factory()->create([
        'company_id' => $this->company->id,
        'contact_id' => $this->contact->id,
        'created_by' => $this->user->id,
        'invoice_date' => '2024-03-15',
    ]);

    $results = $this->service->buildQuery(
        dateFrom: '2024-01-01',
        dateTo: '2024-02-01'
    )->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->invoice_date->format('Y-m-d'))->toBe('2024-01-15');
});

test('buildQuery sorts by invoice date descending by default', function () {
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

    $results = $this->service->buildQuery(
        sortBy: 'invoice_date',
        sortDirection: 'desc'
    )->get();

    expect($results->first()->invoice_number)->toBe('INV-002');
});

test('buildQuery eager loads relationships', function () {
    $invoice = Invoice::factory()->create([
        'company_id' => $this->company->id,
        'contact_id' => $this->contact->id,
        'created_by' => $this->user->id,
    ]);

    $results = $this->service->buildQuery()->get();

    expect($results->first()->relationLoaded('contact'))->toBeTrue()
        ->and($results->first()->relationLoaded('creator'))->toBeTrue();
});

test('getPaginated returns paginated results', function () {
    Invoice::factory()->count(20)->create([
        'company_id' => $this->company->id,
        'contact_id' => $this->contact->id,
        'created_by' => $this->user->id,
    ]);

    $results = $this->service->getPaginated(perPage: 10);

    expect($results)->toBeInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class)
        ->and($results->perPage())->toBe(10)
        ->and($results->total())->toBe(20);
});

test('getPaginated applies all filters', function () {
    Invoice::factory()->create([
        'company_id' => $this->company->id,
        'contact_id' => $this->contact->id,
        'created_by' => $this->user->id,
        'invoice_number' => 'INV-001',
        'status' => 'draft',
        'invoice_date' => '2024-01-15',
    ]);

    Invoice::factory()->create([
        'company_id' => $this->company->id,
        'contact_id' => $this->contact->id,
        'created_by' => $this->user->id,
        'invoice_number' => 'INV-002',
        'status' => 'paid',
        'invoice_date' => '2024-03-15',
    ]);

    $results = $this->service->getPaginated(
        search: 'INV-001',
        status: 'draft',
        dateFrom: '2024-01-01',
        dateTo: '2024-02-01'
    );

    expect($results->total())->toBe(1)
        ->and($results->first()->invoice_number)->toBe('INV-001');
});
