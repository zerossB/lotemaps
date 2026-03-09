<?php

use App\Enums\ProposalStatus;
use App\Models\Client;
use App\Models\Lot;
use App\Models\Proposal;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('New Proposal')] class extends Component {
    public int|string $clientId = '';
    public array $selectedLotIds = [];
    public string $notes = '';
    public string $expiresAt = '';

    public function mount(): void
    {
        $preselectedClientId = request()->query('client_id');

        if ($preselectedClientId && Client::find($preselectedClientId)) {
            $this->clientId = (int) $preselectedClientId;
        }
    }

    public function updatedSelectedLotIds(): void
    {
        // Keep only integer lot IDs
        $this->selectedLotIds = array_values(
            array_filter(array_map('intval', $this->selectedLotIds))
        );
    }

    public function getTotalPriceProperty(): float
    {
        if (empty($this->selectedLotIds)) {
            return 0.0;
        }

        return (float) Lot::whereIn('id', $this->selectedLotIds)->sum('price');
    }

    public function saveProposal(): void
    {
        $this->validate([
            'clientId'      => ['required', 'exists:clients,id'],
            'selectedLotIds' => ['required', 'array', 'min:1'],
            'selectedLotIds.*' => ['exists:lots,id'],
            'notes'         => ['nullable', 'string'],
            'expiresAt'     => ['nullable', 'date', 'after:today'],
        ]);

        $lots = Lot::whereIn('id', $this->selectedLotIds)->get();

        $proposal = Proposal::create([
            'client_id'   => $this->clientId,
            'user_id'     => Auth::id(),
            'status'      => ProposalStatus::Draft,
            'total_price' => $lots->sum('price'),
            'notes'       => $this->notes ?: null,
            'expires_at'  => $this->expiresAt ?: null,
        ]);

        $pivotData = $lots->mapWithKeys(
            fn (Lot $lot) => [$lot->id => ['price' => $lot->price]]
        )->all();

        $proposal->lots()->attach($pivotData);

        $this->redirect(route('proposals.show', $proposal), navigate: true);
    }
}; ?>

<div>
        <div class="mb-6">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('proposals.index')" wire:navigate>
                    {{ __('Proposals') }}
                </flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ __('New') }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>

        <flux:heading size="xl" class="mb-6">{{ __('New Proposal') }}</flux:heading>

        <form wire:submit="saveProposal" class="space-y-6 max-w-2xl">
            {{-- Client --}}
            <flux:field>
                <flux:label>{{ __('Client') }}</flux:label>
                <flux:select wire:model.live="clientId" :placeholder="__('Select a client...')">
                    @foreach (App\Models\Client::orderBy('name')->get() as $client)
                        <flux:select.option :value="$client->id">{{ $client->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="clientId" />
            </flux:field>

            {{-- Lots --}}
            <flux:field>
                <flux:label>{{ __('Select Lots') }}</flux:label>
                <div class="space-y-2 mt-1 max-h-64 overflow-y-auto border border-zinc-200 dark:border-zinc-700 rounded-lg p-3">
                    @foreach (App\Models\Lot::orderBy('code')->get() as $lot)
                        <label class="flex items-center gap-3 cursor-pointer">
                            <flux:checkbox
                                wire:model.live="selectedLotIds"
                                :value="$lot->id"
                            />
                            <div class="flex-1 flex items-center justify-between">
                                <div>
                                    <span class="font-mono text-sm font-medium">{{ $lot->code }}</span>
                                    @if ($lot->block)
                                        <span class="text-sm text-zinc-500 dark:text-zinc-400"> · Block {{ $lot->block }}</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2">
                                    <flux:badge :color="$lot->status->color()" size="sm">
                                        {{ $lot->status->label() }}
                                    </flux:badge>
                                    <span class="text-sm font-medium">
                                        R$ {{ number_format($lot->price, 2, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
                <flux:error name="selectedLotIds" />
            </flux:field>

            {{-- Total --}}
            @if (count($selectedLotIds) > 0)
                <flux:callout icon="calculator" color="blue">
                    <flux:callout.heading>{{ __('Total Price') }}</flux:callout.heading>
                    <flux:callout.text>
                        R$ {{ number_format($this->getTotalPriceProperty(), 2, ',', '.') }}
                        ({{ count($selectedLotIds) }} {{ trans_choice('lot|lots', count($selectedLotIds)) }})
                    </flux:callout.text>
                </flux:callout>
            @endif

            {{-- Notes --}}
            <flux:textarea wire:model="notes" :label="__('Notes')" rows="3" />

            {{-- Expires --}}
            <flux:input wire:model="expiresAt" :label="__('Expires at')" type="date" />

            <div class="flex gap-3">
                <flux:button type="submit" variant="primary">{{ __('Create Proposal') }}</flux:button>
                <flux:button :href="route('proposals.index')" wire:navigate variant="ghost">
                    {{ __('Cancel') }}
                </flux:button>
            </div>
        </form>
</div>

