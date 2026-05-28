<?php

namespace App\Http\Controllers;

use App\Filters\CustomerFilter;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CustomerController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, CustomerFilter $filter)
    {
        $this->authorize('viewAny', Customer::class);

        $customers = $filter->apply(Customer::query());

        // add permissions to each customer
        $customers->getCollection()->transform(function (Customer $customer) {
            return array_merge($customer->toArray(), [
                'can' => [
                    'update' => Auth::user()->can('update', $customer),
                    'delete' => Auth::user()->can('delete', $customer),
                ],
            ]);
        });

        return Inertia::render('Customer/Index', [
            'customers' => $customers,
            'filters' => $filter->filters(),
            'can' => [
                'create' => Auth::user()->can('create', Customer::class),
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
    public function store(StoreCustomerRequest $request)
    {
        $validated = $request->validated();

        $customer = Customer::create($validated);

        return redirect()->back()->with('success', 'Customer created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $validated = $request->validated();

        $customer->update($validated);

        return redirect()->back()->with('success', 'Customer updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()->back()->with('success', 'Customer deleted successfully.');
    }
}
