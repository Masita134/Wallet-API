<?php

namespace App\Models;

use Database\Factories\MovementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'account_id',
    'type',
    'amount',
    'counterparty_cbu',
])]
class Movement extends Model
{
    /** @use HasFactory<MovementFactory> */
    use HasFactory;

    public const TYPE_DEPOSIT = 'deposit';

    public const TYPE_TRANSFER_OUT = 'transfer_out';

    public const TYPE_TRANSFER_IN = 'transfer_in';

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    /**
     * Get the account affected by the movement.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}