<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Http\Controllers\StatisticsController;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StatisticsControllerTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;

    public function test_stats_returns_expected_structure()
    {
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();

        Product::factory()->create([
            'category_id' => $category1->id,
            'price' => 100
        ]);
        Product::factory()->create([
            'category_id' => $category1->id,
            'price' => 200
        ]);
        Product::factory()->create([
            'category_id' => $category2->id,
            'price' => 300
        ]);

        Cache::flush();

        $controller = new StatisticsController();
        $response = $controller->stats();

        $this->assertArrayHasKey('total', $response);
        $this->assertArrayHasKey('total_per_category', $response);
        $this->assertArrayHasKey('avg_price', $response);
        $this->assertArrayHasKey('top_5_expensive', $response);

        $this->assertEquals(3, $response['total']);
        $this->assertEquals(200, $response['avg_price']);

        $totals = $response['total_per_category']->pluck('total', 'category_id')->toArray();
        $this->assertEquals(2, $totals[$category1->id]);
        $this->assertEquals(1, $totals[$category2->id]);

        $topPrices = array_column($response['top_5_expensive'], 'price');
        $this->assertEquals([300, 200, 100], $topPrices);
    }
}
