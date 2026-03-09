<?php

use App\Enums\EmpreendimentoStatus;
use App\Models\Empreendimento;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Developments')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public bool $showCreateModal = false;

    public string $name = '';
    public string $description = '';
    public string $address = '';
    public string $city = '';
    public string $state = '';
    public string $totalArea = '';
    public string $status = 'active';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function createEmpreendimento(): void
    {
        $validated = $this->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:empreendimentos,name'],
            'description' => ['nullable', 'string'],
            'address'     => ['nullable', 'string', 'max:255'],
            'city'        => ['nullable', 'string', 'max:100'],
            'state'       => ['nullable', 'string', 'size:2'],
            'totalArea'   => ['nullable', 'numeric', 'min:0'],
            'status'      => ['required', 'in:active,inactive'],
        ]);

        Empreendimento::create([
            'name'        => $validated['name'],
            'description' => $validated['description'],
            'address'     => $validated['address'],
            'city'        => $validated['city'],
            'state'       => $validated['state'] ? strtoupper($validated['state']) : null,
            'total_area'  => $validated['totalArea'] ?: null,
            'status'      => $validated['status'],
        ]);

        $this->reset('name', 'description', 'address', 'city', 'state', 'totalArea', 'showCreateModal');
        $this->status = 'active';
        $this->resetPage();
    }

    public function deleteEmpreendimento(int $id): void
    {
        Empreendimento::findOrFail($id)->delete();
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">{{ __('Developments') }}</flux:heading>
        <flux:button variant="primary" icon="plus" wire:click="$set('showCreateModal', true)">
            {{ __('New Development') }}
        </flux:button>
    </div>

    <div class="flex gap-3 mb-4">
        <flux:input
            wire:model.live.debounce.300ms="search"
            icon="magnifying-glass"
            :placeholder="__('Search developments...')"
            class="max-w-sm"
        />
        <flux:select wire:model.live="statusFilter" :placeholder="__('All statuses')" class="max-w-[180px]">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach (App\Enums\EmpreendimentoStatus::cases() as $empStatus)
                <flux:select.option :value="$empStatus->value">{{ $empStatus->label() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('City/State') }}</flux:table.column>
            <flux:table.column>{{ __('Total Area (m²)') }}</flux:table.column>
            <flux:table.column>{{ __('Lots') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column />
        </flux:table.columns>
        <flux:table.rows>
            @forelse (
                App\Models\Empreendimento::query()
                    ->withCount('lots')
                    ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%"))
                    ->when($statusFilter, fn ($q) => $q->where('status', $statusFilter))
                    ->orderBy('name')
                    ->paginate(15)
                as $emp
            )
                <flux:table.row :key="$emp->id">
                    <flux:table.cell>
                        <flux:link :href="route('empreendimentos.show', $emp)" wire:navigate class="font-medium">
                            {{ $emp->name }}
                        </flux:link>
                    </flux:table.cell>
                    <flux:table.cell>
                        @if ($emp->city || $emp->state)
                            {{ implode('/', array_filter([$emp->city, $emp->state])) }}
                        @else
                            —
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $emp->total_area ? number_format($emp->total_area, 2, ',', '.') : '—' }}
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $emp->lots_count }}
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$emp->status->color()" size="sm">
                            {{ $emp->status->label() }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:button
                            size="sm"
                            variant="ghost"
                            icon="trash"
                            wire:click="deleteEmpreendimento({{ $emp->id }})"
                            wire:confirm="{{ __('Delete this development?') }}"
                        />
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center py-8">
                        <flux:text>{{ __('No developments found.') }}</flux:text>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal wire:model.self="showCreateModal" class="min-w-[32rem]">
        <form wire:submit="createEmpreendimento" class="space-y-4">
            <flux:heading size="lg">{{ __('New Development') }}</flux:heading>

            <flux:input wire:model="name" :label="__('Name')" required />
            <flux:textarea wire:model="description" :label="__('Description')" rows="2" />
            <flux:input wire:model="address" :label="__('Address')" />
            <div class="grid grid-cols-3 gap-4">
                <flux:input wire:model="city" :label="__('City')" class="col-span-2" />
                <flux:input wire:model="state" :label="__('State')" maxlength="2" class="uppercase" />
            </div>
            <flux:input wire:model="totalArea" :label="__('Total Area (m²)')" type="number" step="0.01" min="0" />
            <flux:select wire:model="status" :label="__('Status')">
                @foreach (App\Enums\EmpreendimentoStatus::cases() as $empStatus)
                    <flux:select.option :value="$empStatus->value">{{ $empStatus->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex gap-2 justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
