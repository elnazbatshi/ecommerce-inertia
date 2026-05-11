<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBrandRequest;
use App\Http\Services\BrandService;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BrandController extends Controller
{
    public function __construct(private readonly BrandService $brands)
    {
    }

    public function index(): Response
    {
        return Inertia::render('Brands/Index', [
            'brands' => $this->brands->all(),
        ]);
    }

    public function store(StoreBrandRequest $request): RedirectResponse
    {
        $this->brands->create($request);

        return back()->with('success', 'برند ایجاد شد.');
    }

    public function update(StoreBrandRequest $request, Brand $brand): RedirectResponse
    {
        $this->brands->update($request, $brand);

        return back()->with('success', 'برند ویرایش شد.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $this->brands->delete($brand);

        return back()->with('success', 'برند حذف شد.');
    }
}
