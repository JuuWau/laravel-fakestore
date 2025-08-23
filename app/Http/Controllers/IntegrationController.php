<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class IntegrationController extends Controller
{
        public function sync()
    {
        $categoriesApi = Http::timeout(5)->retry(3, 100)->get('https://fakestoreapi.com/products/categories');
        if ($categoriesApi->failed()) {
            return response()->json(['error' => 'Failed to fetch categories'], 500);
        }

        DB::transaction(function() use ($categoriesApi) {
            foreach ($categoriesApi->json() as $categoryName) {
                Category::updateOrCreate(
                    ['external_id' => $categoryName],
                    ['name' => $categoryName]
                );
            }

            $productsApi = Http::timeout(5)->retry(3, 100)->get('https://fakestoreapi.com/products');
            foreach ($productsApi->json() as $item) {
                try {
                    $category = Category::where('external_id', $item['category'])->first();
                    Product::updateOrCreate(
                        ['external_id' => $item['id']],
                        [
                            'title' => $item['title'],
                            'price' => $item['price'],
                            'description' => $item['description'],
                            'image' => $item['image'],
                            'category_id' => $category->id
                        ]
                    );
                } catch (\Exception $e) {
                    Log::error('Failed to sync product', ['external_id' => $item['id'], 'error' => $e->getMessage()]);
                }
            }
        });

        Cache::forget('products_list');
        Cache::forget('products_stats');

        return response()->json(['message' => 'Sync completed']);
    }
}
