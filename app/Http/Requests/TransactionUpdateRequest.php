<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransactionUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'portfolio_id' => ['required', 'integer', 'exists:Portfolios,id'],
            'coin_id' => ['required', 'integer', 'exists:,id'],
            'quantity' => ['required', 'numeric', 'between:-9999999999.99999999,9999999999.99999999'],
            'buy_price' => ['required', 'numeric', 'between:-9999999999.99999999,9999999999.99999999'],
            'transaction_type' => ['required', 'in:buy,sell'],
        ];
    }
}
