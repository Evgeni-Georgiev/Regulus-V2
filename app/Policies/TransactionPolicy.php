<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TransactionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Users can view their own transactions
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Transaction $transaction): bool
    {
        // Users can only view transactions that belong to portfolios they own
        return $user->id === $transaction->portfolio->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Authenticated users can create transactions (but portfolio ownership is checked separately)
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Transaction $transaction): bool
    {
        // Users can only update transactions that belong to portfolios they own
        return $user->id === $transaction->portfolio->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Transaction $transaction): bool
    {
        // Users can only delete transactions that belong to portfolios they own
        return $user->id === $transaction->portfolio->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Transaction $transaction): bool
    {
        // Users can only restore transactions that belong to portfolios they own
        return $user->id === $transaction->portfolio->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Transaction $transaction): bool
    {
        // Users can only force delete transactions that belong to portfolios they own
        return $user->id === $transaction->portfolio->user_id;
    }
}
