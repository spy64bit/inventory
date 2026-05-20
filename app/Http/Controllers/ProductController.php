<?php

namespace App\Http\Controllers;

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
    public function index(Request $request)
    {
        $this->authorize('viewAny', Product::class);

        $filters = $request->only(['search', 'sort', 'direction', 'per_page']);

        $query = Product::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('sku', 'like', '%'.$search.'%');
            });
        }

        $sortColumn = $request->input('sort', 'id');
        $sortDirection = $request->input('direction', 'desc');

        $allowedSorts = ['id', 'sku', 'name', 'cost_price', 'reorder_level', 'created_at'];
        if (! in_array($sortColumn, $allowedSorts)) {
            $sortColumn = 'id';
        }
        if (! in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        $query->orderBy($sortColumn, $sortDirection);

        $perPage = min((int) $request->input('per_page', 10), 100);

        $products = $query->paginate($perPage)->withQueryString();

        $total = $products->total();
        $stock = floor($products->sum('current_stock'));
        $lowOnStock = $products->where('current_stock', '<=', 'reorder_level')->count();

        return Inertia::render('Product/Index', [
            'products' => $products,
            'filters' => $filters,
            'stats' => [
                'total' => $total,
                'stock' => $stock,
                'low_on_stock' => $lowOnStock,
            ],
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
    public function store(Request $request)
    {
        $this->authorize('create', Product::class);

        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:255', 'unique:products,sku'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'reorder_level' => ['required', 'numeric', 'min:0'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
        ]);

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
    public function update(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        $saveAndClose = $request->query('save_and_close', false);

        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:255', 'unique:products,sku,'.$product->id],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'reorder_level' => ['required', 'numeric', 'min:0'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
        ]);

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
    public function bulkDestroy(Request $request)
    {
        $this->authorize('delete', Product::class);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:products,id'],
        ]);

        Product::destroy($validated['ids']);

        return to_route('product.index')->with('success', 'Products deleted successfully.');
    }
}
