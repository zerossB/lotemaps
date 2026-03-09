<?php

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Proposals')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }
}; ?>

<div>
        <div class="flex items-center justify-between mb-6">
            <flux:heading size="xl">{{ __('Proposals') }}</flux:heading>
            <flux:button
                variant="primary"
                icon="plus"
                :href="route('proposals.create')"
                wire:navigate
            >
                {{ __('New Proposal') }}
            </flux:button>
        </div>

        <div class="flex gap-3 mb-4">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                :placeholder="__('Search by client name...')"
                class="max-w-sm"
            />
            <flux:select wire:model.live="statusFilter" :placeholder="__('All statuses')" class="max-w-[180px]">
                <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
                @foreach (App\Enums\ProposalStatus::cases() as $proposalStatus)
                    <flux:select.option :value="$proposalStatus->value">
                        {{ $proposalStatus->label() }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('#') }}</flux:table.column>
                <flux:table.column>{{ __('Client') }}</flux:table.column>
                <flux:table.column>{{ __('Lots') }}</flux:table.column>
                <flux:table.column>{{ __('Total Price') }}</flux:table.column>
                <flux:table.column>{{ __('Expires') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Created by') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse (
                    App\Models\Proposal::query()
                        ->with(['client', 'user'])
                        ->withCount('lots')
                        ->when($search, fn ($q) => $q->whereHas(
                            'client',
                            fn ($c) => $c->where('name', 'like', "%{$search}%")
                        ))
                        ->when($statusFilter, fn ($q) => $q->where('status', $statusFilter))
                        ->latest()
                        ->paginate(15)
                    as $proposal
                )
                    <flux:table.row :key="$proposal->id">
                        <flux:table.cell>
                            <flux:link :href="route('proposals.show', $proposal)" wire:navigate>
                                #{{ $proposal->id }}
                            </flux:link>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:link :href="route('clients.show', $proposal->client)" wire:navigate>
                                {{ $proposal->client->name }}
                            </flux:link>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge color="zinc" size="sm">{{ $proposal->lots_count }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            R$ {{ number_format($proposal->total_price, 2, ',', '.') }}
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $proposal->expires_at?->format('d/m/Y') ?? '—' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$proposal->status->color()" size="sm">
                                {{ $proposal->status->label() }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $proposal->user->name }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center py-8">
                            <flux:text>{{ __('No proposals found.') }}</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
</div>

