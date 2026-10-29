<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Collection;

class PaymentService
{
    public function listAll(): Collection
    {
        return Payment::all();
    }

    public function create(array $data): Payment
    {
        return Payment::create($data);
    }

    public function findById(int $id): Payment
    {
        return Payment::findOrFail($id);
    }

    public function update(int $id, array $data): Payment
    {
        $Payment = Payment::findOrFail($id);
        $payment->update($data);

        return $payment;
    }

    public function delete(int $id): void
    {
        Payment::findOrFail($id)->delete();
    }
}