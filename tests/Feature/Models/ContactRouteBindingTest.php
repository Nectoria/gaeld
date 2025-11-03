<?php

use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->company = Company::factory()->create();
    $this->user->companies()->attach($this->company->id, ['role' => 'owner']);

    $this->actingAs($this->user);
    tenant()->switch($this->company->id);
});

test('resolveRouteBinding returns contact from current company', function () {
    $contact = Contact::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $resolved = (new Contact)->resolveRouteBinding($contact->id);

    expect($resolved)->toBeInstanceOf(Contact::class)
        ->and($resolved->id)->toBe($contact->id)
        ->and($resolved->company_id)->toBe($this->company->id);
});

test('resolveRouteBinding throws exception for contact from different company', function () {
    $otherCompany = Company::factory()->create();
    $otherContact = Contact::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    // Should throw ModelNotFoundException
    (new Contact)->resolveRouteBinding($otherContact->id);
})->throws(ModelNotFoundException::class);

test('resolveRouteBinding throws exception for non-existent contact', function () {
    (new Contact)->resolveRouteBinding(99999);
})->throws(ModelNotFoundException::class);

test('resolveRouteBinding works with custom field', function () {
    $contact = Contact::factory()->create([
        'company_id' => $this->company->id,
        'reference_number' => 'TEST-123',
    ]);

    $resolved = (new Contact)->resolveRouteBinding('TEST-123', 'reference_number');

    expect($resolved)->toBeInstanceOf(Contact::class)
        ->and($resolved->id)->toBe($contact->id)
        ->and($resolved->reference_number)->toBe('TEST-123');
});
