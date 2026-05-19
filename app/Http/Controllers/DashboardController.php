<?php

namespace App\Http\Controllers;

use App\Enums\PurchaseOrderStatus;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $openStatuses = [
            PurchaseOrderStatus::Draft,
            PurchaseOrderStatus::Approved,
            PurchaseOrderStatus::Dispatched,
            PurchaseOrderStatus::PartiallyReceived,
        ];

        $stats = [
            'total_products' => Product::count(),
            'low_stock_count' => Product::whereColumn('current_stock', '<=', 'reorder_level')->count(),
            'inventory_value' => (float) Product::sum(DB::raw('current_stock * cost_price')),
            'open_orders_count' => PurchaseOrder::whereIn('status', $openStatuses)->count(),
        ];

        $ordersByStatus = PurchaseOrder::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->status->value => $row->total]);

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'ordersByStatus' => $ordersByStatus,
            'lowStockProducts' => Inertia::defer(fn () => Product::with('supplier:id,name', 'category:id,name')
                ->whereColumn('current_stock', '<=', 'reorder_level')
                ->orderBy('current_stock')
                ->limit(10)
                ->get(['id', 'sku', 'name', 'current_stock', 'reorder_level', 'supplier_id', 'category_id'])
            ),
            'recentMovements' => Inertia::defer(fn () => StockMovement::with('product:id,sku,name', 'user:id,name')
                ->latest()
                ->limit(8)
                ->get(['id', 'product_id', 'type', 'quantity', 'reference_type', 'user_id', 'created_at'])
            ),
            'recentOrders' => Inertia::defer(fn () => PurchaseOrder::with('supplier:id,name', 'createdBy:id,name')
                ->withCount('items')
                ->latest()
                ->limit(6)
                ->get(['id', 'supplier_id', 'status', 'created_by', 'created_at'])
            ),
        ]);
    }
}
