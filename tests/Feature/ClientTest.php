<?php

use App\Models\Client;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected from clients index', function () {
    $this->get(route('clients.index'))->assertRedirect(route('login'));
});

test('authenticated users can view clients index', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('clients.index'))->assertSuccessful();
});

test('can create a client via modal', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::clients.index')
        ->set('name', 'John Doe')
        ->set('email', 'john@example.com')
        ->set('phone', '+55 11 99999-9999')
        ->call('createClient')
        ->assertHasNoErrors();

    expect(Client::where('name', 'John Doe')->exists())->toBeTrue();
});

test('client name is required', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::clients.index')
        ->set('name', '')
        ->call('createClient')
        ->assertHasErrors(['name' => 'required']);
});

test('can update a client', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $client = Client::factory()->create(['name' => 'Old Name']);

    Livewire::test('pages::clients.show', ['client' => $client])
        ->set('name', 'New Name')
        ->call('updateClient')
        ->assertHasNoErrors();

    expect($client->fresh()->name)->toBe('New Name');
});

test('can delete a client', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $client = Client::factory()->create();

    Livewire::test('pages::clients.index')
        ->call('deleteClient', $client->id);

    expect(Client::find($client->id))->toBeNull();
});
