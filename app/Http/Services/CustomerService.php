<?php

namespace App\Http\Services;

use App\Models\Customer;
use App\Support\Pagination;
use Illuminate\Http\Request;

class CustomerService
{
    public function paginated(Request $request)
    {
        return Customer::query()
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
            ->paginate(Pagination::perPage($request))
            ->withQueryString()
            ->through(fn (Customer $customer) => $this->format($customer));
    }

    public function format(Customer $customer): array
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

    public function create(array $data): Customer
    {
        return Customer::create($this->payload($data));
    }

    public function update(Customer $customer, array $data): Customer
    {
        $customer->update($this->payload($data));

        return $customer->refresh();
    }

    private function payload(array $data): array
    {
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        return $data;
    }
}
