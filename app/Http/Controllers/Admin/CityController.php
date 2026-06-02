<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCityRequest;
use App\Http\Requests\UpdateCityRequest;
use App\Http\Resources\CityResource;
use App\Models\City;
use App\Services\CityService;
use App\Services\ProvinceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CityController extends Controller
{
    public function __construct(
        private readonly CityService $cityService,
        private readonly ProvinceService $provinceService,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'province_id', 'is_active', 'rows']);
        $rows = (int) ($filters['rows'] ?? 15);

        return Inertia::render('Cities/Index', [
            'cities' => $this->cityService->paginate($filters, $rows)
                ->through(fn (City $city) => CityResource::make($city)->resolve()),
            'filters' => $filters,
            'provinceOptions' => $this->provinceService->options(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Cities/Create', [
            'provinceOptions' => $this->provinceService->options(),
        ]);
    }

    public function store(StoreCityRequest $request): RedirectResponse
    {
        $this->cityService->create($request->validated());

        return redirect()->route('admin.cities.index')->with('success', 'شهر با موفقیت ایجاد شد.');
    }

    public function edit(City $city): Response
    {
        $city->load('province:id,name,slug');

        return Inertia::render('Cities/Edit', [
            'city' => CityResource::make($city),
            'provinceOptions' => $this->provinceService->options(),
        ]);
    }

    public function update(UpdateCityRequest $request, City $city): RedirectResponse
    {
        $this->cityService->update($city, $request->validated());

        return redirect()->route('admin.cities.index')->with('success', 'شهر با موفقیت ویرایش شد.');
    }

    public function destroy(City $city): RedirectResponse
    {
        $this->cityService->delete($city);

        return back()->with('success', 'شهر حذف شد.');
    }

    public function toggleStatus(City $city): RedirectResponse
    {
        $this->cityService->toggleStatus($city);

        return back()->with('success', 'وضعیت شهر به‌روزرسانی شد.');
    }

    public function options(Request $request): JsonResponse
    {
        $request->validate([
            'province_id' => ['nullable', 'integer', 'exists:provinces,id'],
            'q' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        return response()->json($this->cityService->options($request->only(['province_id', 'q', 'limit'])));
    }
}
