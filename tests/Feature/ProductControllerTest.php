<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('product.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_index_returns_paginated_products(): void
    {
        Product::factory()->count(15)->create();

        $response = $this->actingAs($this->user)
            ->get(route('product.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Product/Index')
            ->has('products.data', 10)
            ->has('products.links')
            ->where('products.total', 15)
            ->has('filters')
        );
    }

    public function test_index_search_filters_by_name(): void
    {
        Product::factory()->create(['name' => 'Widget Alpha']);
        Product::factory()->create(['name' => 'Gadget Beta']);

        $response = $this->actingAs($this->user)
            ->get(route('product.index', ['search' => 'Widget']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Widget Alpha')
        );
    }

    public function test_index_search_filters_by_sku(): void
    {
        Product::factory()->create(['sku' => 'SKU-FIND-ME']);
        Product::factory()->create(['sku' => 'SKU-OTHER']);

        $response = $this->actingAs($this->user)
            ->get(route('product.index', ['search' => 'FIND-ME']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.sku', 'SKU-FIND-ME')
        );
    }

    public function test_index_sorts_by_name_ascending(): void
    {
        Product::factory()->create(['name' => 'Zulu']);
        Product::factory()->create(['name' => 'Alpha']);

        $response = $this->actingAs($this->user)
            ->get(route('product.index', ['sort' => 'name', 'direction' => 'asc']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('products.data.0.name', 'Alpha')
            ->where('products.data.1.name', 'Zulu')
        );
    }

    public function test_index_sorts_by_name_descending(): void
    {
        Product::factory()->create(['name' => 'Zulu']);
        Product::factory()->create(['name' => 'Alpha']);

        $response = $this->actingAs($this->user)
            ->get(route('product.index', ['sort' => 'name', 'direction' => 'desc']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('products.data.0.name', 'Zulu')
            ->where('products.data.1.name', 'Alpha')
        );
    }

    public function test_index_respects_per_page_parameter(): void
    {
        Product::factory()->count(30)->create();

        $response = $this->actingAs($this->user)
            ->get(route('product.index', ['per_page' => 25]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('products.data', 25)
        );
    }

    public function test_index_ignores_invalid_sort_column(): void
    {
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->get(route('product.index', ['sort' => 'invalid_column']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('products.data', 3)
        );
    }

    public function test_destroy_deletes_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('product.destroy', $product));

        $response->assertRedirect();
        $this->assertModelMissing($product);
    }

    public function test_bulk_destroy_deletes_multiple_products(): void
    {
        $products = Product::factory()->count(3)->create();
        $keepProduct = Product::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('product.bulk-destroy'), [
                'ids' => $products->pluck('id')->toArray(),
            ]);

        $response->assertRedirect();

        foreach ($products as $product) {
            $this->assertModelMissing($product);
        }

        $this->assertModelExists($keepProduct);
    }

    public function test_bulk_destroy_validates_ids_are_required(): void
    {
        $response = $this->actingAs($this->user)
            ->delete(route('product.bulk-destroy'), [
                'ids' => [],
            ]);

        $response->assertSessionHasErrors('ids');
    }

    public function test_edit_displays_product_form(): void
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('product.edit', $product));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Product/Edit')
            ->where('product.id', $product->id)
            ->where('product.name', $product->name)
        );
    }

    public function test_update_modifies_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->user)
            ->put(route('product.update', $product), [
                'sku' => 'UPDATED-SKU',
                'name' => 'Updated Name',
                'description' => 'Updated description',
                'cost_price' => 99.99,
                'reorder_level' => 5,
            ]);

        $response->assertRedirect(route('product.index'));
        $product->refresh();
        $this->assertEquals('UPDATED-SKU', $product->sku);
        $this->assertEquals('Updated Name', $product->name);
    }

    public function test_update_validates_required_fields(): void
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->user)
            ->put(route('product.update', $product), []);

        $response->assertSessionHasErrors(['sku', 'name', 'cost_price', 'reorder_level']);
    }

    public function test_update_validates_unique_sku(): void
    {
        $existingProduct = Product::factory()->create(['sku' => 'TAKEN-SKU']);
        $product = Product::factory()->create();

        $response = $this->actingAs($this->user)
            ->put(route('product.update', $product), [
                'sku' => 'TAKEN-SKU',
                'name' => 'Some Name',
                'cost_price' => 10,
                'reorder_level' => 1,
            ]);

        $response->assertSessionHasErrors('sku');
    }
}
