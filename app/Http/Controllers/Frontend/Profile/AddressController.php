<?php

namespace App\Http\Controllers\Frontend\Profile;

use App\Http\Controllers\Controller;
use App\Http\Services\AddressService;
use App\Models\Address;
use App\Models\City;
use App\Models\Customer;
use App\Models\Province;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AddressController extends Controller
{
    public function __construct(private readonly AddressService $addresses)
    {
    }

    public function index(Request $request): Response|RedirectResponse
    {
        $customer = $this->customerFromSession($request);

        if (! $customer) {
            return redirect('/')
                ->with('message', 'برای مشاهده آدرس‌ها ابتدا وارد حساب کاربری شوید.');
        }

        $addresses = $customer->addresses()
            ->orderByDesc('is_default')
            ->latest()
            ->get()
            ->map(fn (Address $address) => $this->formatAddress($address))
            ->values();

        $provinces = Province::query()
            ->active()
            ->ordered()
            ->with(['cities' => fn ($query) => $query->active()->ordered()])
            ->get()
            ->map(fn (Province $province) => [
                'id' => $province->id,
                'name' => $province->name,
                'cities' => $province->cities
                    ->map(fn (City $city) => [
                        'id' => $city->id,
                        'name' => $city->name,
                    ])
                    ->values(),
            ])
            ->values();

        return Inertia::render('Frontend/Profile/Addresses/Index', [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
            ],
            'addresses' => $addresses,
            'provinces' => $provinces,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $customer = $this->customerFromSession($request);
        abort_if(! $customer, 403);

        $this->addresses->create($customer, $this->validatedAddress($request));

        return back()->with('success', 'آدرس با موفقیت ثبت شد.');
    }

    public function update(Request $request, Address $address): RedirectResponse
    {
        $customer = $this->customerFromSession($request);
        abort_if(! $customer, 403);

        $this->addresses->update($customer, $address, $this->validatedAddress($request));

        return back()->with('success', 'آدرس با موفقیت ویرایش شد.');
    }

    public function destroy(Request $request, Address $address): RedirectResponse
    {
        $customer = $this->customerFromSession($request);
        abort_if(! $customer, 403);

        $this->addresses->delete($customer, $address);

        return back()->with('success', 'آدرس حذف شد.');
    }

    public function setDefault(Request $request, Address $address): RedirectResponse
    {
        $customer = $this->customerFromSession($request);
        abort_if(! $customer, 403);

        $this->addresses->setDefault($customer, $address);

        return back()->with('success', 'آدرس پیش‌فرض تغییر کرد.');
    }

    private function customerFromSession(Request $request): ?Customer
    {
        $customerId = $request->session()->get('customer_id');

        if (! $customerId) {
            return null;
        }

        return Customer::query()
            ->select(['id', 'name', 'phone'])
            ->find($customerId);
    }

    private function validatedAddress(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'receiver_name' => ['required', 'string', 'max:255'],
            'receiver_phone' => ['required', 'string', 'max:32'],
            'province_id' => ['required', 'integer', 'exists:provinces,id'],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'address' => ['required', 'string'],
            'plaque' => ['nullable', 'string', 'max:32'],
            'unit' => ['nullable', 'string', 'max:32'],
            'is_default' => ['boolean'],
        ]);

        $cityBelongsToProvince = City::query()
            ->whereKey($validated['city_id'])
            ->where('province_id', $validated['province_id'])
            ->exists();

        if (! $cityBelongsToProvince) {
            abort(422, 'شهر انتخاب‌شده متعلق به استان انتخاب‌شده نیست.');
        }

        return $validated;
    }

    private function formatAddress(Address $address): array
    {
        return [
            'id' => $address->id,
            'title' => $address->title,
            'receiver_name' => $address->receiver_name,
            'receiver_phone' => $address->receiver_phone,
            'province_id' => $address->province_id,
            'city_id' => $address->city_id,
            'province' => $address->getAttribute('province'),
            'city' => $address->getAttribute('city'),
            'postal_code' => $address->postal_code,
            'address' => $address->address,
            'plaque' => $address->plaque,
            'unit' => $address->unit,
            'is_default' => $address->is_default,
        ];
    }
}
