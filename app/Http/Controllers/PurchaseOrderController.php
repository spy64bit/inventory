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

    public function index()
    {
        $purchaseOrders = PurchaseOrder::with(['supplier', 'createdBy', 'approvedBy'])
            ->latest()
            ->paginate(10);

        return Inertia::render('PurchaseOrders/Index', [
            'purchaseOrders' => $purchaseOrders,
        ]);
    }

    public function create()
    {
        return Inertia::render('PurchaseOrders/Create', [
            'suppliers' => Supplier::select('id', 'name')->get(),
            'products' => Product::select('id', 'name', 'cost_price', 'unit_of_measure')->get(),
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
        ]);

        $this->purchaseOrderService->create($data);

        return redirect()->route('purchase-orders.index')
            ->with('success', 'Purchase order created successfully.');
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['supplier', 'createdBy', 'approvedBy', 'items.product']);

        return Inertia::render('PurchaseOrders/Show', [
            'purchaseOrder' => $purchaseOrder,
        ]);
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
