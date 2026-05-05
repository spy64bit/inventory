<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockMovementService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StockMovementController extends Controller
{
    public function __construct(protected StockMovementService $stockMovementService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'sort', 'direction', 'per_page']);

        $query = StockMovement::query()
            ->with(['product:id,sku,name', 'user:id,name']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('remarks', 'like', '%'.$search.'%')
                    ->orWhereHas('product', function ($pq) use ($search) {
                        $pq->where('name', 'like', '%'.$search.'%')
                            ->orWhere('sku', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', '%'.$search.'%');
                    });
            });
        }

        $sortColumn = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');

        $allowedSorts = ['id', 'type', 'quantity', 'created_at'];
        if (! in_array($sortColumn, $allowedSorts)) {
            $sortColumn = 'created_at';
        }
        if (! in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        $query->orderBy($sortColumn, $sortDirection);

        $perPage = min((int) $request->input('per_page', 10), 100);

        $stockMovements = $query->paginate($perPage)->withQueryString();

        return Inertia::render('StockMovements/Index', [
            'stockMovements' => $stockMovements,
            'filters' => $filters,
        ]);
    }

    public function stockIn(Request $request, Product $product)
    {
        $data = $request->validate([
            'quantity' => 'required|numeric|min:0.01',
            'unit_cost' => 'required|numeric|min:0',
            'remarks' => 'nullable|string|max:255',
        ]);

        $this->stockMovementService->stockIn(
            $product,
            $data['quantity'],
            $data['unit_cost'],
            'adjustment',
            null,
            $data['remarks'] ?? ''
        );

        Inertia::flash('success', 'Stock added successfully.');

        return redirect()->back()->with('success', 'Stock added successfully.');
    }

    public function stockOut(Request $request, Product $product)
    {
        $data = $request->validate([
            'quantity' => 'required|numeric|min:0.01',
            'remarks' => 'nullable|string|max:255',
        ]);

        try {
            $this->stockMovementService->stockOut(
                $product,
                $data['quantity'],
                'adjustment',
                null,
                $data['remarks'] ?? ''
            );
        } catch (InsufficientStockException $e) {
            Inertia::flash('error', $e->getMessage());

            return redirect()->back()->withErrors(['quantity' => $e->getMessage()]);
        }

        $successMessage = 'Stock updated - '.$data['quantity'].' units deducted.';

        Inertia::flash('success', $successMessage);

        return redirect()->back()->with('success', $successMessage);
    }
}
