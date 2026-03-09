<?php

use App\Enums\ProposalStatus;
use App\Models\Client;
use App\Models\Lot;
use App\Models\Proposal;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected from proposals index', function () {
    $this->get(route('proposals.index'))->assertRedirect(route('login'));
});

test('authenticated users can view proposals index', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('proposals.index'))->assertSuccessful();
});

test('can create a proposal', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $client = Client::factory()->create();
    $lots = Lot::factory()->count(2)->create();

    Livewire::test('pages::proposals.create')
        ->set('clientId', $client->id)
        ->set('selectedLotIds', $lots->pluck('id')->toArray())
        ->call('saveProposal')
        ->assertHasNoErrors();

    expect(Proposal::where('client_id', $client->id)->exists())->toBeTrue();
});

test('proposal requires client', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $lot = Lot::factory()->create();

    Livewire::test('pages::proposals.create')
        ->set('clientId', '')
        ->set('selectedLotIds', [$lot->id])
        ->call('saveProposal')
        ->assertHasErrors(['clientId' => 'required']);
});

test('proposal requires at least one lot', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $client = Client::factory()->create();

    Livewire::test('pages::proposals.create')
        ->set('clientId', $client->id)
        ->set('selectedLotIds', [])
        ->call('saveProposal')
        ->assertHasErrors(['selectedLotIds' => 'required']);
});

test('proposal total price is calculated from lots', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $client = Client::factory()->create();
    $lot1 = Lot::factory()->create(['price' => 100000]);
    $lot2 = Lot::factory()->create(['price' => 200000]);

    Livewire::test('pages::proposals.create')
        ->set('clientId', $client->id)
        ->set('selectedLotIds', [$lot1->id, $lot2->id])
        ->call('saveProposal');

    $proposal = Proposal::where('client_id', $client->id)->first();
    expect((float) $proposal->total_price)->toBe(300000.0);
});

test('proposal starts as draft', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $client = Client::factory()->create();
    $lot = Lot::factory()->create();

    Livewire::test('pages::proposals.create')
        ->set('clientId', $client->id)
        ->set('selectedLotIds', [$lot->id])
        ->call('saveProposal');

    $proposal = Proposal::where('client_id', $client->id)->first();
    expect($proposal->status)->toBe(ProposalStatus::Draft);
});

test('can transition proposal from draft to sent', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $proposal = Proposal::factory()->draft()->create(['user_id' => $user->id]);

    Livewire::test('pages::proposals.show', ['proposal' => $proposal])
        ->call('transitionStatus', ProposalStatus::Sent->value);

    expect($proposal->fresh()->status)->toBe(ProposalStatus::Sent);
});

test('cannot transition proposal to invalid status', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $proposal = Proposal::factory()->accepted()->create(['user_id' => $user->id]);

    Livewire::test('pages::proposals.show', ['proposal' => $proposal])
        ->call('transitionStatus', ProposalStatus::Sent->value);

    // Accepted proposals cannot go back to sent
    expect($proposal->fresh()->status)->toBe(ProposalStatus::Accepted);
});

test('can delete a proposal', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $proposal = Proposal::factory()->create(['user_id' => $user->id]);

    Livewire::test('pages::proposals.show', ['proposal' => $proposal])
        ->call('deleteProposal');

    expect(Proposal::find($proposal->id))->toBeNull();
});

test('can update proposal notes', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $proposal = Proposal::factory()->create(['user_id' => $user->id]);

    Livewire::test('pages::proposals.show', ['proposal' => $proposal])
        ->set('notes', 'Updated notes')
        ->call('updateProposal')
        ->assertHasNoErrors()
        ->assertDispatched('proposal-updated');

    expect($proposal->fresh()->notes)->toBe('Updated notes');
});
