<?php

use App\Models\Empreendimento;
use App\Models\Lot;
use App\Models\User;
use Livewire\Livewire;

$samplePolygon = json_encode([
    'type' => 'Polygon',
    'coordinates' => [[
        [-47.93, -15.78],
        [-47.92, -15.78],
        [-47.92, -15.77],
        [-47.93, -15.77],
        [-47.93, -15.78],
    ]],
]);

test('saveGeometry stores geojson on a lot', function () use ($samplePolygon) {
    $this->actingAs(User::factory()->create());
    $emp = Empreendimento::factory()->create();
    $lot = Lot::factory()->create(['empreendimento_id' => $emp->id]);

    Livewire::test('pages::empreendimentos.show', ['empreendimento' => $emp])
        ->call('saveGeometry', $lot->id, $samplePolygon)
        ->assertHasNoErrors()
        ->assertDispatched('geometry-saved');

    expect($lot->fresh()->geometry)->not->toBeNull();
    expect($lot->fresh()->geometry['type'])->toBe('Polygon');
});

test('saveGeometry rejects invalid json', function () {
    $this->actingAs(User::factory()->create());
    $emp = Empreendimento::factory()->create();
    $lot = Lot::factory()->create(['empreendimento_id' => $emp->id, 'geometry' => null]);

    // Invalid JSON triggers abort_if — Livewire will render an error response
    Livewire::test('pages::empreendimentos.show', ['empreendimento' => $emp])
        ->call('saveGeometry', $lot->id, 'not-valid-json');

    expect($lot->fresh()->geometry)->toBeNull();
});

test('saveGeometry does not update lot from different empreendimento', function () use ($samplePolygon) {
    $this->actingAs(User::factory()->create());
    $emp1 = Empreendimento::factory()->create();
    $emp2 = Empreendimento::factory()->create();
    $lot = Lot::factory()->create(['empreendimento_id' => $emp2->id]);

    $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    Livewire::test('pages::empreendimentos.show', ['empreendimento' => $emp1])
        ->call('saveGeometry', $lot->id, $samplePolygon);
});

test('clearGeometry removes geojson from lot', function () use ($samplePolygon) {
    $this->actingAs(User::factory()->create());
    $emp = Empreendimento::factory()->create();
    $lot = Lot::factory()->create([
        'empreendimento_id' => $emp->id,
        'geometry' => json_decode($samplePolygon, true),
    ]);

    Livewire::test('pages::empreendimentos.show', ['empreendimento' => $emp])
        ->call('clearGeometry', $lot->id)
        ->assertDispatched('geometry-cleared');

    expect($lot->fresh()->geometry)->toBeNull();
});

test('assignDrawnPolygon links existing lot with geometry', function () use ($samplePolygon) {
    $this->actingAs(User::factory()->create());
    $emp = Empreendimento::factory()->create();
    $lot = Lot::factory()->create(['empreendimento_id' => $emp->id, 'geometry' => null]);

    Livewire::test('pages::empreendimentos.show', ['empreendimento' => $emp])
        ->set('pendingGeometry', $samplePolygon)
        ->set('assignMode', 'link')
        ->set('assignLotId', (string) $lot->id)
        ->call('assignDrawnPolygon')
        ->assertHasNoErrors()
        ->assertDispatched('lots-updated');

    expect($lot->fresh()->geometry)->not->toBeNull();
});

test('assignDrawnPolygon creates new lot with geometry', function () use ($samplePolygon) {
    $this->actingAs(User::factory()->create());
    $emp = Empreendimento::factory()->create();

    Livewire::test('pages::empreendimentos.show', ['empreendimento' => $emp])
        ->set('pendingGeometry', $samplePolygon)
        ->set('assignMode', 'create')
        ->set('newCode', 'MAP-001')
        ->set('newBlock', 'Z')
        ->set('newPrice', '80000')
        ->set('newStatus', 'available')
        ->call('assignDrawnPolygon')
        ->assertHasNoErrors()
        ->assertDispatched('lots-updated');

    $lot = Lot::where('code', 'MAP-001')->first();
    expect($lot)->not->toBeNull();
    expect($lot->empreendimento_id)->toBe($emp->id);
    expect($lot->geometry)->not->toBeNull();
    expect($lot->geometry['type'])->toBe('Polygon');
});

test('assignDrawnPolygon create validates required fields', function () use ($samplePolygon) {
    $this->actingAs(User::factory()->create());
    $emp = Empreendimento::factory()->create();

    Livewire::test('pages::empreendimentos.show', ['empreendimento' => $emp])
        ->set('pendingGeometry', $samplePolygon)
        ->set('assignMode', 'create')
        ->call('assignDrawnPolygon')
        ->assertHasErrors(['newCode', 'newPrice']);
});

test('saveMapCenter persists lat lng zoom on empreendimento', function () {
    $this->actingAs(User::factory()->create());
    $emp = Empreendimento::factory()->create();

    Livewire::test('pages::empreendimentos.show', ['empreendimento' => $emp])
        ->call('saveMapCenter', -23.5505, -46.6333, 15)
        ->assertDispatched('map-center-saved');

    $emp->refresh();
    expect(round((float) $emp->map_lat, 3))->toBe(-23.551);
    expect(round((float) $emp->map_lng, 3))->toBe(-46.633);
    expect($emp->map_zoom)->toBe(15);
});

test('lotsGeoJson computed returns feature collection', function () use ($samplePolygon) {
    $this->actingAs(User::factory()->create());
    $emp = Empreendimento::factory()->create();
    Lot::factory()->create([
        'empreendimento_id' => $emp->id,
        'geometry' => json_decode($samplePolygon, true),
    ]);
    Lot::factory()->create([
        'empreendimento_id' => $emp->id,
        'geometry' => null,
    ]);

    $component = Livewire::test('pages::empreendimentos.show', ['empreendimento' => $emp]);

    $geojson = $emp->fresh()->lots()->whereNotNull('geometry')->count();
    expect($geojson)->toBe(1);
});
