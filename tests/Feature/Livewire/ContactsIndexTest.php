<?php

use App\Livewire\Contacts\Index;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create(['email_verified_at' => now()]);
    $this->company = Company::factory()->create();
    $this->user->current_company_id = $this->company->id;
    $this->user->companies()->attach($this->company->id, ['role' => 'owner']);
    $this->user->save();

    $this->actingAs($this->user);
});

test('contacts index page renders successfully', function () {
    Livewire::test(Index::class)
        ->assertStatus(200);
});

test('displays contacts for current company', function () {
    $contact1 = Contact::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'John Doe',
    ]);

    $contact2 = Contact::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Jane Smith',
    ]);

    // Contact from another company (should not be shown)
    $otherCompany = Company::factory()->create();
    Contact::factory()->create([
        'company_id' => $otherCompany->id,
        'name' => 'Other Company Contact',
    ]);

    Livewire::test(Index::class)
        ->assertSee('John Doe')
        ->assertSee('Jane Smith')
        ->assertDontSee('Other Company Contact');
});

test('can search contacts by name', function () {
    Contact::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'John Doe',
    ]);

    Contact::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Jane Smith',
    ]);

    Livewire::test(Index::class)
        ->set('search', 'John')
        ->assertSee('John Doe')
        ->assertDontSee('Jane Smith');
});

test('can search contacts by email', function () {
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

    Livewire::test(Index::class)
        ->set('search', 'john@example')
        ->assertSee('John Doe')
        ->assertDontSee('Jane Smith');
});

test('can filter contacts by type', function () {
    Contact::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Customer Contact',
        'type' => 'customer',
    ]);

    Contact::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Vendor Contact',
        'type' => 'vendor',
    ]);

    Livewire::test(Index::class)
        ->set('type', 'customer')
        ->assertSee('Customer Contact')
        ->assertDontSee('Vendor Contact');
});

test('can sort contacts by name', function () {
    Contact::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Zebra Company',
    ]);

    Contact::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Alpha Company',
    ]);

    $component = Livewire::test(Index::class);

    // Default sort should be by name ascending
    $contacts = $component->get('contacts');
    expect($contacts->first()->name)->toBe('Alpha Company');

    // Toggle sort direction
    $component->call('updateSorting', 'name');
    $contacts = $component->get('contacts');
    expect($contacts->first()->name)->toBe('Zebra Company');
});

test('pagination resets when search changes', function () {
    // Create more than 15 contacts to trigger pagination
    Contact::factory()->count(20)->create([
        'company_id' => $this->company->id,
    ]);

    $component = Livewire::test(Index::class);

    // Go to page 2
    $component->set('page', 2);

    // Change search - should reset to page 1
    $component->set('search', 'test');

    expect($component->get('page'))->toBe(1);
});

test('can clear all filters', function () {
    Livewire::test(Index::class)
        ->set('search', 'John')
        ->set('type', 'customer')
        ->call('clearFilters')
        ->assertSet('search', '')
        ->assertSet('type', '')
        ->assertSet('sortBy', 'name')
        ->assertSet('sortDirection', 'asc');
});

test('displays empty state when no contacts', function () {
    Livewire::test(Index::class)
        ->assertSee('No contacts found');
});

test('displays filtered empty state when search returns no results', function () {
    Contact::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'John Doe',
    ]);

    Livewire::test(Index::class)
        ->set('search', 'NonExistent')
        ->assertSee('No contacts found')
        ->assertSee('Try adjusting your filters');
});
