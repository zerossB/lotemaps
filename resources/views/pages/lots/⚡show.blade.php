<?php

use App\Enums\LotStatus;
use App\Models\Empreendimento;
use App\Models\Lot;
use Livewire\Attributes\Title;
use Livewire\Component;

new class extends Component {
    public Lot $lot;

    public string $code = '';
    public string $block = '';
    public string $description = '';
    public string $areaSqm = '';
    public string $price = '';
    public string $status = '';
    public string $empreendimentoId = '';

    public function mount(): void
    {
        $this->code        = $this->lot->code;
        $this->block       = $this->lot->block ?? '';
        $this->description = $this->lot->description ?? '';
        $this->areaSqm     = $this->lot->area_sqm ? (string) $this->lot->area_sqm : '';
        $this->price       = (string) $this->lot->price;
        $this->status      = $this->lot->status->value;
        $this->empreendimentoId = $this->lot->empreendimento_id ? (string) $this->lot->empreendimento_id : '';
    }

    public function updateLot(): void
    {
        $validated = $this->validate([
            'code'             => ['required', 'string', 'max:50', "unique:lots,code,{$this->lot->id}"],
            'block'            => ['nullable', 'string', 'max:50'],
            'description'      => ['nullable', 'string'],
            'areaSqm'          => ['nullable', 'numeric', 'min:0'],
            'price'            => ['required', 'numeric', 'min:0'],
            'status'           => ['required', 'in:available,reserved,sold'],
            'empreendimentoId' => ['nullable', 'integer', 'exists:empreendimentos,id'],
        ]);

        $this->lot->update([
            'empreendimento_id' => $validated['empreendimentoId'] ?: null,
            'code'        => $validated['code'],
            'block'       => $validated['block'],
            'description' => $validated['description'],
            'area_sqm'    => $validated['areaSqm'] ?: null,
            'price'       => $validated['price'],
            'status'      => $validated['status'],
        ]);

        $this->dispatch('lot-updated');
    }

    public function render(): \Illuminate\View\View
    {
        return $this->view()->title("Lot: {$this->lot->code}");
    }
}; ?>

<div>
        <div class="mb-6">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('lots.index')" wire:navigate>
                    {{ __('Lots') }}
                </flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ $lot->code }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <flux:card>
                    <flux:heading class="mb-4">{{ __('Lot Details') }}</flux:heading>
                    <form wire:submit="updateLot" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <flux:input wire:model="code" :label="__('Code')" required />
                            <flux:input wire:model="block" :label="__('Block')" />
                        </div>
                        <flux:textarea wire:model="description" :label="__('Description')" rows="3" />
                        <div class="grid grid-cols-2 gap-4">
                            <flux:input
                                wire:model="areaSqm"
                                :label="__('Area (m²)')"
                                type="number"
                                step="0.01"
                                min="0"
                            />
                            <flux:input
                                wire:model="price"
                                :label="__('Price (R$)')"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                            />
                        </div>
                        <flux:select wire:model="status" :label="__('Status')">
                            @foreach (App\Enums\LotStatus::cases() as $lotStatus)
                                <flux:select.option :value="$lotStatus->value">
                                    {{ $lotStatus->label() }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:select wire:model="empreendimentoId" :label="__('Development')">
                            <flux:select.option value="">{{ __('— None —') }}</flux:select.option>
                            @foreach (App\Models\Empreendimento::orderBy('name')->get() as $emp)
                                <flux:select.option :value="$emp->id">{{ $emp->name }}</flux:select.option>
                            @endforeach
                        </flux:select>

                        <div class="flex items-center gap-4">
                            <flux:button type="submit" variant="primary">{{ __('Save Changes') }}</flux:button>
                            <x-action-message on="lot-updated">{{ __('Saved.') }}</x-action-message>
                        </div>
                    </form>
                </flux:card>
            </div>

            <div>
                <flux:card>
                    <flux:heading class="mb-4">{{ __('Proposals') }}</flux:heading>
                    @forelse ($lot->proposals()->with('client')->latest()->get() as $proposal)
                        <div class="flex items-center justify-between py-2">
                            <flux:link :href="route('proposals.show', $proposal)" wire:navigate class="text-sm">
                                {{ $proposal->client->name }}
                            </flux:link>
                            <flux:badge :color="$proposal->status->color()" size="sm">
                                {{ $proposal->status->label() }}
                            </flux:badge>
                        </div>
                    @empty
                        <flux:text>{{ __('No proposals for this lot.') }}</flux:text>
                    @endforelse
                </flux:card>
            </div>
        </div>
</div>

