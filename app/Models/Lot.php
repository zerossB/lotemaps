<?php

namespace App\Models;

use App\Enums\LotStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Lot extends Model
{
    /** @use HasFactory<\Database\Factories\LotFactory> */
    use HasFactory;

    protected $fillable = [
        'empreendimento_id',
        'code',
        'block',
        'description',
        'area_sqm',
        'price',
        'status',
        'geometry',
    ];

    protected function casts(): array
    {
        return [
            'status' => LotStatus::class,
            'area_sqm' => 'decimal:2',
            'price' => 'decimal:2',
            'geometry' => 'array',
        ];
    }

    /** @return BelongsTo<Empreendimento, $this> */
    public function empreendimento(): BelongsTo
    {
        return $this->belongsTo(Empreendimento::class);
    }

    /** @return BelongsToMany<Proposal, $this> */
    public function proposals(): BelongsToMany
    {
        return $this->belongsToMany(Proposal::class, 'proposal_lot')
            ->withPivot('price');
    }

    public function isAvailable(): bool
    {
        return $this->status === LotStatus::Available;
    }
}
