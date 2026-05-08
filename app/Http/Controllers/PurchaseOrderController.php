<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidPurchaseOrderStatusException;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\PurchaseOrderService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PurchaseOrderController extends Controller
{
    public function __construct(
        private readonly PurchaseOrderService $purchaseOrderService
    ) {}

    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:255',
            'sort' => 'nullable|in:id,status,created_at,submitted_at,received_at',
            'direction' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|in:10,25,50',
        ]);

        $sort = $filters['sort'] ?? 'created_at';
        $direction = $filters['direction'] ?? 'desc';
        $perPage = (int) ($filters['per_page'] ?? 10);
        $search = $filters['search'] ?? null;

        $purchaseOrders = PurchaseOrder::with(['supplier:id,name', 'createdBy:id,name', 'approvedBy:id,name'])
            ->when($search, function ($query, $search): void {
                $query->where(function ($q) use ($search): void {
                    $q->where('id', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('PurchaseOrders/Index', [
            'purchaseOrders' => $purchaseOrders,
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('PurchaseOrders/Create', [
            'suppliers' => Supplier::all(['id', 'name', 'email', 'phone', 'address']),
            'products' => Product::all(['id', 'sku', 'name', 'cost_price', 'unit_of_measure']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity_ordered' => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'required|numeric|min:0.01',
        ], [
            'items.*.product_id.required' => 'Please select a product.',
            'items.*.product_id.exists' => 'Please select a product.',
            'items.*.unit_cost.min' => 'Unit cost must be at least 0.01.',
        ]);

        $this->purchaseOrderService->create($data);

        return redirect()->route('purchase-orders.index')
            ->with('success', 'Purchase order created successfully.');
    }

    public function edit(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['supplier', 'createdBy', 'approvedBy', 'items.product']);

        return Inertia::render('PurchaseOrders/Show', [
            'purchaseOrder' => $purchaseOrder,
            'suppliers' => Supplier::all(['id', 'name', 'email', 'phone', 'address']),
            'products' => Product::all(['id', 'sku', 'name', 'cost_price', 'unit_of_measure']),
        ]);
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $data = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity_ordered' => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'required|numeric|min:0.01',
        ], [
            'items.*.product_id.required' => 'Please select a product.',
            'items.*.product_id.exists' => 'Please select a product.',
            'items.*.unit_cost.min' => 'Unit cost must be at least 0.01.',
        ]);

        $this->purchaseOrderService->update($purchaseOrder, $data);

        return redirect()->route('purchase-orders.edit', $purchaseOrder)
            ->with('success', 'Purchase order updated successfully.');
    }

    public function approve(PurchaseOrder $purchaseOrder)
    {
        try {
            $this->purchaseOrderService->approve($purchaseOrder);
        } catch (InvalidPurchaseOrderStatusException $e) {
            return redirect()->back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Purchase order approved successfully.');
    }

    public function submit(PurchaseOrder $purchaseOrder)
    {
        try {
            $this->purchaseOrderService->submit($purchaseOrder);
        } catch (InvalidPurchaseOrderStatusException $e) {
            return redirect()->back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Purchase order submitted to supplier.');
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder)
    {
        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity_received' => 'required|numeric|min:0',
        ]);

        try {
            $this->purchaseOrderService->receive($purchaseOrder, $data['items']);
        } catch (InvalidPurchaseOrderStatusException $e) {
            return redirect()->back()->withErrors(['status' => $e->getMessage()]);
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['items' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Stock received and updated successfully.');
    }

    public function cancel(PurchaseOrder $purchaseOrder)
    {
        try {
            $this->purchaseOrderService->cancel($purchaseOrder);
        } catch (InvalidPurchaseOrderStatusException $e) {
            return redirect()->back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Purchase order cancelled.');
    }

    public function close(PurchaseOrder $purchaseOrder)
    {
        try {
            $this->purchaseOrderService->close($purchaseOrder);
        } catch (InvalidPurchaseOrderStatusException $e) {
            return redirect()->back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Purchase order closed.');
    }
}
