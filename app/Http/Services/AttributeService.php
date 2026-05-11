<?php

namespace App\Http\Services;

use App\Models\Attribute;

class AttributeService
{
    public function __construct(private readonly SlugService $slugs)
    {
    }

    public function create(array $data): Attribute
    {
        $attribute = Attribute::create($this->payload($data));
        $this->syncValues($attribute, $data['values'] ?? []);

        return $attribute;
    }

    public function update(Attribute $attribute, array $data): Attribute
    {
        $attribute->update($this->payload($data));
        $this->syncValues($attribute, $data['values'] ?? []);

        return $attribute->refresh();
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
                    'slug' => $this->slugs->make(filled($value['slug'] ?? null) ? $value['slug'] : $value['value']),
                ]
            );
            $keptIds[] = $record->id;
        }

        $attribute->values()->whereNotIn('id', $keptIds)->delete();
    }
}
