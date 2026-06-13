<?php

namespace App\Http\Controllers;

use App\Ai\Agents\InventoryCommandAgent;
use App\Http\Requests\AiConfirmRequest;
use App\Http\Requests\AiPromptRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Services\AiAssistantService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Ai\Exceptions\RateLimitedException;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Throwable;

class AiAssistantController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly AiAssistantService $aiAssistantService
    ) {}

    /**
     * Show the AI Assistant page with initial product list.
     */
    public function index(): Response
    {
        return Inertia::render('AiAssistant/Index', [
            'products' => Product::orderBy('name')->get(['id', 'name', 'sku', 'current_stock']),
        ]);
    }

    /**
     * Send a prompt to the AI agent and return the structured interpretation.
     * Does NOT execute any action — only returns the parsed intent for confirmation.
     */
    public function prompt(AiPromptRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $agent = new InventoryCommandAgent;

        if (filled($validated['conversation_id'] ?? null)) {
            $agent = $agent->continue($validated['conversation_id'], as: $user);
        } else {
            $agent = $agent->forUser($user);
        }

        try {
            /** @var StructuredAgentResponse $response */
            $response = $agent->prompt($validated['prompt']);

            Log::info('AI raw response', $response->toArray());

            return response()->json([
                'conversation_id' => $response->conversationId,
                'commands' => $response->toArray()['commands'] ?? [],
            ]);
        } catch (RateLimitedException) {
            return response()->json([
                'error' => 'The AI provider is currently rate limited. Please wait a moment and try again.',
            ], 429);
        }
    }

    /**
     * Execute the confirmed AI action and return the result.
     */
    public function confirm(AiConfirmRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            // For edit_product, pre-fetch the model so we can authorize and avoid a redundant DB query in the service
            $editProduct = null;
            if ($data['action'] === 'edit_product') {
                $editProduct = Product::findOrFail($data['product']['id']);
            }

            // Per-action authorization
            match ($data['action']) {
                'create_purchase_order' => $this->authorize('create', PurchaseOrder::class),
                'create_sales_order' => $this->authorize('create', SalesOrder::class),
                'add_product' => $this->authorize('create', Product::class),
                'edit_product' => $this->authorize('update', $editProduct),
                'check_stock' => $this->authorize('viewAny', Product::class),
            };

            // Entity existence validation before any DB write
            if ($data['action'] === 'create_purchase_order') {
                $earlyResponse = $this->validatePurchaseOrderEntities($data);
                if ($earlyResponse !== null) {
                    return $earlyResponse;
                }
            } elseif ($data['action'] === 'create_sales_order') {
                $earlyResponse = $this->validateSalesOrderEntities($data);
                if ($earlyResponse !== null) {
                    return $earlyResponse;
                }
            }

            $result = match ($data['action']) {
                'create_purchase_order' => $this->handleCreatePurchaseOrder($data),
                'create_sales_order' => $this->handleCreateSalesOrder($data),
                'add_product' => $this->handleAddProduct($data),
                'edit_product' => $this->handleEditProduct($data, $editProduct),
                'check_stock' => $this->handleCheckStock($data),
            };

            return response()->json(['success' => true, 'result' => $result]);
        } catch (AuthorizationException) {
            return response()->json(['success' => false, 'error' => 'You do not have permission to perform this action.'], 403);
        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'error' => 'The referenced record no longer exists.'], 422);
        } catch (QueryException $e) {
            Log::error($e->getMessage());

            return response()->json(['success' => false, 'error' => 'A database error occurred. Please try again.'], 422);
        } catch (Throwable $e) {
            Log::error($e);

            return response()->json(['success' => false, 'error' => 'An unexpected error occurred.'], 500);
        }
    }

    /**
     * Return the most recent AI conversation messages for the authenticated user.
     */
    public function history(Request $request): JsonResponse
    {
        $conversationId = $request->query('conversation_id');

        if (! $conversationId) {
            return response()->json(['messages' => []]);
        }

        $messagesTable = config('ai.conversations.tables.messages', 'agent_conversation_messages');

        $messages = DB::table($messagesTable)
            ->where('conversation_id', $conversationId)
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at')
            ->get(['role', 'content', 'created_at'])
            ->map(fn ($m) => [
                'role' => $m->role,
                'content' => $m->content,
                'created_at' => $m->created_at,
            ]);

        return response()->json(['messages' => $messages]);
    }

    /**
     * @param  array{supplier_id?: int|null, items: array<int, array{product_id: int, quantity: int, unit_price: float|null}>}  $data
     * @return array{id: int, message: string}
     */
    private function handleCreatePurchaseOrder(array $data): array
    {
        $po = $this->aiAssistantService->createPurchaseOrder($data);

        return ['id' => $po->id, 'message' => "Draft purchase order #{$po->id} created successfully."];
    }

    /**
     * @param  array{customer_id?: int|null, items: array<int, array{product_id: int, quantity: int, unit_price: float|null}>}  $data
     * @return array{id: int, message: string}
     */
    private function handleCreateSalesOrder(array $data): array
    {
        $so = $this->aiAssistantService->createSalesOrder($data);

        return ['id' => $so->id, 'message' => "Draft sales order #{$so->id} created successfully."];
    }

    /**
     * @param  array{product: array{name: string, sku?: string|null, cost_price?: float|null, selling_price?: float|null, category_id?: int|null, unit?: string|null}}  $data
     * @return array{id: int, name: string, message: string}
     */
    private function handleAddProduct(array $data): array
    {
        $product = $this->aiAssistantService->addProduct($data['product']);

        return ['id' => $product->id, 'name' => $product->name, 'message' => "Product \"{$product->name}\" created successfully."];
    }

    /**
     * @param  array{product: array{id: int, name?: string|null, sku?: string|null, cost_price?: float|null, selling_price?: float|null, category_id?: int|null, unit?: string|null}}  $data
     * @return array{id: int, name: string, message: string}
     */
    private function handleEditProduct(array $data, Product $product): array
    {
        $product = $this->aiAssistantService->editProduct($data['product'], $product);

        return ['id' => $product->id, 'name' => $product->name, 'message' => "Product \"{$product->name}\" updated successfully."];
    }

    /**
     * @param  array{stock_check_ids: int[]}  $data
     * @return array{products: array<int, array{id: int, name: string, sku: string, current_stock: int|float}>}
     */
    private function handleCheckStock(array $data): array
    {
        $products = $this->aiAssistantService->checkStock($data['stock_check_ids'] ?? []);

        return ['products' => $products];
    }

    /**
     * Verify that all referenced products and the supplier (if provided) exist before writing to the DB.
     *
     * @param  array{supplier_id?: int|null, items?: array<int, array{product_id: int}>}  $data
     */
    private function validatePurchaseOrderEntities(array $data): ?JsonResponse
    {
        $productIds = collect($data['items'] ?? [])->pluck('product_id')->filter()->unique();
        $existingCount = Product::whereIn('id', $productIds)->count();

        if ($existingCount !== $productIds->count()) {
            return response()->json(['success' => false, 'error' => 'One or more products no longer exist. Please refresh and try again.'], 422);
        }

        if (! empty($data['supplier_id']) && ! Supplier::where('id', $data['supplier_id'])->exists()) {
            return response()->json(['success' => false, 'error' => 'The selected supplier no longer exists. Please refresh and try again.'], 422);
        }

        return null;
    }

    /**
     * Verify that all referenced products and the customer (if provided) exist before writing to the DB.
     *
     * @param  array{customer_id?: int|null, items?: array<int, array{product_id: int}>}  $data
     */
    private function validateSalesOrderEntities(array $data): ?JsonResponse
    {
        $productIds = collect($data['items'] ?? [])->pluck('product_id')->filter()->unique();
        $existingCount = Product::whereIn('id', $productIds)->count();

        if ($existingCount !== $productIds->count()) {
            return response()->json(['success' => false, 'error' => 'One or more products no longer exist. Please refresh and try again.'], 422);
        }

        if (! empty($data['customer_id']) && ! Customer::where('id', $data['customer_id'])->exists()) {
            return response()->json(['success' => false, 'error' => 'The selected customer no longer exists. Please refresh and try again.'], 422);
        }

        return null;
    }
}
