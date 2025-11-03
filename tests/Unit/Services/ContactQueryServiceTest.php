<?php

use App\Models\Company;
use App\Models\Contact;
use App\Services\ContactQueryService;

beforeEach(function () {
    $this->service = new ContactQueryService();
    $this->company = Company::factory()->create();
});

test('buildQuery returns a query builder instance', function () {
    $query = $this->service->buildQuery();

    expect($query)->toBeInstanceOf(\Illuminate\Database\Eloquent\Builder::class);
});

test('buildQuery filters by search term', function () {
    Contact::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'John Doe',
    ]);

    Contact::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Jane Smith',
    ]);

    $results = $this->service->buildQuery(search: 'John')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('John Doe');
});

test('buildQuery searches by email', function () {
    Contact::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    Contact::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
    ]);

    $results = $this->service->buildQuery(search: 'john@example')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('John Doe');
});

test('buildQuery filters by customer type', function () {
    Contact::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Customer',
        'type' => 'customer',
    ]);

    Contact::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Vendor',
        'type' => 'vendor',
    ]);

    Contact::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Both',
        'type' => 'both',
    ]);

    $results = $this->service->buildQuery(type: 'customer')->get();

    expect($results)->toHaveCount(2) // customer and both
        ->and($results->pluck('name')->toArray())->toContain('Customer', 'Both');
});

test('buildQuery filters by vendor type', function () {
    Contact::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Customer',
        'type' => 'customer',
    ]);

    Contact::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Vendor',
        'type' => 'vendor',
    ]);

    Contact::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Both',
        'type' => 'both',
    ]);

    $results = $this->service->buildQuery(type: 'vendor')->get();

    expect($results)->toHaveCount(2) // vendor and both
        ->and($results->pluck('name')->toArray())->toContain('Vendor', 'Both');
});

test('buildQuery sorts by specified column', function () {
    Contact::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Zebra',
    ]);

    Contact::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Alpha',
    ]);

    $results = $this->service->buildQuery(sortBy: 'name', sortDirection: 'asc')->get();

    expect($results->first()->name)->toBe('Alpha')
        ->and($results->last()->name)->toBe('Zebra');
});

test('getPaginated returns paginated results', function () {
    Contact::factory()->count(20)->create([
        'company_id' => $this->company->id,
    ]);

    $results = $this->service->getPaginated(perPage: 10);

    expect($results)->toBeInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class)
        ->and($results->perPage())->toBe(10)
        ->and($results->total())->toBe(20);
});
