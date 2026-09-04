<?php

namespace App\Models;

use App\Enums\MovementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Movement extends Model
{
    protected $fillable = [
        'account_id',
        'type',
        'amount',
        'instrument',
        'quantity',
        'price',
    ];

    protected $casts = [
        'type' => MovementType::class,
        'amount' => 'decimal:2',
        'price' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}