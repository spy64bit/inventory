<?php

namespace App\Http\Controllers;

use App\Filters\SupplierFilter;
use App\Http\Requests\BulkDestroySupplierRequest;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SupplierController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, SupplierFilter $filter)
    {
        $this->authorize('viewAny', Supplier::class);

        $suppliers = $filter->apply(Supplier::query());

        return Inertia::render('Supplier/Index', [
            'suppliers' => $suppliers,
            'filters' => $filter->filters(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSupplierRequest $request)
    {
        $validated = $request->validated();

        Supplier::create($validated);

        return redirect()->back();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        $validated = $request->validated();

        $supplier->update($validated);

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier)
    {
        $this->authorize('delete', $supplier);

        Supplier::destroy($supplier->id);

        return redirect()->back();
    }

    /**
     * Remove multiple resources from storage.
     */
    public function bulkDestroy(BulkDestroySupplierRequest $request)
    {
        $validated = $request->validated();

        Supplier::destroy($validated['ids']);

        return redirect()->back();
    }

    public function search(Request $request)
    {
        $search = $request->input('q');

        $suppliers = Supplier::whereLike('name', '%'.$search.'%')
            ->orderBy('name')
            ->limit(10)
            ->get([
                'id', 'name',
            ]);

        return response()->json($suppliers);
    }
}
