<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $categoryId = $request->query('category_id');
        $minPrice = $request->query('min_price');
        $maxPrice = $request->query('max_price');
        $search = $request->query('search');
        $sortPrice = $request->query('sort_price');
        $perPage = $request->query('per_page', 10);

        $query = Product::with('category');

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($minPrice) {
            $query->where('price', '>=', $minPrice);
        }

        if ($maxPrice) {
            $query->where('price', '<=', $maxPrice);
        }

        if ($search) {
            $query->where('title', 'ilike', "%{$search}%");
        }

        if ($sortPrice && in_array($sortPrice, ['asc', 'desc'])) {
            $query->orderBy('price', $sortPrice);
        } else {
            $query->orderBy('id', 'asc');
        }

        $products = $query->paginate($perPage);

        return response()->json($products);
    }


    public function show($id)
    {
        return Product::with('category')->findOrFail($id);
    }
}
