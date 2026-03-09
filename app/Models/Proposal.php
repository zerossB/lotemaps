<?php

namespace App\Models;

use App\Enums\ProposalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Proposal extends Model
{
    /** @use HasFactory<\Database\Factories\ProposalFactory> */
    use HasFactory;

    protected $fillable = [
        'client_id',
        'user_id',
        'status',
        'total_price',
        'notes',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProposalStatus::class,
            'total_price' => 'decimal:2',
            'expires_at' => 'date',
        ];
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsToMany<Lot, $this> */
    public function lots(): BelongsToMany
    {
        return $this->belongsToMany(Lot::class, 'proposal_lot')
            ->withPivot('price');
    }

    public function recalculateTotalPrice(): void
    {
        $this->total_price = $this->lots()->sum('proposal_lot.price');
        $this->save();
    }
}
