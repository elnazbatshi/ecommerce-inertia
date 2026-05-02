<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $perPage = max(1, min((int) $request->integer('rows', 10), 100));

        $customers = Customer::query()
            ->withCount('addresses')
            ->when($request->string('search')->toString(), function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->input('status'), fn ($query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Customer $customer) => $this->formatCustomer($customer));

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'filters' => $request->only(['search', 'status', 'rows']),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('customers.index');
    }

    public function store(Request $request): RedirectResponse
    {
        Customer::create($this->validatedCustomer($request));

        return back()->with('success', 'Customer created successfully.');
    }

    public function show(Customer $customer): RedirectResponse
    {
        return redirect()->route('customers.edit', $customer);
    }

    public function edit(Customer $customer): Response
    {
        $customer->load(['addresses' => fn ($query) => $query->latest('is_default')->latest()]);
        $customer->loadCount('addresses');

        return Inertia::render('Customers/Edit', [
            'customer' => [
                ...$this->formatCustomer($customer),
                'addresses' => $customer->addresses->map(fn ($address) => $this->formatAddress($address))->values(),
            ],
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $customer->update($this->validatedCustomer($request, $customer));

        return back()->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
    }

    private function validatedCustomer(Request $request, ?Customer $customer = null): array
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:32', Rule::unique('customers', 'phone')->ignore($customer?->id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($customer?->id)],
            'password' => [$customer ? 'nullable' : 'nullable', 'string', 'min:8'],
            'status' => ['required', Rule::in(Customer::STATUSES)],
        ]);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        return $data;
    }

    private function statusOptions(): array
    {
        return [
            ['label' => 'Active', 'value' => 'active', 'severity' => 'success'],
            ['label' => 'Inactive', 'value' => 'inactive', 'severity' => 'warn'],
            ['label' => 'Blocked', 'value' => 'blocked', 'severity' => 'danger'],
        ];
    }

    private function formatCustomer(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'status' => $customer->status,
            'addresses_count' => $customer->addresses_count ?? 0,
            'last_login_at' => $customer->last_login_at?->toDateTimeString(),
            'created_at' => $customer->created_at?->toDateTimeString(),
        ];
    }

    private function formatAddress($address): array
    {
        return [
            'id' => $address->id,
            'title' => $address->title,
            'receiver_name' => $address->receiver_name,
            'receiver_phone' => $address->receiver_phone,
            'province' => $address->province,
            'city' => $address->city,
            'postal_code' => $address->postal_code,
            'address' => $address->address,
            'plaque' => $address->plaque,
            'unit' => $address->unit,
            'latitude' => $address->latitude,
            'longitude' => $address->longitude,
            'is_default' => $address->is_default,
            'created_at' => $address->created_at?->toDateTimeString(),
        ];
    }
}
