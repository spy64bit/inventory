<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Category::class);

        $filters = $request->only(['search', 'sort', 'direction', 'per_page']);

        $query = Category::query();

        if ($search = $request->input('search')) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        $sortColumn = $request->input('sort', 'id');
        $sortDirection = $request->input('direction', 'desc');

        $allowedSorts = ['id', 'name', 'created_at'];
        if (! in_array($sortColumn, $allowedSorts)) {
            $sortColumn = 'id';
        }
        if (! in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        $query->orderBy($sortColumn, $sortDirection);

        $perPage = min((int) $request->input('per_page', 10), 100);

        $categories = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Category/Index', [
            'categories' => $categories,
            'filters' => $filters,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Category::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:category,name'],
        ]);

        Category::create($validated);

        return redirect()->back();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        $this->authorize('update', $category);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:category,name,'.$category->id],
        ]);

        $category->update($validated);

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        $this->authorize('delete', $category);

        $category->delete();

        return redirect()->back();
    }

    /**
     * Remove multiple resources from storage.
     */
    public function bulkDestroy(Request $request)
    {
        $this->authorize('delete', Category::class);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:category,id'],
        ]);

        Category::destroy($validated['ids']);

        return redirect()->back();
    }

    public function search(Request $request)
    {
        $search = $request->input('q');

        $categories = Category::whereLike('name', '%'.$search.'%')
            ->orderBy('name')
            ->limit(10)
            ->get([
                'id', 'name',
            ]);

        return response()->json($categories);
    }
}
