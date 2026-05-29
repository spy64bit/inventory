<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidPurchaseOrderStatusException;
use App\Filters\PurchaseOrderFilter;
use App\Http\Requests\ReceivePurchaseOrderRequest;
use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Requests\UpdatePurchaseOrderRequest;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\PurchaseOrderService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PurchaseOrderController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly PurchaseOrderService $purchaseOrderService
    ) {}

    public function index(Request $request, PurchaseOrderFilter $filter)
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        $purchaseOrders = $filter->apply(PurchaseOrder::query());

        return Inertia::render('PurchaseOrders/Index', [
            'purchaseOrders' => $purchaseOrders,
            'filters' => $filter->filters(),
        ]);
    }

    public function create()
    {
        return Inertia::render('PurchaseOrders/Create', [
            'suppliers' => Supplier::all(['id', 'name', 'email', 'phone', 'address']),
            'products' => Product::all(['id', 'sku', 'name', 'cost_price', 'unit_of_measure']),
        ]);
    }

    public function store(StorePurchaseOrderRequest $request)
    {
        $data = $request->validated();

        $this->purchaseOrderService->create($data);

        return redirect()->route('purchase-orders.index')
            ->with('success', 'Purchase order created successfully.');
    }

    public function edit(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);

        $purchaseOrder->load(['supplier', 'createdBy', 'approvedBy', 'items.product']);

        return Inertia::render('PurchaseOrders/Show', [
            'purchaseOrder' => $purchaseOrder,
            'suppliers' => Supplier::all(['id', 'name', 'email', 'phone', 'address']),
            'products' => Product::all(['id', 'sku', 'name', 'cost_price', 'unit_of_measure']),
            'can' => [
                'approve' => Auth::user()->can('approve', $purchaseOrder),
                'cancel' => Auth::user()->can('cancel', $purchaseOrder),
                'dispatch' => Auth::user()->can('dispatch', $purchaseOrder),
                'receive' => Auth::user()->can('receive', $purchaseOrder),
                'close' => Auth::user()->can('close', $purchaseOrder),
                'update' => Auth::user()->can('update', $purchaseOrder),
            ],
        ]);
    }

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder)
    {
        $data = $request->validated();

        $this->purchaseOrderService->update($purchaseOrder, $data);

        return redirect()->route('purchase-orders.edit', $purchaseOrder)
            ->with('success', 'Purchase order updated successfully.');
    }

    public function approve(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('approve', $purchaseOrder);

        try {
            $this->purchaseOrderService->approve($purchaseOrder);
        } catch (InvalidPurchaseOrderStatusException $e) {
            return redirect()->back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Purchase order approved successfully.');
    }

    public function dispatch(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('dispatch', $purchaseOrder);

        try {
            $this->purchaseOrderService->dispatch($purchaseOrder);
        } catch (InvalidPurchaseOrderStatusException $e) {
            return redirect()->back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Purchase order dispatched to supplier.');
    }

    public function receive(ReceivePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder)
    {
        $data = $request->validated();

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
        $this->authorize('cancel', $purchaseOrder);

        try {
            $this->purchaseOrderService->cancel($purchaseOrder);
        } catch (InvalidPurchaseOrderStatusException $e) {
            return redirect()->back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Purchase order cancelled.');
    }

    public function close(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('close', $purchaseOrder);

        try {
            $this->purchaseOrderService->close($purchaseOrder);
        } catch (InvalidPurchaseOrderStatusException $e) {
            return redirect()->back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Purchase order closed.');
    }
}
