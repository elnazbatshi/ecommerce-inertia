<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FrontendCatalogController extends Controller
{
    public function menu(): JsonResponse
    {
        return response()->json([
            'data' => [
                'main_categories' => $this->mainCategories(),
                'popular_brands' => $this->popularBrands(),
                'popular_vehicles' => $this->popularVehicles(),
                'quick_links' => $this->quickLinks(),
            ],
        ]);
    }

    public function categories(): JsonResponse
    {
        return response()->json([
            'data' => $this->mainCategories(),
        ]);
    }

    public function brands(): JsonResponse
    {
        return response()->json([
            'data' => $this->popularBrands(),
        ]);
    }

    public function popularVehicles(): array|JsonResponse
    {
        if (!Schema::hasTable('vehicles')) {
            $vehicles = $this->fallbackVehicles();
        } else {
            $vehicles = Vehicle::query()
                ->with('brand:id,name')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->limit(8)
                ->get()
                ->map(fn (Vehicle $vehicle) => $this->mapVehicle($vehicle))
                ->values()
                ->all();

            if ($vehicles === []) {
                $vehicles = $this->fallbackVehicles();
            }
        }

        if (request()->routeIs('frontend.vehicles.popular')) {
            return response()->json(['data' => $vehicles]);
        }

        return $vehicles;
    }

    public function searchSuggestions(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if ($query === '') {
            return response()->json([
                'data' => [
                    'popular_terms' => ['روغن موتور', 'لنت ترمز', 'شمع موتور', 'فیلتر هوا', 'باتری'],
                    'products' => [],
                    'categories' => [],
                    'brands' => [],
                    'posts' => [],
                ],
            ]);
        }

        return response()->json([
            'data' => [
                'popular_terms' => [],
                'products' => Product::query()
                    ->where('status', 'active')
                    ->where('name', 'like', "%{$query}%")
                    ->limit(6)
                    ->get(['id', 'name', 'slug'])
                    ->map(fn (Product $product) => [
                        'id' => $product->id,
                        'title' => $product->name,
                        'slug' => $product->slug,
                        'url' => "/products/{$product->slug}",
                    ])
                    ->values(),
                'categories' => Category::query()
                    ->where('name', 'like', "%{$query}%")
                    ->when(Schema::hasColumn('categories', 'is_active'), fn ($builder) => $builder->where('is_active', true))
                    ->limit(6)
                    ->get(['id', 'name', 'slug'])
                    ->map(fn (Category $category) => [
                        'id' => $category->id,
                        'title' => $category->name,
                        'slug' => $category->slug,
                        'url' => "/category/{$category->slug}",
                    ])
                    ->values(),
                'brands' => Brand::query()
                    ->where('name', 'like', "%{$query}%")
                    ->when(Schema::hasColumn('brands', 'is_active'), fn ($builder) => $builder->where('is_active', true))
                    ->limit(6)
                    ->get(['id', 'name', 'slug'])
                    ->map(fn (Brand $brand) => [
                        'id' => $brand->id,
                        'title' => $brand->name,
                        'slug' => $brand->slug,
                        'url' => "/brand/{$brand->slug}",
                    ])
                    ->values(),
                'posts' => $this->searchPosts($query),
            ],
        ]);
    }

    private function mainCategories(): array
    {
        if (!Schema::hasTable('categories')) {
            return $this->fallbackCategories();
        }

        $categories = Category::query()
            ->with(['children' => fn ($query) => $query
                ->when(Schema::hasColumn('categories', 'is_active'), fn ($builder) => $builder->where('is_active', true))
                ->orderBy(Schema::hasColumn('categories', 'sort_order') ? 'sort_order' : 'name')
                ->orderBy('name')])
            ->whereNull('parent_id')
            ->when(Schema::hasColumn('categories', 'is_active'), fn ($query) => $query->where('is_active', true))
            ->orderBy(Schema::hasColumn('categories', 'sort_order') ? 'sort_order' : 'name')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category) => $this->mapCategory($category))
            ->values()
            ->all();

        return $categories === [] ? $this->fallbackCategories() : $categories;
    }

    private function popularBrands(): array
    {
        if (!Schema::hasTable('brands')) {
            return $this->fallbackBrands();
        }

        $brands = Brand::query()
            ->when(Schema::hasColumn('brands', 'is_active'), fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name', 'slug', 'logo'])
            ->map(fn (Brand $brand) => [
                'id' => $brand->id,
                'name' => $brand->name,
                'title' => $brand->name,
                'slug' => $brand->slug,
                'logo' => $brand->logo,
                'url' => "/brand/{$brand->slug}",
            ])
            ->values()
            ->all();

        return $brands === [] ? $this->fallbackBrands() : $brands;
    }

    private function mapCategory(Category $category): array
    {
        return [
            'id' => $category->id,
            'title' => $category->name,
            'name' => $category->name,
            'slug' => $category->slug,
            'icon' => $category->icon ?? null,
            'image' => $category->image ?? null,
            'url' => "/category/{$category->slug}",
            'children' => $category->children
                ->map(fn (Category $child) => [
                    'id' => $child->id,
                    'title' => $child->name,
                    'name' => $child->name,
                    'slug' => $child->slug,
                    'icon' => $child->icon ?? null,
                    'image' => $child->image ?? null,
                    'url' => "/category/{$child->slug}",
                    'children' => [],
                ])
                ->values()
                ->all(),
        ];
    }

    private function mapVehicle(Vehicle $vehicle): array
    {
        $title = trim(implode(' ', array_filter([
            $vehicle->brand?->name,
            $vehicle->name,
            $vehicle->trim,
        ])));

        return [
            'id' => $vehicle->id,
            'title' => $title,
            'type' => $vehicle->type,
            'brand' => $vehicle->brand?->name,
            'model' => $vehicle->name,
            'trim' => $vehicle->trim,
            'engine' => $vehicle->engine,
            'slug' => $vehicle->slug,
        ];
    }

    private function searchPosts(string $query): array
    {
        $table = Schema::hasTable('blog_posts') ? 'blog_posts' : (Schema::hasTable('posts') ? 'posts' : null);

        if ($table === null) {
            return [];
        }

        return DB::table($table)
            ->where('title', 'like', "%{$query}%")
            ->when(Schema::hasColumn($table, 'status'), fn ($builder) => $builder->whereIn('status', ['published', 'active']))
            ->limit(6)
            ->get(['id', 'title', 'slug'])
            ->map(fn ($post) => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'url' => "/blog/{$post->slug}",
            ])
            ->values()
            ->all();
    }

    private function quickLinks(): array
    {
        return [
            ['title' => 'راهنمای انتخاب روغن موتور', 'url' => '/blog?topic=engine-oil'],
            ['title' => 'راهنمای انتخاب لنت ترمز', 'url' => '/blog?topic=brake-pad'],
            ['title' => 'سؤال از کارشناس', 'url' => '/page/contact-expert'],
        ];
    }

    private function fallbackCategories(): array
    {
        return [
            ['id' => null, 'title' => 'روغن و روانکار', 'name' => 'روغن و روانکار', 'slug' => 'oil-lubricants', 'icon' => 'oil', 'image' => null, 'url' => '/category/oil-lubricants', 'children' => []],
            ['id' => null, 'title' => 'قطعات اصلی', 'name' => 'قطعات اصلی', 'slug' => 'main-parts', 'icon' => 'engine', 'image' => null, 'url' => '/category/main-parts', 'children' => []],
            ['id' => null, 'title' => 'قطعات جانبی', 'name' => 'قطعات جانبی', 'slug' => 'accessories-parts', 'icon' => 'gear', 'image' => null, 'url' => '/category/accessories-parts', 'children' => []],
        ];
    }

    private function fallbackBrands(): array
    {
        return collect(['Motul', 'Castrol', 'Bosch', 'NGK', 'K&N', 'Liqui Moly', 'Valeo', 'Mahle'])
            ->map(fn (string $name) => [
                'id' => null,
                'name' => $name,
                'title' => $name,
                'slug' => str($name)->lower()->replace('&', '')->replace(' ', '-')->toString(),
                'logo' => null,
                'url' => '#',
            ])
            ->all();
    }

    private function fallbackVehicles(): array
    {
        return [
            ['id' => null, 'title' => 'Peugeot 206 تیپ 5', 'type' => 'car', 'brand' => 'Peugeot', 'model' => '206', 'trim' => 'تیپ 5', 'engine' => 'TU5', 'slug' => 'peugeot-206-type-5'],
            ['id' => null, 'title' => 'Samand EF7', 'type' => 'car', 'brand' => 'Samand', 'model' => 'EF7', 'trim' => 'EF7', 'engine' => 'EF7', 'slug' => 'samand-ef7'],
        ];
    }
}
