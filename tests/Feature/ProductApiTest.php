<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_products_endpoint_requires_client_id()
    {
        $response = $this->getJson('/api/products');

        $response->assertStatus(400)
                 ->assertJson(['error' => 'X-Client-Id header missing']);
    }

    public function test_products_endpoint_returns_success_with_valid_client_id()
    {
        Cache::flush();

        $response = $this->getJson('/api/products', [
            'X-Client-Id' => 'teste123',
        ]);

        $response->assertStatus(200);
    }

    public function test_products_endpoint_rate_limiting()
    {
        Cache::flush();
        $headers = ['X-Client-Id' => 'teste123'];

        for ($i = 0; $i < 10; $i++) {
            $this->getJson('/api/products', $headers)->assertStatus(200);
        }

        $this->getJson('/api/products', $headers)
             ->assertStatus(429)
             ->assertJson(['message' => 'Too many requests']);
    }
}
