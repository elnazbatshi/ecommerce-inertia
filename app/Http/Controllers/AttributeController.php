<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttributeRequest;
use App\Models\Attribute;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AttributeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Attributes/Index', [
            'attributes' => Attribute::query()->with('values')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreAttributeRequest $request): RedirectResponse
    {
        $attribute = Attribute::create($this->payload($request->validated()));
        $this->syncValues($attribute, $request->validated('values', []));

        return back()->with('success', 'ویژگی ایجاد شد.');
    }

    public function update(StoreAttributeRequest $request, Attribute $attribute): RedirectResponse
    {
        $attribute->update($this->payload($request->validated()));
        $this->syncValues($attribute, $request->validated('values', []));

        return back()->with('success', 'ویژگی ویرایش شد.');
    }

    public function destroy(Attribute $attribute): RedirectResponse
    {
        $attribute->delete();

        return back()->with('success', 'ویژگی حذف شد.');
    }

    private function payload(array $data): array
    {
        return [
            'name' => $data['name'],
            'slug' => $data['slug'] ?? null,
            'type' => $data['type'] ?? null,
        ];
    }

    private function syncValues(Attribute $attribute, array $values): void
    {
        $keptIds = [];

        foreach ($values as $value) {
            $record = $attribute->values()->updateOrCreate(
                ['id' => $value['id'] ?? null],
                [
                    'value' => $value['value'],
                    'slug' => filled($value['slug'] ?? null)
                        ? app(\App\Services\SlugService::class)->make($value['slug'])
                        : app(\App\Services\SlugService::class)->make($value['value']),
                ]
            );
            $keptIds[] = $record->id;
        }

        $attribute->values()->whereNotIn('id', $keptIds)->delete();
    }
}
