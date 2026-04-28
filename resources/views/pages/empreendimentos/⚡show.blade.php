<?php

use App\Enums\EmpreendimentoStatus;
use App\Enums\LotStatus;
use App\Models\Empreendimento;
use App\Models\Lot;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination, WithFileUploads;

    public Empreendimento $empreendimento;

    // Dados gerais
    public string $name = '';
    public string $description = '';
    public string $address = '';
    public string $city = '';
    public string $state = '';
    public string $totalArea = '';
    public string $status = '';

    // Map settings
    public string $mapType = 'map'; // 'map' | 'image'
    public $mapImageUpload = null;  // TemporaryUploadedFile

    // Lotes
    public string $lotSearch = '';
    public bool $showLinkModal = false;
    public int|null $selectedLotId = null;

    // Mapa
    public bool $showMapAssignModal = false;
    public string $pendingGeometry = '';   // GeoJSON string do polígono desenhado
    public string $assignMode = 'link';    // 'link' | 'create'
    public string $assignLotId = '';
    public string $newCode = '';
    public string $newBlock = '';
    public string $newPrice = '';
    public string $newStatus = 'available';

    // Lot details modal
    public bool $showLotDetailsModal = false;
    public array $viewingLotProps = [];

    public function mount(): void
    {
        $this->name        = $this->empreendimento->name;
        $this->description = $this->empreendimento->description ?? '';
        $this->address     = $this->empreendimento->address ?? '';
        $this->city        = $this->empreendimento->city ?? '';
        $this->state       = $this->empreendimento->state ?? '';
        $this->totalArea   = $this->empreendimento->total_area ? (string) $this->empreendimento->total_area : '';
        $this->status      = $this->empreendimento->status->value;
        $this->mapType     = $this->empreendimento->map_type ?? 'map';
    }

    public function updateEmpreendimento(): void
    {
        $validated = $this->validate([
            'name'        => ['required', 'string', 'max:255', "unique:empreendimentos,name,{$this->empreendimento->id}"],
            'description' => ['nullable', 'string'],
            'address'     => ['nullable', 'string', 'max:255'],
            'city'        => ['nullable', 'string', 'max:100'],
            'state'       => ['nullable', 'string', 'size:2'],
            'totalArea'   => ['nullable', 'numeric', 'min:0'],
            'status'      => ['required', 'in:active,inactive'],
        ]);

        $this->empreendimento->update([
            'name'        => $validated['name'],
            'description' => $validated['description'],
            'address'     => $validated['address'],
            'city'        => $validated['city'],
            'state'       => $validated['state'] ? strtoupper($validated['state']) : null,
            'total_area'  => $validated['totalArea'] ?: null,
            'status'      => $validated['status'],
        ]);

        $this->dispatch('empreendimento-updated');

        Flux::toast(variant: 'success', heading: __('Development updated.'), text: __('Changes saved.'));
    }

    public function saveMapType(): void
    {
        $this->validate([
            'mapType' => ['required', 'in:map,image'],
        ]);

        $this->empreendimento->update(['map_type' => $this->mapType]);
        $this->dispatch('map-type-saved');

        Flux::toast(variant: 'success', text: __('Map mode saved.'));
    }

    public function uploadMapImage(): void
    {
        $this->validate([
            'mapImageUpload' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        // Remove old image if exists
        if ($this->empreendimento->map_image) {
            \Storage::disk('public')->delete($this->empreendimento->map_image);
        }

        $path = $this->mapImageUpload->store(
            "map-images/{$this->empreendimento->id}",
            'public'
        );

        // Get image dimensions
        $fullPath = \Storage::disk('public')->path($path);
        [$width, $height] = getimagesize($fullPath);

        $this->empreendimento->update([
            'map_type'         => 'image',
            'map_image'        => $path,
            'map_image_width'  => $width,
            'map_image_height' => $height,
        ]);

        $this->mapType        = 'image';
        $this->mapImageUpload = null;

        $this->dispatch('map-image-saved');

        Flux::toast(variant: 'success', text: __('Map image uploaded.'));
    }

    public function removeMapImage(): void
    {
        if ($this->empreendimento->map_image) {
            \Storage::disk('public')->delete($this->empreendimento->map_image);
        }

        $this->empreendimento->update([
            'map_type'         => 'map',
            'map_image'        => null,
            'map_image_width'  => null,
            'map_image_height' => null,
        ]);

        $this->mapType = 'map';
        $this->dispatch('map-image-removed');
    }

    public function unlinkLot(int $lotId): void
    {
        Lot::where('id', $lotId)
            ->where('empreendimento_id', $this->empreendimento->id)
            ->update(['empreendimento_id' => null]);
    }

    public function linkLot(): void
    {
        $this->validate([
            'selectedLotId' => ['required', 'integer', 'exists:lots,id'],
        ]);

        Lot::where('id', $this->selectedLotId)
            ->whereNull('empreendimento_id')
            ->update(['empreendimento_id' => $this->empreendimento->id]);

        $this->reset('selectedLotId', 'showLinkModal');
    }

    // ── Map actions ──────────────────────────────────────────────────────────

    public function saveGeometry(int $lotId, string $geometry): void
    {
        $lot = Lot::where('id', $lotId)
            ->where('empreendimento_id', $this->empreendimento->id)
            ->firstOrFail();

        $decoded = json_decode($geometry, true);
        abort_if($decoded === null || ! isset($decoded['type']), 422, 'Invalid GeoJSON');

        $lot->update(['geometry' => $decoded]);

        $this->dispatch('geometry-saved', lotId: $lotId);
    }

    public function clearGeometry(int $lotId): void
    {
        Lot::where('id', $lotId)
            ->where('empreendimento_id', $this->empreendimento->id)
            ->update(['geometry' => null]);

        $this->dispatch('geometry-cleared', lotId: $lotId);
    }

    public function openLotDetailsModal(int $lotId): void
    {
        $lot = Lot::where('id', $lotId)
            ->where('empreendimento_id', $this->empreendimento->id)
            ->firstOrFail();

        $this->viewingLotProps = [
            'id'    => $lot->id,
            'code'  => $lot->code,
            'block' => $lot->block,
            'price' => (float) $lot->price,
            'label' => $lot->status->label(),
            'color' => $lot->status->color(),
            'area'  => $lot->area_sqm,
        ];

        $this->showLotDetailsModal = true;
    }

    public function assignDrawnPolygon(): void
    {
        $this->validate([
            'pendingGeometry' => ['required', 'string'],
        ]);

        $geometry = json_decode($this->pendingGeometry, true);
        abort_if($geometry === null, 422, 'Invalid GeoJSON');

        if ($this->assignMode === 'link') {
            $this->validate([
                'assignLotId' => ['required', 'integer', 'exists:lots,id'],
            ]);

            $lot = Lot::where('id', $this->assignLotId)
                ->where('empreendimento_id', $this->empreendimento->id)
                ->firstOrFail();

            $lot->update(['geometry' => $geometry]);
        } else {
            $this->validate([
                'newCode'   => ['required', 'string', 'max:50', 'unique:lots,code'],
                'newBlock'  => ['nullable', 'string', 'max:50'],
                'newPrice'  => ['required', 'numeric', 'min:0'],
                'newStatus' => ['required', 'in:available,reserved,sold'],
            ]);

            Lot::create([
                'empreendimento_id' => $this->empreendimento->id,
                'code'              => $this->newCode,
                'block'             => $this->newBlock ?: null,
                'price'             => $this->newPrice,
                'status'            => $this->newStatus,
                'geometry'          => $geometry,
            ]);
        }

        $this->reset('showMapAssignModal', 'pendingGeometry', 'assignMode', 'assignLotId', 'newCode', 'newBlock', 'newPrice');
        $this->assignMode  = 'link';
        $this->newStatus   = 'available';

        $this->dispatch('lots-updated');
    }

    public function saveMapCenter(float $lat, float $lng, int $zoom): void
    {
        $this->empreendimento->update([
            'map_lat'  => $lat,
            'map_lng'  => $lng,
            'map_zoom' => $zoom,
        ]);

        $this->dispatch('map-center-saved');
    }

    public function getAvailableLotsProperty()
    {
        return Lot::whereNull('empreendimento_id')->orderBy('code')->get();
    }

    public function getLotsGeoJsonProperty(): array
    {
        $features = $this->empreendimento->lots()
            ->whereNotNull('geometry')
            ->get()
            ->map(fn (Lot $lot) => [
                'type'       => 'Feature',
                'geometry'   => $lot->geometry,
                'properties' => [
                    'id'     => $lot->id,
                    'code'   => $lot->code,
                    'block'  => $lot->block,
                    'price'  => (float) $lot->price,
                    'status' => $lot->status->value,
                    'color'  => $lot->status->color(),
                    'label'  => $lot->status->label(),
                ],
            ])
            ->values()
            ->all();

        return [
            'type'     => 'FeatureCollection',
            'features' => $features,
        ];
    }

    public function render(): \Illuminate\View\View
    {
        return $this->view()->title("Empreendimento: {$this->empreendimento->name}");
    }
}; ?>

@assets
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
@endassets

<div x-data="{ tab: 'dados' }">
    <div class="mb-6">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('empreendimentos.index')" wire:navigate>
                {{ __('Developments') }}
            </flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $empreendimento->name }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    {{-- Tab navigation --}}
    <div class="flex gap-1 mb-6 border-b border-zinc-200 dark:border-zinc-700">
        <button
            type="button"
            @click="tab = 'dados'"
            :class="tab === 'dados' ? 'border-b-2 border-zinc-900 dark:border-white text-zinc-900 dark:text-white font-medium' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'"
            class="px-4 py-2 text-sm transition-colors -mb-px"
        >{{ __('Data') }}</button>
        <button
            type="button"
            @click="tab = 'lotes'"
            :class="tab === 'lotes' ? 'border-b-2 border-zinc-900 dark:border-white text-zinc-900 dark:text-white font-medium' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'"
            class="px-4 py-2 text-sm transition-colors -mb-px"
        >{{ __('Lots') }}</button>
        <button
            type="button"
            @click="tab = 'mapa'; $nextTick(() => $dispatch('map-tab-activated'))"
            :class="tab === 'mapa' ? 'border-b-2 border-zinc-900 dark:border-white text-zinc-900 dark:text-white font-medium' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'"
            class="px-4 py-2 text-sm transition-colors -mb-px"
        >{{ __('Map') }}</button>
    </div>

    {{-- ── ABA: DADOS ──────────────────────────────────────────────── --}}
    <div x-show="tab === 'dados'" x-cloak>
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6">
                <flux:card>
                    <flux:heading class="mb-4">{{ __('Development Details') }}</flux:heading>
                    <form wire:submit="updateEmpreendimento" class="space-y-4">
                        <flux:input wire:model="name" :label="__('Name')" required />
                        <flux:textarea wire:model="description" :label="__('Description')" rows="3" />
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
                        <div class="flex items-center gap-4">
                            <flux:button type="submit" variant="primary">{{ __('Save Changes') }}</flux:button>
                        </div>
                    </form>
                </flux:card>

                {{-- ── Map Settings ────────────────────────────────── --}}
                <flux:card>
                    <flux:heading class="mb-4">{{ __('Map Settings') }}</flux:heading>

                    <div class="space-y-4">
                        {{-- Seletor de modo --}}
                        <flux:radio.group wire:model.live="mapType" :label="__('Lot visualization mode')">
                            <flux:radio value="map" :label="__('Geographic map (OpenStreetMap)')" />
                            <flux:radio value="image" :label="__('Custom development image')" />
                        </flux:radio.group>

                        @if ($mapType !== $empreendimento->map_type)
                            <div class="flex items-center gap-3">
                                <flux:button wire:click="saveMapType" variant="primary" size="sm">
                                    {{ __('Save mode') }}
                                </flux:button>
                            </div>
                        @endif

                        {{-- Upload de imagem (visível somente no modo image) --}}
                        @if ($mapType === 'image')
                            <flux:separator />

                            @if ($empreendimento->map_image)
                                <div class="space-y-3">
                                    <flux:text class="text-sm font-medium">{{ __('Current image') }}</flux:text>
                                    <div class="relative inline-block">
                                        <img
                                            src="{{ Storage::url($empreendimento->map_image) }}"
                                            alt="{{ __('Development map') }}"
                                            class="rounded-lg border border-zinc-200 dark:border-zinc-700 max-h-48 object-contain"
                                        />
                                    </div>
                                    <flux:text class="text-xs text-zinc-400">
                                        {{ $empreendimento->map_image_width }}×{{ $empreendimento->map_image_height }}px
                                    </flux:text>
                                    <flux:button
                                        wire:click="removeMapImage"
                                        wire:confirm="{{ __('Remove image and revert to geographic map?') }}"
                                        variant="danger"
                                        size="sm"
                                        icon="trash"
                                    >
                                        {{ __('Remove image') }}
                                    </flux:button>
                                </div>
                            @else
                                <div
                                    x-data="{ dragging: false }"
                                    @dragover.prevent="dragging = true"
                                    @dragleave.prevent="dragging = false"
                                    @drop.prevent="dragging = false; $refs.fileInput.files = $event.dataTransfer.files; $wire.set('mapImageUpload', $event.dataTransfer.files[0])"
                                    :class="dragging ? 'border-blue-400 bg-blue-50 dark:bg-blue-900/20' : 'border-zinc-300 dark:border-zinc-600'"
                                    class="border-2 border-dashed rounded-lg p-6 text-center transition-colors"
                                >
                                    <input
                                        type="file"
                                        x-ref="fileInput"
                                        wire:model="mapImageUpload"
                                        accept="image/jpeg,image/png,image/webp"
                                        class="hidden"
                                        id="map-image-input"
                                    />
                                    <flux:icon.photo class="w-8 h-8 mx-auto text-zinc-400 mb-2" />
                                    <flux:text class="text-sm">
                                        {{ __('Drag an image or') }}
                                        <label for="map-image-input" class="text-blue-500 hover:text-blue-600 cursor-pointer underline">
                                            {{ __('click to select') }}
                                        </label>
                                    </flux:text>
                                    <flux:text class="text-xs text-zinc-400 mt-1">JPG, PNG or WebP · max 10 MB</flux:text>
                                </div>

                                @if ($mapImageUpload)
                                    <div class="flex items-center gap-3 mt-2">
                                        <img
                                            src="{{ $mapImageUpload->temporaryUrl() }}"
                                            class="w-16 h-16 object-cover rounded border border-zinc-200"
                                            alt="preview"
                                        />
                                        <flux:text class="text-sm text-zinc-600 dark:text-zinc-300 flex-1 truncate">
                                            {{ $mapImageUpload->getClientOriginalName() }}
                                        </flux:text>
                                        <flux:button wire:click="uploadMapImage" variant="primary" size="sm" wire:loading.attr="disabled">
                                            <span wire:loading.remove wire:target="uploadMapImage">{{ __('Upload image') }}</span>
                                            <span wire:loading wire:target="uploadMapImage">{{ __('Uploading...') }}</span>
                                        </flux:button>
                                    </div>
                                @endif

                                @error('mapImageUpload')
                                    <flux:text class="text-sm text-red-500">{{ $message }}</flux:text>
                                @enderror
                            @endif
                        @endif
                    </div>
                </flux:card>
            </div>

            <div>
                <flux:card>
                    <flux:heading class="mb-3">{{ __('Summary') }}</flux:heading>
                    @php
                        $counts = $empreendimento->lots()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
                    @endphp
                    <div class="space-y-2">
                        @foreach (App\Enums\LotStatus::cases() as $lotStatus)
                            <div class="flex items-center justify-between">
                                <flux:badge :color="$lotStatus->color()" size="sm">{{ $lotStatus->label() }}</flux:badge>
                                <flux:text class="font-medium">{{ $counts[$lotStatus->value] ?? 0 }}</flux:text>
                            </div>
                        @endforeach
                        <flux:separator class="my-2" />
                        <div class="flex items-center justify-between">
                            <flux:text>{{ __('Total') }}</flux:text>                            <flux:text class="font-bold">{{ $empreendimento->lots()->count() }}</flux:text>
                        </div>
                    </div>
                </flux:card>
            </div>
        </div>
    </div>

    {{-- ── ABA: LOTES ──────────────────────────────────────────────── --}}
    <div x-show="tab === 'lotes'" x-cloak>
        <flux:card>
            <div class="flex items-center justify-between mb-4">
                <flux:heading>{{ __('Lots') }}</flux:heading>
                <flux:button size="sm" variant="ghost" icon="plus" wire:click="$set('showLinkModal', true)">
                    {{ __('Link Lot') }}
                </flux:button>
            </div>

            <flux:input
                wire:model.live.debounce.300ms="lotSearch"
                icon="magnifying-glass"
                :placeholder="__('Search lots...')"
                class="mb-3"
            />

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Code') }}</flux:table.column>
                    <flux:table.column>{{ __('Block') }}</flux:table.column>
                    <flux:table.column>{{ __('Area (m²)') }}</flux:table.column>
                    <flux:table.column>{{ __('Price') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column>{{ __('Geometry') }}</flux:table.column>
                    <flux:table.column />
                </flux:table.columns>
                <flux:table.rows>
                    @forelse (
                        $empreendimento->lots()
                            ->when($lotSearch, fn ($q) => $q->where('code', 'like', "%{$lotSearch}%")
                                ->orWhere('block', 'like', "%{$lotSearch}%"))
                            ->orderBy('code')
                            ->get()
                        as $lot
                    )
                        <flux:table.row :key="$lot->id">
                            <flux:table.cell>
                                <flux:link :href="route('lots.show', $lot)" wire:navigate class="font-mono">
                                    {{ $lot->code }}
                                </flux:link>
                            </flux:table.cell>
                            <flux:table.cell>{{ $lot->block ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $lot->area_sqm ? number_format($lot->area_sqm, 2) : '—' }}</flux:table.cell>
                            <flux:table.cell>R$ {{ number_format($lot->price, 2, ',', '.') }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge :color="$lot->status->color()" size="sm">{{ $lot->status->label() }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if ($lot->geometry)
                                    <flux:badge color="blue" size="sm">{{ __('Defined') }}</flux:badge>
                                @else
                                    <flux:text class="text-zinc-400 text-sm">—</flux:text>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex gap-1">
                                    @if ($lot->geometry)
                                        <flux:button
                                            size="sm"
                                            variant="ghost"
                                            icon="x-mark"
                                            wire:click="clearGeometry({{ $lot->id }})"
                                            wire:confirm="{{ __('Remove geometry from this lot?') }}"
                                        />
                                    @endif
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        icon="link-slash"
                                        wire:click="unlinkLot({{ $lot->id }})"
                                        wire:confirm="{{ __('Unlink this lot?') }}"
                                    />
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7" class="text-center py-6">
                                <flux:text>{{ __('No lots linked.') }}</flux:text>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    </div>

    {{-- ── ABA: MAPA ───────────────────────────────────────────────── --}}
    <div x-show="tab === 'mapa'" x-cloak>
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <flux:text class="text-sm text-zinc-500">
                    @if ($empreendimento->map_type === 'image')
                        {{ __('Draw polygons over the image to define lot boundaries.') }}
                    @else
                        {{ __('Draw polygons to define lot boundaries. Click a polygon to view its details.') }}
                    @endif
                </flux:text>
                @if ($empreendimento->map_type !== 'image')
                    <flux:button size="sm" variant="ghost" icon="map-pin" id="btn-save-center">
                        {{ __('Save map center') }}
                    </flux:button>
                @endif
            </div>

            @if ($empreendimento->map_type === 'image' && ! $empreendimento->map_image)
                <div class="flex flex-col items-center justify-center h-64 rounded-xl border-2 border-dashed border-zinc-300 dark:border-zinc-600 text-center p-8">
                    <flux:icon.photo class="w-10 h-10 text-zinc-400 mb-3" />
                    <flux:heading size="sm" class="mb-1">{{ __('No image configured') }}</flux:heading>
                    <flux:text class="text-sm text-zinc-500">
                        {{ __('Go to the') }}
                        <button type="button" class="text-blue-500 underline" @click="tab = 'dados'">{{ __('Data') }}</button>
                        {{ __('tab and upload the map image.') }}
                    </flux:text>
                </div>
            @else
                <div class="flex flex-wrap gap-3 text-sm">
                    @foreach (App\Enums\LotStatus::cases() as $lotStatus)
                        <div class="flex items-center gap-1.5">
                            <span class="inline-block w-3 h-3 rounded-sm" style="background: {{ match($lotStatus->color()) { 'green' => '#22c55e', 'yellow' => '#eab308', 'red' => '#ef4444', default => '#6b7280' } }}"></span>
                            <span class="text-zinc-600 dark:text-zinc-400">{{ $lotStatus->label() }}</span>
                        </div>
                    @endforeach
                </div>

                <div
                    id="lot-map"
                    wire:ignore
                    style="height: 750px; border-radius: 8px; overflow: hidden; z-index: 0;"
                    data-lat="{{ $empreendimento->map_lat ?? -15.7801 }}"
                    data-lng="{{ $empreendimento->map_lng ?? -47.9292 }}"
                    data-zoom="{{ $empreendimento->map_zoom ?? 4 }}"
                    data-geojson="{{ json_encode($this->lotsGeoJson) }}"
                    data-emp-id="{{ $empreendimento->id }}"
                    data-map-type="{{ $empreendimento->map_type ?? 'map' }}"
                    data-image-url="{{ $empreendimento->map_image ? Storage::url($empreendimento->map_image) : '' }}"
                    data-image-width="{{ $empreendimento->map_image_width ?? 0 }}"
                    data-image-height="{{ $empreendimento->map_image_height ?? 0 }}"
                ></div>
            @endif
        </div>
    </div>

    {{-- Modal: link existing lot --}}
    <flux:modal wire:model.self="showLinkModal" class="min-w-[24rem]">
        <form wire:submit="linkLot" class="space-y-4">
            <flux:heading size="lg">{{ __('Link Lot') }}</flux:heading>
            <flux:select wire:model="selectedLotId" :label="__('Select Lot')" required>
                <flux:select.option value="">{{ __('-- select --') }}</flux:select.option>
                @foreach ($this->availableLots as $availableLot)
                    <flux:select.option :value="$availableLot->id">
                        {{ $availableLot->code }}{{ $availableLot->block ? ' (Block '.$availableLot->block.')' : '' }}
                    </flux:select.option>
                @endforeach
            </flux:select>
            <div class="flex gap-2 justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Link') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Modal: lot details --}}
    <flux:modal wire:model.self="showLotDetailsModal" class="min-w-xl" flyout>
        @if (!empty($viewingLotProps))
            <div class="space-y-4">
                <div class="flex items-start justify-between gap-4">
                    <flux:heading size="lg">
                        {{ __('Lot') }} {{ $viewingLotProps['code'] }}
                        @if ($viewingLotProps['block'])
                            &mdash; {{ __('Block') }} {{ $viewingLotProps['block'] }}
                        @endif
                    </flux:heading>
                    <flux:badge :color="$viewingLotProps['color']" size="sm">{{ $viewingLotProps['label'] }}</flux:badge>
                </div>

                <flux:separator />

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <flux:text class="text-xs text-zinc-500 mb-0.5">{{ __('Price') }}</flux:text>
                        <flux:text class="font-medium">R$ {{ number_format($viewingLotProps['price'], 2, ',', '.') }}</flux:text>
                    </div>
                    @if ($viewingLotProps['area'])
                        <div>
                            <flux:text class="text-xs text-zinc-500 mb-0.5">{{ __('Area (m²)') }}</flux:text>
                            <flux:text class="font-medium">{{ number_format($viewingLotProps['area'], 2) }}</flux:text>
                        </div>
                    @endif
                </div>

                <flux:separator />

                <div class="flex items-center justify-between gap-2">
                    <flux:button
                        variant="danger"
                        size="sm"
                        icon="trash"
                        wire:click="clearGeometry({{ $viewingLotProps['id'] }})"
                        wire:confirm="{{ __('Remove geometry from this lot?') }}"
                    >
                        {{ __('Remove geometry') }}
                    </flux:button>
                    <div class="flex gap-2">
                        <flux:modal.close>
                            <flux:button variant="ghost">{{ __('Close') }}</flux:button>
                        </flux:modal.close>
                        <flux:button
                            :href="route('lots.show', $viewingLotProps['id'])"
                            wire:navigate
                            variant="primary"
                            icon="arrow-top-right-on-square"
                        >
                            {{ __('View details') }}
                        </flux:button>
                    </div>
                </div>
            </div>
        @endif
    </flux:modal>

    {{-- Modal: assign drawn polygon --}}
    <flux:modal wire:model.self="showMapAssignModal" class="min-w-[28rem]">
        <form wire:submit="assignDrawnPolygon" class="space-y-4">
            <flux:heading size="lg">{{ __('Assign Polygon') }}</flux:heading>

            <flux:radio.group wire:model.live="assignMode" :label="__('What do you want to do with this polygon?')">
                <flux:radio value="link" :label="__('Link to existing lot')" />
                <flux:radio value="create" :label="__('Create new lot')" />
            </flux:radio.group>

            @if ($assignMode === 'link')
                <flux:select wire:model="assignLotId" :label="__('Lot')" required>
                    <flux:select.option value="">{{ __('-- select --') }}</flux:select.option>
                    @foreach ($empreendimento->lots()->whereNull('geometry')->orderBy('code')->get() as $lot)
                        <flux:select.option :value="$lot->id">
                            {{ $lot->code }}{{ $lot->block ? ' (Block '.$lot->block.')' : '' }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            @else
                <div class="grid grid-cols-2 gap-3">
                    <flux:input wire:model="newCode" :label="__('Code')" required />
                    <flux:input wire:model="newBlock" :label="__('Block')" />
                </div>
                <flux:input wire:model="newPrice" :label="__('Price (R$)')" type="number" step="0.01" min="0" required />
                <flux:select wire:model="newStatus" :label="__('Status')">
                    @foreach (App\Enums\LotStatus::cases() as $lotStatus)
                        <flux:select.option :value="$lotStatus->value">{{ $lotStatus->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
            @endif

            <div class="flex gap-2 justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>

@script
<script>
(function () {
    // ── Colour helpers ────────────────────────────────────────────────────
    const STATUS_COLORS = {
        available: '#22c55e',
        reserved:  '#eab308',
        sold:      '#ef4444',
    };

    function lotStyle(status) {
        const color = STATUS_COLORS[status] ?? '#6b7280';
        return { color, weight: 2, fillColor: color, fillOpacity: 0.5 };
    }

    // ── Map init ──────────────────────────────────────────────────────────
    const mapEl = document.getElementById('lot-map');
    if (!mapEl) return;

    const mapType   = mapEl.dataset.mapType ?? 'map';
    const imageUrl  = mapEl.dataset.imageUrl ?? '';
    const imgW      = parseInt(mapEl.dataset.imageWidth) || 1000;
    const imgH      = parseInt(mapEl.dataset.imageHeight) || 800;

    let map;
    let imageBounds = null;

    function fitImageMapToViewport() {
        if (mapType !== 'image' || !imageBounds) {
            return;
        }

        const containerH = mapEl.offsetHeight;
        const containerW = mapEl.offsetWidth;

        if (!containerH || !containerW) {
            return;
        }

        map.invalidateSize();
        map.fitBounds(imageBounds, {
            animate: false,
            padding: [24, 24],
        });
    }

    if (mapType === 'image' && imageUrl) {
        // ── Modo imagem: Leaflet com CRS.Simple ───────────────────────
        imageBounds = [[0, 0], [imgH, imgW]];

        map = L.map('lot-map', {
            crs: L.CRS.Simple,
            minZoom: 0,
            maxZoom: 8,
            maxBounds: imageBounds,
            maxBoundsViscosity: 1.0,
        });

        L.imageOverlay(imageUrl, imageBounds).addTo(map);
        requestAnimationFrame(() => fitImageMapToViewport());
    } else {
        // ── Geographic map mode: OSM tiles ───────────────────────────
        const lat  = parseFloat(mapEl.dataset.lat);
        const lng  = parseFloat(mapEl.dataset.lng);
        const zoom = parseInt(mapEl.dataset.zoom);

        map = L.map('lot-map').setView([lat, lng], zoom);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 20,
        }).addTo(map);
    }

    // ── Layer storage: lotId → polygon layer / label marker ───────────────
    const lotLayers   = {};
    const labelLayers = {};

    // Returns [lat, lng] centroid of a Polygon or Rectangle geometry
    function calcCentroid(geometry) {
        let coords = null;
        if (geometry.type === 'Polygon') {
            coords = geometry.coordinates[0];
        } else if (geometry.type === 'MultiPolygon') {
            coords = geometry.coordinates[0][0];
        }
        if (!coords || coords.length === 0) { return null; }
        let sumLng = 0, sumLat = 0;
        for (const [lng, lat] of coords) { sumLng += lng; sumLat += lat; }
        return [sumLat / coords.length, sumLng / coords.length];
    }

    // Place a badge label at the centroid of the feature's geometry
    function addLotLabel(feature) {
        const props    = feature.properties;
        const centroid = calcCentroid(feature.geometry);
        if (!centroid) { return; }

        const color = STATUS_COLORS[props.status] ?? '#6b7280';
        const icon  = L.divIcon({
            className: '',
            html: `<div style="background:${color};color:#fff;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700;white-space:nowrap;box-shadow:0 1px 4px rgba(0,0,0,.4);pointer-events:none;line-height:1.6">${props.code}</div>`,
            iconSize:   null,
            iconAnchor: null,
        });

        const marker = L.marker(centroid, { icon, interactive: false, keyboard: false }).addTo(map);
        labelLayers[props.id] = marker;
    }

    function addLotLayer(feature) {
        const props = feature.properties;
        const layer = L.geoJSON(feature, {
            style: lotStyle(props.status),
        }).addTo(map);
        layer.on('click', () => $wire.call('openLotDetailsModal', props.id));
        layer._lotId = props.id;
        lotLayers[props.id] = layer;
        addLotLabel(feature);
        return layer;
    }

    // ── Load initial GeoJSON ──────────────────────────────────────────────
    let geojsonData;
    try { geojsonData = JSON.parse(mapEl.dataset.geojson); } catch { geojsonData = { type: 'FeatureCollection', features: [] }; }
    (geojsonData.features || []).forEach(addLotLayer);

    // ── Leaflet.draw ──────────────────────────────────────────────────────
    const drawnItems = new L.FeatureGroup().addTo(map);

    const drawControl = new L.Control.Draw({
        draw: {
            polygon:      true,
            polyline:     false,
            rectangle:    true,
            circle:       false,
            marker:       false,
            circlemarker: false,
        },
        edit: {
            featureGroup: drawnItems,
            remove: false,
        },
    });
    map.addControl(drawControl);

    // ── Event: polygon drawn ──────────────────────────────────────────────
    map.on(L.Draw.Event.CREATED, function (e) {
        const layer = e.layer;
        drawnItems.addLayer(layer);
        const geojson = JSON.stringify(layer.toGeoJSON().geometry);
        $wire.set('pendingGeometry', geojson);
        $wire.set('showMapAssignModal', true);
    });

    // ── Event: polygons edited ────────────────────────────────────────────
    map.on(L.Draw.Event.EDITED, function (e) {
        e.layers.eachLayer(function (layer) {
            if (layer._lotId) {
                const geojson = JSON.stringify(layer.toGeoJSON().geometry);
                $wire.call('saveGeometry', layer._lotId, geojson);
            }
        });
    });

    // ── Livewire events ───────────────────────────────────────────────────
    $wire.on('geometry-saved', ({ lotId }) => {
        if (lotLayers[lotId])   map.removeLayer(lotLayers[lotId]);
        if (labelLayers[lotId]) map.removeLayer(labelLayers[lotId]);
        delete lotLayers[lotId];
        delete labelLayers[lotId];
    });

    $wire.on('geometry-cleared', ({ lotId }) => {
        if (lotLayers[lotId])   map.removeLayer(lotLayers[lotId]);
        if (labelLayers[lotId]) map.removeLayer(labelLayers[lotId]);
        delete lotLayers[lotId];
        delete labelLayers[lotId];
        $wire.set('showLotDetailsModal', false);
    });

    $wire.on('lots-updated', () => {
        setTimeout(() => {
            $wire.call('getLotsGeoJsonProperty').then(data => {
                if (!data || !data.features) return;
                Object.values(lotLayers).forEach(l => map.removeLayer(l));
                Object.values(labelLayers).forEach(l => map.removeLayer(l));
                Object.keys(lotLayers).forEach(k => delete lotLayers[k]);
                Object.keys(labelLayers).forEach(k => delete labelLayers[k]);
                data.features.forEach(addLotLayer);
            }).catch(() => {});
        }, 300);
    });

    // ── Save map centre ───────────────────────────────────────────────────
    document.getElementById('btn-save-center')?.addEventListener('click', () => {
        const c = map.getCenter();
        $wire.call('saveMapCenter', c.lat, c.lng, map.getZoom());
    });

    // ── Fix map size when map tab is activated ────────────────────────────
    window.addEventListener('map-tab-activated', () => {
        setTimeout(() => {
            map.invalidateSize();

            if (mapType === 'image') {
                fitImageMapToViewport();
            }
        }, 50);
    });

    window.addEventListener('resize', () => {
        if (mapType === 'image') {
            fitImageMapToViewport();
        }
    });
})();
</script>
@endscript