<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category');

        if ($request->category_id) $query->where('category_id', $request->category_id);
        if ($request->min_price) $query->where('price', '>=', $request->min_price);
        if ($request->max_price) $query->where('price', '<=', $request->max_price);
        if ($request->search) $query->where('title', 'like', '%' . $request->search . '%');

        if ($request->sort_price) {
            $direction = in_array(strtolower($request->sort_price), ['asc', 'desc']) ? $request->sort_price : 'asc';
            $query->orderBy('price', $direction);
        }

        $page = $request->page ?? 1;
        $perPage = $request->per_page ?? 10;

        $cacheKey = 'products_list_' . md5(json_encode($request->only([
            'category_id','min_price','max_price','search','sort_price','page','per_page'
        ])));

        return response()->json(
            Cache::remember($cacheKey, 60, fn() => $query->paginate(perPage: $perPage, page: $page))
        );
    }

    public function show($id)
    {
        return Product::with('category')->findOrFail($id);
    }
}
