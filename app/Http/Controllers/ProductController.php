<?php

namespace App\Http\Controllers;

use App\Filters\ProductFilter;
use App\Http\Requests\BulkDestroyProductRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, ProductFilter $filter)
    {
        $this->authorize('viewAny', Product::class);

        $products = $filter->apply(Product::query());

        $products->getCollection()->transform(function (Product $product) use ($request) {
            return array_merge($product->toArray(), [
                'can' => [
                    'stock_in' => $request->user()->can('stockIn', $product),
                    'stock_out' => $request->user()->can('stockOut', $product),
                ],
            ]);
        });

        // stats
        $total = $products->total();
        $stock = floor(Product::sum('current_stock'));
        $lowOnStock = Product::withTrashed()->whereColumn('current_stock', '<=', 'reorder_level')->count();

        return Inertia::render('Product/Index', [
            'products' => $products,
            'filters' => $filter->filters(),
            'stats' => [
                'total' => $total,
                'stock' => $stock,
                'low_on_stock' => $lowOnStock,
            ],
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        $validated = $request->validated();

        Product::create($validated);

        return redirect()->back()->with('success', 'Product created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        $this->authorize('update', $product);

        return Inertia::render('Product/Edit', [
            'product' => $product,
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $saveAndClose = $request->query('save_and_close', false);

        $validated = $request->validated();

        $product->update($validated);

        if ($saveAndClose) {
            return to_route('product.index')->with('success', 'Product updated successfully.');
        }

        return to_route('product.edit', $product)->with('success', 'Product updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        Product::destroy($product->id);

        return to_route('product.index')->with('success', 'Product deleted successfully.');
    }

    /**
     * Remove multiple resources from storage.
     */
    public function bulkDestroy(BulkDestroyProductRequest $request)
    {
        $validated = $request->validated();

        Product::destroy($validated['ids']);

        return to_route('product.index')->with('success', 'Products deleted successfully.');
    }
}
