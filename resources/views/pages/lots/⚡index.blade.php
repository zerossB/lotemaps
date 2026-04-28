<?php

use App\Enums\LotStatus;
use App\Models\Empreendimento;
use App\Models\Lot;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Lots')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $empreendimentoFilter = '';
    public bool $showCreateModal = false;

    public string $code = '';
    public string $block = '';
    public string $description = '';
    public string $areaSqm = '';
    public string $price = '';
    public string $status = 'available';
    public string $empreendimentoId = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedEmpreendimentoFilter(): void
    {
        $this->resetPage();
    }

    public function createLot(): void
    {
        $validated = $this->validate([
            'code'              => ['required', 'string', 'max:50', 'unique:lots,code'],
            'block'             => ['nullable', 'string', 'max:50'],
            'description'       => ['nullable', 'string'],
            'areaSqm'           => ['nullable', 'numeric', 'min:0'],
            'price'             => ['required', 'numeric', 'min:0'],
            'status'            => ['required', 'in:available,reserved,sold'],
            'empreendimentoId'  => ['nullable', 'integer', 'exists:empreendimentos,id'],
        ]);

        Lot::create([
            'empreendimento_id' => $validated['empreendimentoId'] ?: null,
            'code'        => $validated['code'],
            'block'       => $validated['block'],
            'description' => $validated['description'],
            'area_sqm'    => $validated['areaSqm'] ?: null,
            'price'       => $validated['price'],
            'status'      => $validated['status'],
        ]);

        $this->reset('code', 'block', 'description', 'areaSqm', 'price', 'empreendimentoId', 'showCreateModal');
        $this->status = 'available';
        $this->resetPage();

        Flux::toast(variant: 'success', text: __('Lot created.'));
    }

    public function deleteLot(int $lotId): void
    {
        Lot::findOrFail($lotId)->delete();

        Flux::toast(variant: 'success', text: __('Lot deleted.'));
    }
}; ?>

<div>
        <div class="flex items-center justify-between mb-6">
            <flux:heading size="xl">{{ __('Lots') }}</flux:heading>
            <flux:button variant="primary" icon="plus" wire:click="$set('showCreateModal', true)">
                {{ __('New Lot') }}
            </flux:button>
        </div>

        <div class="flex gap-3 mb-4">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                :placeholder="__('Search lots...')"
                class="max-w-sm"
            />
            <flux:select wire:model.live="statusFilter" :placeholder="__('All statuses')" class="max-w-[180px]">
                <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
                @foreach (App\Enums\LotStatus::cases() as $lotStatus)
                    <flux:select.option :value="$lotStatus->value">{{ $lotStatus->label() }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="empreendimentoFilter" :placeholder="__('All developments')" class="max-w-[220px]">
                <flux:select.option value="">{{ __('All developments') }}</flux:select.option>
                @foreach (App\Models\Empreendimento::orderBy('name')->get() as $emp)
                    <flux:select.option :value="$emp->id">{{ $emp->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Code') }}</flux:table.column>
                <flux:table.column>{{ __('Block') }}</flux:table.column>
                <flux:table.column>{{ __('Development') }}</flux:table.column>
                <flux:table.column>{{ __('Area (m²)') }}</flux:table.column>
                <flux:table.column>{{ __('Price') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column />
            </flux:table.columns>
            <flux:table.rows>
                @forelse (
                    App\Models\Lot::query()
                        ->with('empreendimento')
                        ->when($search, fn ($q) => $q->where('code', 'like', "%{$search}%")
                            ->orWhere('block', 'like', "%{$search}%"))
                        ->when($statusFilter, fn ($q) => $q->where('status', $statusFilter))
                        ->when($empreendimentoFilter, fn ($q) => $q->where('empreendimento_id', $empreendimentoFilter))
                        ->orderBy('code')
                        ->paginate(15)
                    as $lot
                )
                    <flux:table.row :key="$lot->id">
                        <flux:table.cell>
                            <flux:link :href="route('lots.show', $lot)" wire:navigate class="font-mono">
                                {{ $lot->code }}
                            </flux:link>
                        </flux:table.cell>
                        <flux:table.cell>{{ $lot->block ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($lot->empreendimento)
                                <flux:link :href="route('empreendimentos.show', $lot->empreendimento)" wire:navigate class="text-sm">
                                    {{ $lot->empreendimento->name }}
                                </flux:link>
                            @else
                                —
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $lot->area_sqm ? number_format($lot->area_sqm, 2) : '—' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            R$ {{ number_format($lot->price, 2, ',', '.') }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$lot->status->color()" size="sm">
                                {{ $lot->status->label() }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="trash"
                                wire:click="deleteLot({{ $lot->id }})"
                                wire:confirm="{{ __('Delete this lot?') }}"
                            />
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center py-8">
                            <flux:text>{{ __('No lots found.') }}</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <flux:modal wire:model.self="showCreateModal" class="min-w-[30rem]">
            <form wire:submit="createLot" class="space-y-4">
                <flux:heading size="lg">{{ __('New Lot') }}</flux:heading>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="code" :label="__('Code')" required />
                    <flux:input wire:model="block" :label="__('Block')" />
                </div>
                <flux:textarea wire:model="description" :label="__('Description')" rows="2" />
                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="areaSqm" :label="__('Area (m²)')" type="number" step="0.01" min="0" />
                    <flux:input wire:model="price" :label="__('Price (R$)')" type="number" step="0.01" min="0" required />
                </div>
                <flux:select wire:model="status" :label="__('Status')">
                    @foreach (App\Enums\LotStatus::cases() as $lotStatus)
                        <flux:select.option :value="$lotStatus->value">{{ $lotStatus->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="empreendimentoId" :label="__('Development')">
                    <flux:select.option value="">{{ __('— None —') }}</flux:select.option>
                    @foreach (App\Models\Empreendimento::orderBy('name')->get() as $emp)
                        <flux:select.option :value="$emp->id">{{ $emp->name }}</flux:select.option>
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

