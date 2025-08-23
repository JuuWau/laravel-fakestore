<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class StatisticsController extends Controller
{
    public function stats()
    {
        return Cache::remember('products_stats', 60, function () {
            $total = Product::count();
            $totalPerCategory = Product::select('category_id', DB::raw('count(*) as total'))
                ->groupBy('category_id')->get();
            $avgPrice = Product::average('price');
            $top5 = DB::select('SELECT * FROM products ORDER BY price DESC LIMIT 5');

            return [
                'total' => $total,
                'total_per_category' => $totalPerCategory,
                'avg_price' => $avgPrice,
                'top_5_expensive' => $top5
            ];
        });
    }
}
