<?php

namespace App\Models;

use App\Enums\EmpreendimentoStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empreendimento extends Model
{
    /** @use HasFactory<\Database\Factories\EmpreendimentoFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'address',
        'city',
        'state',
        'total_area',
        'status',
        'map_lat',
        'map_lng',
        'map_zoom',
        'map_type',
        'map_image',
        'map_image_width',
        'map_image_height',
    ];

    protected function casts(): array
    {
        return [
            'status'           => EmpreendimentoStatus::class,
            'total_area'       => 'decimal:2',
            'map_lat'          => 'float',
            'map_lng'          => 'float',
            'map_zoom'         => 'integer',
            'map_image_width'  => 'integer',
            'map_image_height' => 'integer',
        ];
    }

    /** @return HasMany<Lot, $this> */
    public function lots(): HasMany
    {
        return $this->hasMany(Lot::class);
    }
}
