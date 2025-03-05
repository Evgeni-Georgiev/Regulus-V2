<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'portfolio_id' => $this->portfolio_id,
            'coin_id' => $this->coin_id,
            'quantity' => $this->quantity,
            'buy_price' => $this->buy_price,
            'transaction_type' => $this->transaction_type,
            'coin' => CoinResource::make($this->whenLoaded('coin')),
            'created_at' => $this->created_at ? $this->created_at->toISOString() : null,
        ];
    }
}
