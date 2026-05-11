<?php

namespace App\Http\Controllers;

use App\Http\Services\AddressService;
use App\Models\Address;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function __construct(private readonly AddressService $addresses)
    {
    }

    public function store(Request $request, Customer $customer): RedirectResponse
    {
        $this->addresses->create($customer, $this->validatedAddress($request));

        return back()->with('success', 'Address created successfully.');
    }

    public function update(Request $request, Customer $customer, Address $address): RedirectResponse
    {
        $this->addresses->update($customer, $address, $this->validatedAddress($request));

        return back()->with('success', 'Address updated successfully.');
    }

    public function destroy(Customer $customer, Address $address): RedirectResponse
    {
        $this->addresses->delete($customer, $address);

        return back()->with('success', 'Address deleted successfully.');
    }

    public function setDefault(Customer $customer, Address $address): RedirectResponse
    {
        $this->addresses->setDefault($customer, $address);

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

}
