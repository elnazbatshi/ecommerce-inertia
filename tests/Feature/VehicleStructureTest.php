<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VehicleStructureTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_manage_vehicle_types(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->post('/admin/vehicle-types', [
                'name' => 'کامیون تست',
                'slug' => 'test-truck',
                'sort_order' => 5,
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.vehicle-types.index'));

        $type = VehicleType::where('slug', 'test-truck')->firstOrFail();

        $this->actingAs($admin)
            ->put("/admin/vehicle-types/{$type->id}", [
                'name' => 'کامیون تست ویرایش',
                'slug' => 'test-truck',
                'sort_order' => 6,
                'is_active' => false,
            ])
            ->assertRedirect(route('admin.vehicle-types.index'));

        $this->assertSame('کامیون تست ویرایش', $type->fresh()->name);
        $this->assertFalse($type->fresh()->is_active);
    }

    public function test_admin_can_create_vehicle_brand_for_vehicle_type(): void
    {
        $admin = User::factory()->create();
        $type = $this->vehicleType('truck');

        $this->actingAs($admin)
            ->post('/admin/vehicle-brands', [
                'vehicle_type_id' => $type->id,
                'name' => 'Volvo',
                'slug' => 'volvo-test',
                'country' => 'Sweden',
                'sort_order' => 1,
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.vehicle-brands.index'));

        $brand = VehicleBrand::where('slug', 'volvo-test')->firstOrFail();

        $this->assertSame($type->id, $brand->vehicle_type_id);
        $this->assertSame('universal', $brand->type);
    }

    public function test_admin_can_create_vehicle_under_brand_and_product_can_attach_it(): void
    {
        $admin = User::factory()->create();
        $type = $this->vehicleType('car');
        $brand = $this->vehicleBrand($type);

        $this->actingAs($admin)
            ->post('/admin/vehicles', [
                'vehicle_type_id' => $type->id,
                'vehicle_brand_id' => $brand->id,
                'name' => '206 تیپ 5',
                'slug' => 'peugeot-206-type-5-test',
                'year_from' => 1380,
                'year_to' => 1403,
                'engine' => 'TU5',
                'sort_order' => 1,
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.vehicles.index'));

        $vehicle = Vehicle::where('slug', 'peugeot-206-type-5-test')->firstOrFail();
        $product = $this->product();
        $product->vehicles()->sync([$vehicle->id]);

        $this->assertTrue($product->vehicles()->whereKey($vehicle->id)->exists());
    }

    public function test_vehicle_finder_returns_types_brands_and_vehicles(): void
    {
        $type = $this->vehicleType('motorcycle');
        $brand = $this->vehicleBrand($type, 'Honda', 'honda-test');
        $vehicle = Vehicle::create([
            'vehicle_brand_id' => $brand->id,
            'type' => 'motorcycle',
            'name' => 'PCX 160',
            'slug' => 'pcx-160-test',
            'is_active' => true,
        ]);

        $this->getJson('/api/frontend/vehicle-finder/types')
            ->assertOk()
            ->assertJsonFragment(['slug' => 'motorcycle']);

        $this->getJson("/api/frontend/vehicle-finder/brands?vehicle_type_id={$type->id}")
            ->assertOk()
            ->assertJsonFragment(['slug' => $brand->slug]);

        $this->getJson("/api/frontend/vehicle-finder/vehicles?vehicle_brand_id={$brand->id}")
            ->assertOk()
            ->assertJsonFragment(['slug' => $vehicle->slug]);
    }

    public function test_product_archive_can_filter_by_vehicle(): void
    {
        $type = $this->vehicleType('car');
        $brand = $this->vehicleBrand($type);
        $vehicle = Vehicle::create([
            'vehicle_brand_id' => $brand->id,
            'type' => 'car',
            'name' => '207',
            'slug' => 'peugeot-207-test',
            'is_active' => true,
        ]);
        $matching = $this->product('matching-product');
        $other = $this->product('other-product');
        $matching->vehicles()->sync([$vehicle->id]);

        $response = $this->get("/products?vehicle={$vehicle->id}");

        $response->assertOk();
        $products = $response->viewData('page')['props']['products']['data'] ?? [];

        $this->assertContains($matching->id, collect($products)->pluck('id')->all());
        $this->assertNotContains($other->id, collect($products)->pluck('id')->all());

        $this->assertFalse($other->vehicles()->whereKey($vehicle->id)->exists());
    }

    private function vehicleType(string $slug): VehicleType
    {
        return VehicleType::query()->updateOrCreate(
            ['slug' => $slug],
            ['name' => ucfirst($slug), 'sort_order' => 1, 'is_active' => true]
        );
    }

    private function vehicleBrand(VehicleType $type, string $name = 'Peugeot', string $slug = 'peugeot-test'): VehicleBrand
    {
        return VehicleBrand::create([
            'vehicle_type_id' => $type->id,
            'name' => $name,
            'slug' => $slug . '-' . uniqid(),
            'type' => $type->slug === 'motorcycle' ? 'motorcycle' : 'car',
            'is_active' => true,
        ]);
    }

    private function product(string $slug = 'test-product'): Product
    {
        return Product::create([
            'name' => $slug,
            'slug' => $slug . '-' . uniqid(),
            'price' => 100000,
            'status' => 'active',
            'type' => 'simple',
            'stock' => 10,
        ]);
    }
}
