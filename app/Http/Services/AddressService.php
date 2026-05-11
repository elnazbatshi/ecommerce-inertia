<?php

namespace App\Http\Services;

use App\Models\Address;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class AddressService
{
    public function create(Customer $customer, array $data): Address
    {
        return DB::transaction(function () use ($customer, $data) {
            if ($data['is_default'] ?? false) {
                $customer->addresses()->update(['is_default' => false]);
            }

            return $customer->addresses()->create($data);
        });
    }

    public function update(Customer $customer, Address $address, array $data): Address
    {
        $this->ensureBelongsToCustomer($customer, $address);

        return DB::transaction(function () use ($customer, $address, $data) {
            if ($data['is_default'] ?? false) {
                $customer->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
            }

            $address->update($data);

            return $address->refresh();
        });
    }

    public function delete(Customer $customer, Address $address): void
    {
        $this->ensureBelongsToCustomer($customer, $address);
        $address->delete();
    }

    public function setDefault(Customer $customer, Address $address): void
    {
        $this->ensureBelongsToCustomer($customer, $address);

        DB::transaction(function () use ($customer, $address) {
            $customer->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        });
    }

    private function ensureBelongsToCustomer(Customer $customer, Address $address): void
    {
        abort_if($address->customer_id !== $customer->id, 404);
    }
}
