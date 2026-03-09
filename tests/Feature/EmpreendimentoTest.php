<?php

use App\Enums\EmpreendimentoStatus;
use App\Models\Empreendimento;
use App\Models\Lot;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected from empreendimentos index', function () {
    $this->get(route('empreendimentos.index'))->assertRedirect(route('login'));
});

test('authenticated users can view empreendimentos index', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('empreendimentos.index'))->assertSuccessful();
});

test('can create an empreendimento via modal', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::empreendimentos.index')
        ->set('name', 'Loteamento Primavera')
        ->set('city', 'São Paulo')
        ->set('state', 'SP')
        ->set('totalArea', '50000')
        ->set('status', 'active')
        ->call('createEmpreendimento')
        ->assertHasNoErrors();

    expect(Empreendimento::where('name', 'Loteamento Primavera')->exists())->toBeTrue();
});

test('empreendimento name must be unique', function () {
    $this->actingAs(User::factory()->create());
    Empreendimento::factory()->create(['name' => 'Loteamento Teste']);

    Livewire::test('pages::empreendimentos.index')
        ->set('name', 'Loteamento Teste')
        ->call('createEmpreendimento')
        ->assertHasErrors(['name' => 'unique']);
});

test('can update an empreendimento', function () {
    $this->actingAs(User::factory()->create());
    $emp = Empreendimento::factory()->create(['city' => 'Campinas']);

    Livewire::test('pages::empreendimentos.show', ['empreendimento' => $emp])
        ->set('city', 'Ribeirão Preto')
        ->call('updateEmpreendimento')
        ->assertHasNoErrors()
        ->assertDispatched('empreendimento-updated');

    expect($emp->fresh()->city)->toBe('Ribeirão Preto');
});

test('can delete an empreendimento', function () {
    $this->actingAs(User::factory()->create());
    $emp = Empreendimento::factory()->create();

    Livewire::test('pages::empreendimentos.index')
        ->call('deleteEmpreendimento', $emp->id);

    expect(Empreendimento::find($emp->id))->toBeNull();
});

test('empreendimento status defaults to active', function () {
    $emp = Empreendimento::factory()->create();

    expect($emp->status)->toBe(EmpreendimentoStatus::Active);
});

test('can link a lot to an empreendimento', function () {
    $this->actingAs(User::factory()->create());
    $emp = Empreendimento::factory()->create();
    $lot = Lot::factory()->create(['empreendimento_id' => null]);

    Livewire::test('pages::empreendimentos.show', ['empreendimento' => $emp])
        ->set('selectedLotId', $lot->id)
        ->call('linkLot')
        ->assertHasNoErrors();

    expect($lot->fresh()->empreendimento_id)->toBe($emp->id);
});

test('can unlink a lot from an empreendimento', function () {
    $this->actingAs(User::factory()->create());
    $emp = Empreendimento::factory()->create();
    $lot = Lot::factory()->create(['empreendimento_id' => $emp->id]);

    Livewire::test('pages::empreendimentos.show', ['empreendimento' => $emp])
        ->call('unlinkLot', $lot->id);

    expect($lot->fresh()->empreendimento_id)->toBeNull();
});

test('lot belongs to empreendimento', function () {
    $emp = Empreendimento::factory()->create();
    $lot = Lot::factory()->create(['empreendimento_id' => $emp->id]);

    expect($lot->empreendimento->id)->toBe($emp->id);
    expect($emp->lots->first()->id)->toBe($lot->id);
});

test('empreendimento_id is nullable for lots', function () {
    $lot = Lot::factory()->create(['empreendimento_id' => null]);

    expect($lot->empreendimento_id)->toBeNull();
    expect($lot->empreendimento)->toBeNull();
});
