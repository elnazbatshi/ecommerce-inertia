<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $categories = $this->seedCategories();
        $this->seedBrands();
        $this->seedHeaderMenu($categories);
    }

    /**
     * @return array<string, Category>
     */
    private function seedCategories(): array
    {
        $groups = [
            'oil-lubricants' => [
                'name' => 'روغن موتور',
                'children' => [
                    ['name' => 'روغن 10W40', 'slug' => '10w40'],
                    ['name' => 'روغن 20W50', 'slug' => '20w50'],
                    ['name' => 'روغن اسکوتر', 'slug' => 'scooter-oil'],
                    ['name' => 'فیلتر روغن', 'slug' => 'oil-filter'],
                ],
            ],
            'main-parts' => [
                'name' => 'قطعات اصلی',
                'children' => [
                    ['name' => 'لنت ترمز', 'slug' => 'brake-pad'],
                    ['name' => 'باتری', 'slug' => 'battery'],
                    ['name' => 'شمع موتور', 'slug' => 'spark-plug'],
                    ['name' => 'زنجیر', 'slug' => 'chain'],
                    ['name' => 'فیلتر هوا', 'slug' => 'air-filter'],
                ],
            ],
            'accessories-parts' => [
                'name' => 'قطعات جانبی',
                'children' => [
                    ['name' => 'آینه', 'slug' => 'mirror'],
                    ['name' => 'راهنما', 'slug' => 'turn-signal'],
                    ['name' => 'قاب و فلاپ', 'slug' => 'body-panel'],
                    ['name' => 'چراغ', 'slug' => 'light'],
                    ['name' => 'دستگیره', 'slug' => 'handle-grip'],
                ],
            ],
            'equipment-tools' => [
                'name' => 'تجهیزات و لوازم',
                'children' => [
                    ['name' => 'کلاه کاسکت', 'slug' => 'helmet'],
                    ['name' => 'دستکش', 'slug' => 'gloves'],
                    ['name' => 'ابزار تعمیر', 'slug' => 'repair-tools'],
                    ['name' => 'قفل موتور', 'slug' => 'motorcycle-lock'],
                    ['name' => 'کاور موتور', 'slug' => 'motorcycle-cover'],
                ],
            ],
        ];

        $categories = [];

        foreach ($groups as $sort => $definition) {
            $parent = Category::query()->updateOrCreate(
                ['slug' => is_string($sort) ? $sort : $definition['slug']],
                [
                    'name' => $definition['name'],
                    'parent_id' => null,
                    'sort_order' => array_search($sort, array_keys($groups), true) + 1,
                    'is_active' => true,
                ],
            );

            $categories[$parent->slug] = $parent;

            foreach ($definition['children'] as $index => $child) {
                Category::query()->updateOrCreate(
                    ['slug' => $child['slug']],
                    [
                        'name' => $child['name'],
                        'parent_id' => $parent->id,
                        'sort_order' => $index + 1,
                        'is_active' => true,
                    ],
                );
            }
        }

        return $categories;
    }

    private function seedBrands(): void
    {
        foreach ([
            ['name' => 'Motul', 'slug' => 'motul'],
            ['name' => 'Castrol', 'slug' => 'castrol'],
            ['name' => 'NGK', 'slug' => 'ngk'],
            ['name' => 'Bosch', 'slug' => 'bosch'],
            ['name' => 'K&N', 'slug' => 'k-n'],
        ] as $brand) {
            Brand::query()->updateOrCreate(
                ['slug' => $brand['slug']],
                [
                    'name' => $brand['name'],
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * @param array<string, Category> $categories
     */
    private function seedHeaderMenu(array $categories): void
    {
        Menu::query()
            ->where('location', 'header')
            ->where('slug', '!=', 'header-menu')
            ->update(['is_active' => false]);

        $menu = Menu::query()->updateOrCreate(
            ['slug' => 'header-menu'],
            [
                'name' => 'منوی هدر',
                'location' => 'header',
                'description' => 'منوی اصلی سایت',
                'is_active' => true,
            ],
        );

        $this->upsertItem($menu, [
            'title' => 'صفحه اصلی',
            'type' => 'custom',
            'url' => '/',
            'sort_order' => 1,
        ]);

        $this->upsertCategoryItem($menu, $categories['oil-lubricants'] ?? null, 'روغن موتور', 2);
        $this->upsertCategoryItem($menu, $categories['main-parts'] ?? null, 'قطعات اصلی', 3);
        $this->upsertCategoryItem($menu, $categories['accessories-parts'] ?? null, 'قطعات جانبی', 4);
        $this->upsertCategoryItem($menu, $categories['equipment-tools'] ?? null, 'تجهیزات و لوازم', 5);

        $this->upsertItem($menu, [
            'title' => 'برندها',
            'type' => 'custom',
            'url' => '/brands',
            'sort_order' => 6,
            'auto_children' => true,
            'children_source' => 'brands',
        ]);

        $this->upsertItem($menu, [
            'title' => 'وبلاگ',
            'type' => 'custom',
            'url' => '/blog',
            'sort_order' => 7,
        ]);

        $contactPage = Page::query()->where('slug', 'contact-expert')->first();

        $this->upsertItem($menu, [
            'title' => 'تماس با ما',
            'type' => $contactPage ? 'page' : 'custom',
            'reference_id' => $contactPage?->id,
            'url' => $contactPage ? $contactPage->slug : '/page/contact-expert',
            'route_params' => $contactPage ? ['slug' => $contactPage->slug] : null,
            'sort_order' => 8,
        ]);
    }

    private function upsertCategoryItem(Menu $menu, ?Category $category, string $title, int $sortOrder): void
    {
        $slug = $category?->slug ?? str($title)->slug()->toString();

        $this->upsertItem($menu, [
            'title' => $title,
            'type' => $category ? 'category' : 'custom',
            'reference_id' => $category?->id,
            'url' => $category ? $slug : "/category/{$slug}",
            'route_params' => $category ? ['slug' => $slug] : null,
            'sort_order' => $sortOrder,
            'auto_children' => true,
            'children_source' => 'categories',
        ]);
    }

    private function upsertItem(Menu $menu, array $data): void
    {
        MenuItem::query()->updateOrCreate(
            [
                'menu_id' => $menu->id,
                'parent_id' => null,
                'title' => $data['title'],
            ],
            [
                'title_attribute' => $data['title_attribute'] ?? $data['title'],
                'type' => $data['type'],
                'reference_id' => $data['reference_id'] ?? null,
                'url' => $data['url'] ?? null,
                'route_name' => $data['route_name'] ?? null,
                'route_params' => $data['route_params'] ?? null,
                'target' => $data['target'] ?? '_self',
                'icon' => $data['icon'] ?? null,
                'css_class' => $data['css_class'] ?? null,
                'rel' => $data['rel'] ?? null,
                'depth' => 0,
                'sort_order' => $data['sort_order'],
                'is_active' => true,
                'auto_children' => $data['auto_children'] ?? false,
                'children_source' => $data['children_source'] ?? null,
            ],
        );
    }
}
