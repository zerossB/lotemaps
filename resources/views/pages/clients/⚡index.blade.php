<?php

use App\Models\Client;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Clients')] class extends Component {
    use WithPagination;

    public string $search = '';
    public bool $showCreateModal = false;

    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $document = '';
    public string $notes = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function createClient(): void
    {
        $validated = $this->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['nullable', 'email', 'max:255'],
            'phone'    => ['nullable', 'string', 'max:50'],
            'document' => ['nullable', 'string', 'max:20'],
            'notes'    => ['nullable', 'string'],
        ]);

        Client::create($validated);

        $this->reset('name', 'email', 'phone', 'document', 'notes', 'showCreateModal');
        $this->resetPage();
    }

    public function deleteClient(int $clientId): void
    {
        Client::findOrFail($clientId)->delete();
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">{{ __('Clients') }}</flux:heading>
        <flux:button variant="primary" icon="plus" wire:click="$set('showCreateModal', true)">
            {{ __('New Client') }}
        </flux:button>
    </div>

    <flux:input
        wire:model.live.debounce.300ms="search"
        icon="magnifying-glass"
        :placeholder="__('Search clients...')"
        class="mb-4 max-w-sm"
    />

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Email') }}</flux:table.column>
            <flux:table.column>{{ __('Phone') }}</flux:table.column>
            <flux:table.column>{{ __('Proposals') }}</flux:table.column>
            <flux:table.column />
        </flux:table.columns>
        <flux:table.rows>
            @forelse (
                App\Models\Client::query()
                    ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"))
                    ->withCount('proposals')
                    ->orderBy('name')
                    ->paginate(15)
                as $client
            )
                <flux:table.row :key="$client->id">
                    <flux:table.cell>
                        <flux:link :href="route('clients.show', $client)" wire:navigate>
                            {{ $client->name }}
                        </flux:link>
                    </flux:table.cell>
                    <flux:table.cell>{{ $client->email ?? '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $client->phone ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="zinc" size="sm">{{ $client->proposals_count }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:button
                            size="sm"
                            variant="ghost"
                            icon="trash"
                            wire:click="deleteClient({{ $client->id }})"
                            wire:confirm="{{ __('Delete this client?') }}"
                        />
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center py-8">
                        <flux:text>{{ __('No clients found.') }}</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal wire:model.self="showCreateModal" class="min-w-[28rem]">
        <form wire:submit="createClient" class="space-y-4">
            <flux:heading size="lg">{{ __('New Client') }}</flux:heading>

            <flux:input wire:model="name" :label="__('Name')" required />
            <flux:input wire:model="email" :label="__('Email')" type="email" />
            <flux:input wire:model="phone" :label="__('Phone')" />
            <flux:input wire:model="document" :label="__('Document (CPF/CNPJ)')" />
            <flux:textarea wire:model="notes" :label="__('Notes')" rows="2" />

            <div class="flex gap-2 justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

