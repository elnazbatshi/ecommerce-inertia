<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttributeRequest;
use App\Http\Services\AttributeService;
use App\Http\Services\CatalogService;
use App\Models\Attribute;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AttributeController extends Controller
{
    public function __construct(
        private readonly AttributeService $attributes,
        private readonly CatalogService $catalog,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('Attributes/Index', [
            'attributes' => $this->catalog->attributes(),
        ]);
    }

    public function store(StoreAttributeRequest $request): RedirectResponse
    {
        $this->attributes->create($request->validated());

        return back()->with('success', 'ویژگی ایجاد شد.');
    }

    public function update(StoreAttributeRequest $request, Attribute $attribute): RedirectResponse
    {
        $this->attributes->update($attribute, $request->validated());

        return back()->with('success', 'ویژگی ویرایش شد.');
    }

    public function destroy(Attribute $attribute): RedirectResponse
    {
        $attribute->delete();

        return back()->with('success', 'ویژگی حذف شد.');
    }

}
