<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{
    public function store(Request $request, Customer $customer): RedirectResponse
    {
        $data = $this->validatedAddress($request);

        DB::transaction(function () use ($customer, $data) {
            if ($data['is_default'] ?? false) {
                $customer->addresses()->update(['is_default' => false]);
            }

            $customer->addresses()->create($data);
        });

        return back()->with('success', 'Address created successfully.');
    }

    public function update(Request $request, Customer $customer, Address $address): RedirectResponse
    {
        $this->ensureAddressBelongsToCustomer($customer, $address);
        $data = $this->validatedAddress($request);

        DB::transaction(function () use ($customer, $address, $data) {
            if ($data['is_default'] ?? false) {
                $customer->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
            }

            $address->update($data);
        });

        return back()->with('success', 'Address updated successfully.');
    }

    public function destroy(Customer $customer, Address $address): RedirectResponse
    {
        $this->ensureAddressBelongsToCustomer($customer, $address);
        $address->delete();

        return back()->with('success', 'Address deleted successfully.');
    }

    public function setDefault(Customer $customer, Address $address): RedirectResponse
    {
        $this->ensureAddressBelongsToCustomer($customer, $address);

        DB::transaction(function () use ($customer, $address) {
            $customer->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        });

        return back()->with('success', 'Default address updated successfully.');
    }

    private function validatedAddress(Request $request): array
    {
        return $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'receiver_name' => ['required', 'string', 'max:255'],
            'receiver_phone' => ['required', 'string', 'max:32'],
            'province' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'address' => ['required', 'string'],
            'plaque' => ['nullable', 'string', 'max:32'],
            'unit' => ['nullable', 'string', 'max:32'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_default' => ['boolean'],
        ]);
    }

    private function ensureAddressBelongsToCustomer(Customer $customer, Address $address): void
    {
        abort_if($address->customer_id !== $customer->id, 404);
    }
}
