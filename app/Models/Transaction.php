<?php

namespace App\Models;

use App\Enums\TransactionTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'portfolio_id',
        'coin_id',
        'quantity',
        'buy_price',
        'transaction_type',
        'exchange_source',
        'exchange_transaction_id',
        'synced_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'portfolio_id' => 'integer',
        'coin_id' => 'integer',
        'quantity' => 'float',
        'buy_price' => 'float',
        'transaction_type' => TransactionTypeEnum::class,
        'synced_at' => 'datetime',
    ];

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function coin(): BelongsTo
    {
        return $this->belongsTo(Coin::class);
    }
}
