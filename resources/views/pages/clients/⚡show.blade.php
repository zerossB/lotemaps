<?php

use App\Models\Client;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new class extends Component {
    public Client $client;

    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $document = '';
    public string $notes = '';

    public function mount(): void
    {
        $this->name     = $this->client->name;
        $this->email    = $this->client->email ?? '';
        $this->phone    = $this->client->phone ?? '';
        $this->document = $this->client->document ?? '';
        $this->notes    = $this->client->notes ?? '';
    }

    public function updateClient(): void
    {
        $validated = $this->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['nullable', 'email', 'max:255'],
            'phone'    => ['nullable', 'string', 'max:50'],
            'document' => ['nullable', 'string', 'max:20'],
            'notes'    => ['nullable', 'string'],
        ]);

        $this->client->update($validated);

        Flux::toast(variant: 'success', heading: __('Client updated.'), text: __('Changes saved.'));
    }

    public function render(): \Illuminate\View\View
    {
        return $this->view()->title("Client: {$this->client->name}");
    }
}; ?>

<div>
    <div class="mb-6">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('clients.index')" wire:navigate>
                {{ __('Clients') }}
            </flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $client->name }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <flux:card>
                    <flux:heading class="mb-4">{{ __('Client Details') }}</flux:heading>
                    <form wire:submit="updateClient" class="space-y-4">
                        <flux:input wire:model="name" :label="__('Name')" required />
                        <flux:input wire:model="email" :label="__('Email')" type="email" />
                        <flux:input wire:model="phone" :label="__('Phone')" />
                        <flux:input wire:model="document" :label="__('Document (CPF/CNPJ)')" />
                        <flux:textarea wire:model="notes" :label="__('Notes')" rows="3" />

                        <div class="flex items-center gap-4">
                            <flux:button type="submit" variant="primary">{{ __('Save Changes') }}</flux:button>
                        </div>
                    </form>
                </flux:card>
            </div>

            <div>
                <flux:card>
                    <flux:heading class="mb-4">{{ __('Proposals') }}</flux:heading>
                    @forelse ($client->proposals()->with('user')->latest()->get() as $proposal)
                        <div class="flex items-center justify-between py-2">
                            <flux:link :href="route('proposals.show', $proposal)" wire:navigate class="text-sm">
                                #{{ $proposal->id }}
                            </flux:link>
                            <flux:badge :color="$proposal->status->color()" size="sm">
                                {{ $proposal->status->label() }}
                            </flux:badge>
                        </div>
                    @empty
                        <flux:text>{{ __('No proposals yet.') }}</flux:text>
                    @endforelse

                    <div class="mt-4">
                        <flux:button
                            :href="route('proposals.create', ['client_id' => $client->id])"
                            wire:navigate
                            variant="ghost"
                            size="sm"
                            icon="plus"
                        >
                            {{ __('New Proposal') }}
                        </flux:button>
                    </div>
                </flux:card>
            </div>
        </div>
</div>

