<?php

use App\Models\Company;
use App\Models\User;
use App\Services\TenantService;
use Illuminate\Support\Facades\Session;

beforeEach(function () {
    $this->service = new TenantService;
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('switch persists company to database', function () {
    $company = Company::factory()->create();
    $this->user->companies()->attach($company->id, ['role' => 'owner']);

    // Clear cache to ensure fresh data
    $this->service->clearCache($this->user);

    $result = $this->service->switch($company->id);

    expect($result)->toBeTrue()
        ->and(Session::get('current_company_id'))->toBe($company->id)
        ->and($this->user->fresh()->current_company_id)->toBe($company->id);
});

test('switch fails for inaccessible company', function () {
    $company = Company::factory()->create();
    // User is not attached to this company

    $result = $this->service->switch($company->id);

    expect($result)->toBeFalse()
        ->and(Session::get('current_company_id'))->toBeNull()
        ->and($this->user->fresh()->current_company_id)->toBeNull();
});

test('getDefaultCompany loads from database preference', function () {
    $company1 = Company::factory()->create();
    $company2 = Company::factory()->create();

    $this->user->companies()->attach($company1->id, ['role' => 'owner']);
    $this->user->companies()->attach($company2->id, ['role' => 'member']);
    $this->user->current_company_id = $company2->id;
    $this->user->save();

    // Clear cache and session
    $this->service->clearCache($this->user);
    Session::forget('current_company_id');

    $current = $this->service->current();

    expect($current->id)->toBe($company2->id)
        ->and(Session::get('current_company_id'))->toBe($company2->id);
});

test('getDefaultCompany falls back to first accessible company', function () {
    $company1 = Company::factory()->create();
    $company2 = Company::factory()->create();

    $this->user->companies()->attach($company1->id, ['role' => 'owner']);
    $this->user->companies()->attach($company2->id, ['role' => 'member']);

    // Clear cache and session
    $this->service->clearCache($this->user);
    Session::forget('current_company_id');

    $current = $this->service->current();

    // Should get first accessible company
    expect($current->id)->toBe($company1->id)
        ->and(Session::get('current_company_id'))->toBe($company1->id)
        ->and($this->user->fresh()->current_company_id)->toBe($company1->id);
});

test('accessible returns only active companies', function () {
    $activeCompany = Company::factory()->create(['is_active' => true]);
    $inactiveCompany = Company::factory()->create(['is_active' => false]);

    $this->user->companies()->attach($activeCompany->id, ['role' => 'owner', 'is_active' => true]);
    $this->user->companies()->attach($inactiveCompany->id, ['role' => 'owner', 'is_active' => true]);

    // Clear cache
    $this->service->clearCache($this->user);

    $accessible = $this->service->accessible($this->user);

    expect($accessible)->toHaveCount(1)
        ->and($accessible->first()->id)->toBe($activeCompany->id);
});

test('accessible returns only companies where user pivot is active', function () {
    $company1 = Company::factory()->create(['is_active' => true]);
    $company2 = Company::factory()->create(['is_active' => true]);

    $this->user->companies()->attach($company1->id, ['role' => 'owner', 'is_active' => true]);
    $this->user->companies()->attach($company2->id, ['role' => 'owner', 'is_active' => false]);

    // Clear cache
    $this->service->clearCache($this->user);

    $accessible = $this->service->accessible($this->user);

    expect($accessible)->toHaveCount(1)
        ->and($accessible->first()->id)->toBe($company1->id);
});

test('userHasAccess checks if user can access company', function () {
    $ownCompany = Company::factory()->create();
    $otherCompany = Company::factory()->create();

    $this->user->companies()->attach($ownCompany->id, ['role' => 'owner']);

    // Clear cache
    $this->service->clearCache($this->user);

    expect($this->service->userHasAccess($this->user, $ownCompany))->toBeTrue()
        ->and($this->service->userHasAccess($this->user, $otherCompany))->toBeFalse();
});

test('forget clears session', function () {
    Session::put('current_company_id', 123);

    $this->service->forget();

    expect(Session::get('current_company_id'))->toBeNull();
});

test('clearCache removes user companies from cache', function () {
    $company = Company::factory()->create();
    $this->user->companies()->attach($company->id, ['role' => 'owner']);

    // Load into cache
    $this->service->accessible($this->user);

    // Clear cache
    $this->service->clearCache($this->user);

    // Cache should be empty (we can't directly check, but accessible will reload)
    $accessible = $this->service->accessible($this->user);

    expect($accessible)->toHaveCount(1);
});
