<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidPurchaseOrderStatusException;
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

    public function index(Request $request)
    {
        $this->authorize('viewAny', PurchaseOrder::class);

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
