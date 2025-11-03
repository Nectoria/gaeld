<?php

use App\Models\Company;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated and verified users in a company can visit the dashboard', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $company = Company::factory()->create();
    $user->current_company_id = $company->id;

    // Ensure the user is associated with the company
    $user->companies()->attach($company->id, ['role' => 'owner']);
    $user->save();

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertStatus(200);
});
