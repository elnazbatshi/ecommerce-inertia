<?php

namespace App\Http\Services;

use App\Models\Address;
use App\Models\City;
use App\Models\Customer;
use App\Models\Province;
use Illuminate\Support\Facades\DB;

class AddressService
{
    public function create(Customer $customer, array $data): Address
    {
        $data = $this->hydrateLegacyLocationFields($data);

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
        $data = $this->hydrateLegacyLocationFields($data);

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

    private function hydrateLegacyLocationFields(array $data): array
    {
        if (! empty($data['province_id'])) {
            $province = Province::query()->find($data['province_id']);
            if ($province) {
                $data['province'] = $province->name;
            }
        }

        if (! empty($data['city_id'])) {
            $city = City::query()->find($data['city_id']);
            if ($city) {
                $data['city'] = $city->name;
            }
        }

        return $data;
    }
}
