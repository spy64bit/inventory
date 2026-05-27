<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Http\Requests\StoreSalesOrderRequest;
use App\Http\Requests\UpdateSalesOrderRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Services\StockMovementService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SalesOrderController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly StockMovementService $stockMovementService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', SalesOrder::class);

        $filters = $request->validate([
            'search' => 'nullable|string|max:255',
            'sort' => 'nullable|in:id,status,created_at,confirmed_at,fulfilled_at',
            'direction' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|in:10,25,50',
        ]);

        $sort = $filters['sort'] ?? 'created_at';
        $direction = $filters['direction'] ?? 'desc';
        $perPage = (int) ($filters['per_page'] ?? 10);
        $search = $filters['search'] ?? null;

        $salesOrders = SalesOrder::with(['customer:id,name', 'createdBy:id,name'])
            ->when($search, function ($query, $search): void {
                $query->where(function ($q) use ($search): void {
                    $q->where('id', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('SalesOrders/Index', [
            'salesOrders' => $salesOrders,
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', SalesOrder::class);

        return Inertia::render('SalesOrders/Create', [
            'customers' => Customer::orderBy('name')->get(['id', 'name', 'email', 'contact_no', 'address']),
            'products' => Product::orderBy('name')->get(['id', 'sku', 'name', 'selling_price', 'current_stock', 'unit_of_measure']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSalesOrderRequest $request)
    {
        $data = $request->validated();

        DB::transaction(function () use ($data): void {
            $salesOrder = SalesOrder::create([
                'customer_id' => $data['customer_id'],
                'status' => 'draft',
                'created_by' => Auth::id(),
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $salesOrder->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                ]);
            }
        });

        return redirect()->route('sales-orders.index')
            ->with('success', 'Sales order created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(SalesOrder $salesOrder)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SalesOrder $salesOrder)
    {
        $this->authorize('view', $salesOrder);

        $salesOrder->load(['customer', 'createdBy', 'items.product']);

        return Inertia::render('SalesOrders/Edit', [
            'salesOrder' => $salesOrder,
            'customers' => Customer::orderBy('name')->get(['id', 'name', 'email', 'contact_no', 'address']),
            'products' => Product::orderBy('name')->get(['id', 'sku', 'name', 'selling_price', 'current_stock', 'unit_of_measure']),
            'can' => [
                'update' => Auth::user()->can('update', $salesOrder),
                'confirm' => Auth::user()->can('confirm', $salesOrder),
                'fulfill' => Auth::user()->can('fulfill', $salesOrder),
                'cancel' => Auth::user()->can('cancel', $salesOrder),
                'delete' => Auth::user()->can('delete', $salesOrder),
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSalesOrderRequest $request, SalesOrder $salesOrder)
    {
        $this->authorize('update', $salesOrder);

        $data = $request->validated();

        DB::transaction(function () use ($salesOrder, $data): void {
            $salesOrder->update([
                'customer_id' => $data['customer_id'],
                'notes' => $data['notes'] ?? null,
            ]);

            $keepIds = [];

            foreach ($data['items'] as $item) {
                if (! empty($item['id'])) {
                    $existing = $salesOrder->items()->where('id', $item['id'])->first();
                    if ($existing) {
                        $existing->update([
                            'product_id' => $item['product_id'],
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['unit_price'],
                        ]);
                        $keepIds[] = $existing->id;

                        continue;
                    }
                }

                $created = $salesOrder->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                ]);
                $keepIds[] = $created->id;
            }

            $salesOrder->items()->whereNotIn('id', $keepIds)->delete();
        });

        return redirect()->route('sales-orders.edit', $salesOrder)
            ->with('success', 'Sales order updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SalesOrder $salesOrder)
    {
        //
    }

    /**
     * Confirm a draft sales order.
     */
    public function confirm(SalesOrder $salesOrder)
    {
        $this->authorize('confirm', $salesOrder);

        if ($salesOrder->status !== 'draft') {
            return redirect()->back()->withErrors(['status' => 'Only draft sales orders can be confirmed.']);
        }

        $salesOrder->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Sales order confirmed.');
    }

    /**
     * Fulfill a confirmed sales order by decrementing product stock.
     */
    public function fulfill(SalesOrder $salesOrder)
    {
        $this->authorize('fulfill', $salesOrder);

        if ($salesOrder->status !== 'confirmed') {
            return redirect()->back()->withErrors(['status' => 'Only confirmed sales orders can be fulfilled.']);
        }

        $salesOrder->load('items.product');

        try {
            DB::transaction(function () use ($salesOrder): void {
                foreach ($salesOrder->items as $item) {
                    $this->stockMovementService->stockOut(
                        product: $item->product,
                        quantity: (float) $item->quantity,
                        referenceType: 'sales_order',
                        referenceId: $salesOrder->id,
                        remarks: "Sales order #{$salesOrder->id} fulfilled",
                    );
                }

                $salesOrder->update([
                    'status' => 'fulfilled',
                    'fulfilled_at' => now(),
                ]);
            });
        } catch (InsufficientStockException $e) {
            return redirect()->back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Sales order fulfilled and stock updated.');
    }

    /**
     * Cancel a draft or confirmed sales order.
     */
    public function cancel(SalesOrder $salesOrder)
    {
        $this->authorize('cancel', $salesOrder);

        if (! in_array($salesOrder->status, ['draft', 'confirmed'], true)) {
            return redirect()->back()->withErrors(['status' => 'Only draft or confirmed sales orders can be cancelled.']);
        }

        $salesOrder->update(['status' => 'cancelled']);

        return redirect()->back()->with('success', 'Sales order cancelled.');
    }
}
