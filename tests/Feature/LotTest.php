<?php

use App\Enums\LotStatus;
use App\Models\Lot;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected from lots index', function () {
    $this->get(route('lots.index'))->assertRedirect(route('login'));
});

test('authenticated users can view lots index', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('lots.index'))->assertSuccessful();
});

test('can create a lot via modal', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::lots.index')
        ->set('code', 'AB-001')
        ->set('block', 'A')
        ->set('price', '150000')
        ->call('createLot')
        ->assertHasNoErrors();

    expect(Lot::where('code', 'AB-001')->exists())->toBeTrue();
});

test('lot code must be unique', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Lot::factory()->create(['code' => 'AB-001']);

    Livewire::test('pages::lots.index')
        ->set('code', 'AB-001')
        ->set('price', '100000')
        ->call('createLot')
        ->assertHasErrors(['code' => 'unique']);
});

test('can update a lot', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $lot = Lot::factory()->create(['price' => 100000]);

    Livewire::test('pages::lots.show', ['lot' => $lot])
        ->set('price', '200000')
        ->call('updateLot')
        ->assertHasNoErrors();

    expect((float) $lot->fresh()->price)->toBe(200000.0);
});

test('lot status defaults to available', function () {
    $lot = Lot::factory()->create();

    expect($lot->status)->toBe(LotStatus::Available);
});
