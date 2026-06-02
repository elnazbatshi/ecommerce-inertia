<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Http\Services\ProductService;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $products) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Products/Index', [
            'products' => $this->products->paginated($request),
            'filters' => $request->only(['search', 'category_id', 'brand_id', 'status', 'type', 'sortField', 'sortOrder', 'rows']),
            'categories' => $this->products->categoryOptions(),
            'brands' => $this->products->brandOptions(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Products/Create', $this->formData());
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $this->products->create($request);

        return redirect()->route('admin.products.index')->with('success', 'محصول ایجاد شد.');
    }

    public function edit(Product $product): Response
    {
        $product->load(['images', 'variants.attributeValues.attribute', 'category:id,name', 'brand:id,name,slug,description,content', 'relatedPosts:id,title,slug']);
        $product->load(['vehicles.brand:id,name']);

        return Inertia::render('Products/Edit', [
            ...$this->formData(),
            'product' => ProductResource::make($product)->resolve(),
        ]);
    }

    public function show(Product $product): RedirectResponse
    {
        return redirect()->route('admin.products.edit', $product);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->products->update($request, $product);

        return redirect()->route('admin.products.index')->with('success', 'محصول ویرایش شد.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return back()->with('success', 'محصول حذف شد.');
    }

    public function destroyImage(ProductImage $image): RedirectResponse
    {
        $this->products->deleteImage($image);

        return back()->with('success', 'تصویر حذف شد.');
    }

    public function destroyVariant(ProductVariant $variant): RedirectResponse
    {
        $this->products->deleteVariant($variant);

        return back()->with('success', 'تنوع حذف شد.');
    }

    private function formData(): array
    {
        return [
            'categories' => $this->products->categoryOptions(),
            'brands' => $this->products->brandOptions(),
            'attributes' => $this->products->attributeOptions(),
            'posts' => $this->products->publishedPostOptions(),
            'statusOptions' => config('shop.products.status_options'),
            'typeOptions' => config('shop.products.type_options'),
            'vehicles' => $this->products->vehicleOptions(),
        ];
    }
}
