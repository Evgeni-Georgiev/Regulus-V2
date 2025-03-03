<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Portfolio extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'name',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * A Portfolio can have many Coins(Assets) to track statistics on.
     * Define the many-to-many relationship with coins.
     *
     * @return BelongsToMany
     */
    public function coins(): BelongsToMany
    {
        return $this->BelongsToMany(Coin::class, 'portfolio_coins')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /**
     * A Portfolio van have many Snapshots.
     *
     * @return HasMany
     */
    public function snapshots(): HasMany
    {
        return $this->hasMany(PortfolioSnapshot::class);
    }
}
