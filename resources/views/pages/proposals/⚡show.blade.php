<?php

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use Livewire\Attributes\Title;
use Livewire\Component;

new class extends Component {
    public Proposal $proposal;

    public string $notes = '';
    public string $expiresAt = '';

    public function mount(): void
    {
        $this->notes     = $this->proposal->notes ?? '';
        $this->expiresAt = $this->proposal->expires_at?->format('Y-m-d') ?? '';
    }

    public function updateProposal(): void
    {
        $this->validate([
            'notes'     => ['nullable', 'string'],
            'expiresAt' => ['nullable', 'date'],
        ]);

        $this->proposal->update([
            'notes'      => $this->notes ?: null,
            'expires_at' => $this->expiresAt ?: null,
        ]);

        $this->dispatch('proposal-updated');
    }

    public function transitionStatus(string $newStatus): void
    {
        $status = ProposalStatus::from($newStatus);
        $allowed = ProposalStatus::transitionsFrom($this->proposal->status);

        if (! in_array($status, $allowed)) {
            return;
        }

        $this->proposal->update(['status' => $status]);
        $this->proposal->refresh();
    }

    public function deleteProposal(): void
    {
        $this->proposal->delete();
        $this->redirect(route('proposals.index'), navigate: true);
    }

    public function render(): \Illuminate\View\View
    {
        return $this->view()->title("Proposal #{$this->proposal->id}");
    }
}; ?>

<div>
        <div class="mb-6">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('proposals.index')" wire:navigate>
                    {{ __('Proposals') }}
                </flux:breadcrumbs.item>
                <flux:breadcrumbs.item>#{{ $proposal->id }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>

        <div class="flex items-start justify-between mb-6">
            <div>
                <flux:heading size="xl">{{ __('Proposal') }} #{{ $proposal->id }}</flux:heading>
                <flux:text class="mt-1">
                    {{ __('Client:') }}
                    <flux:link :href="route('clients.show', $proposal->client)" wire:navigate>
                        {{ $proposal->client->name }}
                    </flux:link>
                    · {{ __('Created by') }} {{ $proposal->user->name }}
                    · {{ $proposal->created_at->format('d/m/Y') }}
                </flux:text>
            </div>
            <flux:badge :color="$proposal->status->color()" size="lg">
                {{ $proposal->status->label() }}
            </flux:badge>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6">
                {{-- Lots --}}
                <flux:card>
                    <flux:heading class="mb-4">{{ __('Lots') }}</flux:heading>
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>{{ __('Code') }}</flux:table.column>
                            <flux:table.column>{{ __('Block') }}</flux:table.column>
                            <flux:table.column>{{ __('Area (m²)') }}</flux:table.column>
                            <flux:table.column>{{ __('Price') }}</flux:table.column>
                            <flux:table.column>{{ __('Status') }}</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($proposal->lots as $lot)
                                <flux:table.row :key="$lot->id">
                                    <flux:table.cell>
                                        <flux:link :href="route('lots.show', $lot)" wire:navigate class="font-mono">
                                            {{ $lot->code }}
                                        </flux:link>
                                    </flux:table.cell>
                                    <flux:table.cell>{{ $lot->block ?? '—' }}</flux:table.cell>
                                    <flux:table.cell>
                                        {{ $lot->area_sqm ? number_format($lot->area_sqm, 2) : '—' }}
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        R$ {{ number_format($lot->pivot->price, 2, ',', '.') }}
                                    </flux:table.cell>
                                    <flux:table.cell>
                                        <flux:badge :color="$lot->status->color()" size="sm">
                                            {{ $lot->status->label() }}
                                        </flux:badge>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>

                    <div class="mt-4 flex justify-end">
                        <flux:text class="font-semibold">
                            {{ __('Total:') }} R$ {{ number_format($proposal->total_price, 2, ',', '.') }}
                        </flux:text>
                    </div>
                </flux:card>

                {{-- Notes & Expiry --}}
                <flux:card>
                    <flux:heading class="mb-4">{{ __('Details') }}</flux:heading>
                    <form wire:submit="updateProposal" class="space-y-4">
                        <flux:textarea wire:model="notes" :label="__('Notes')" rows="4" />
                        <flux:input wire:model="expiresAt" :label="__('Expires at')" type="date" />

                        <div class="flex items-center gap-4">
                            <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
                            <x-action-message on="proposal-updated">{{ __('Saved.') }}</x-action-message>
                        </div>
                    </form>
                </flux:card>
            </div>

            {{-- Status transitions + Danger --}}
            <div class="space-y-6">
                @php
                    $availableTransitions = App\Enums\ProposalStatus::transitionsFrom($proposal->status);
                @endphp

                @if (count($availableTransitions) > 0)
                    <flux:card>
                        <flux:heading class="mb-4">{{ __('Actions') }}</flux:heading>
                        <div class="space-y-2">
                            @foreach ($availableTransitions as $transition)
                                <flux:button
                                    wire:click="transitionStatus('{{ $transition->value }}')"
                                    variant="{{ $transition === App\Enums\ProposalStatus::Accepted ? 'primary' : ($transition === App\Enums\ProposalStatus::Rejected ? 'danger' : 'ghost') }}"
                                    class="w-full"
                                >
                                    {{ __('Mark as') }} {{ $transition->label() }}
                                </flux:button>
                            @endforeach
                        </div>
                    </flux:card>
                @endif

                <flux:card>
                    <flux:heading class="mb-4">{{ __('Danger Zone') }}</flux:heading>
                    <flux:modal.trigger name="delete-proposal">
                        <flux:button variant="danger" class="w-full">{{ __('Delete Proposal') }}</flux:button>
                    </flux:modal.trigger>
                </flux:card>
            </div>
        </div>

        <flux:modal name="delete-proposal" class="min-w-[22rem]">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('Delete proposal?') }}</flux:heading>
                    <flux:text class="mt-2">{{ __('This action cannot be reversed.') }}</flux:text>
                </div>
                <div class="flex gap-2 justify-end">
                    <flux:modal.close>
                        <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button
                        variant="danger"
                        wire:click="deleteProposal"
                    >
                        {{ __('Delete') }}
                    </flux:button>
                </div>
            </div>
        </flux:modal>
</div>

