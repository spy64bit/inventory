<?php

namespace App\Http\Controllers;

use App\Models\StockMovements;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StockMovementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'sort', 'direction', 'per_page']);

        $query = StockMovements::query()
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
}
