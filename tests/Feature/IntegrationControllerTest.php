<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Category;
use App\Models\Product;

class IntegrationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_syncs_categories_and_products_successfully()
    {
        Http::fake([
            'https://fakestoreapi.com/products/categories' => Http::response([
                'electronics',
                'jewelery',
            ], 200),
            'https://fakestoreapi.com/products' => Http::response([
                [
                    'id' => 1,
                    'title' => 'Product 1',
                    'price' => 99.99,
                    'description' => 'A sample product',
                    'category' => 'electronics',
                    'image' => 'http://example.com/image1.jpg',
                ],
                [
                    'id' => 2,
                    'title' => 'Product 2',
                    'price' => 149.99,
                    'description' => 'Another sample product',
                    'category' => 'jewelery',
                    'image' => 'http://example.com/image2.jpg',
                ],
            ], 200),
        ]);

        $response = $this->postJson('/api/integracoes/fakestore/sync', [], [
            'X-Client-Id' => 'test-client',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Sync completed']);

        $this->assertDatabaseHas('categories', ['name' => 'electronics']);
        $this->assertDatabaseHas('categories', ['name' => 'jewelery']);

        $this->assertDatabaseHas('products', [
            'title' => 'Product 1',
            'price' => 99.99,
            'category_id' => Category::where('name', 'electronics')->first()->id,
        ]);

        $this->assertDatabaseHas('products', [
            'title' => 'Product 2',
            'price' => 149.99,
            'category_id' => Category::where('name', 'jewelery')->first()->id,
        ]);

        $this->assertNull(Cache::get('products_list'));
        $this->assertNull(Cache::get('products_stats'));
    }

    public function test_it_handles_failed_category_request()
    {
        Http::fake([
            'https://fakestoreapi.com/products/categories' => Http::response(null, 500),
        ]);

        $response = $this->postJson('/api/integracoes/fakestore/sync', [], [
            'X-Client-Id' => 'test-client',
        ]);

        $response->assertStatus(500)
                 ->assertJsonFragment(['error' => 'Failed to fetch categories']);
    }
}
