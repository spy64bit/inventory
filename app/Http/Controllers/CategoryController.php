<?php

namespace App\Http\Controllers;

use App\Filters\CategoryFilter;
use App\Http\Requests\BulkDestroyCategoryRequest;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
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
    public function index(Request $request, CategoryFilter $filter)
    {
        $this->authorize('viewAny', Category::class);

        $categories = $filter->apply(Category::query()->with('parent:id,name'));

        return Inertia::render('Category/Index', [
            'categories' => $categories,
            'filters' => $filter->filters(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request)
    {

        $validated = $request->validated();

        // Generate slug from name
        $validated['slug'] = strtolower(str_replace(' ', '-', $validated['name']));

        Category::create($validated);

        return redirect()->back();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category)
    {

        $validated = $request->validated();

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
    public function bulkDestroy(BulkDestroyCategoryRequest $request)
    {
        $validated = $request->validated();

        Category::destroy($validated['ids']);

        return redirect()->back();
    }

    public function search(Request $request)
    {
        $search = $request->input('q');

        $categories = Category::where('name', 'like', $search.'%')
            ->orderBy('name')
            ->limit(10)
            ->get([
                'id', 'name',
            ]);

        return response()->json($categories);
    }
}
