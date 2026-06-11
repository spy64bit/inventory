<?php

use App\Ai\Agents\InventoryCommandAgent;
use App\Enums\Position;
use App\Enums\PurchaseOrderStatus;
use App\Enums\SalesOrderStatus;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── Helpers ───────────────────────────────────────────────────────────────────

function actingAsUser(): User
{
    $user = User::factory()->create(['position' => Position::Admin]);
    test()->actingAs($user);

    return $user;
}

// ── Auth guards ───────────────────────────────────────────────────────────────

it('redirects guests from ai-assistant page', function () {
    $this->get('/ai-assistant')->assertRedirect('/login');
});

it('redirects guests from prompt endpoint', function () {
    $this->post('/ai-assistant/prompt')->assertRedirect('/login');
});

it('redirects guests from confirm endpoint', function () {
    $this->post('/ai-assistant/confirm')->assertRedirect('/login');
});

// ── Index ─────────────────────────────────────────────────────────────────────

it('renders the ai-assistant page for authenticated users', function () {
    actingAsUser();

    $this->get('/ai-assistant')->assertSuccessful()->assertInertia(
        fn ($page) => $page->component('AiAssistant/Index')
    );
});

// ── Prompt ────────────────────────────────────────────────────────────────────

it('validates prompt is required', function () {
    actingAsUser();

    $this->postJson('/ai-assistant/prompt', [])->assertUnprocessable()
        ->assertJsonValidationErrors(['prompt']);
});

it('validates prompt max length', function () {
    actingAsUser();

    $this->postJson('/ai-assistant/prompt', ['prompt' => str_repeat('a', 2001)])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['prompt']);
});

it('calls the inventory command agent and returns parsed response', function () {
    actingAsUser();

    $fakeParsed = [
        'action' => 'create_purchase_order',
        'confidence' => 0.95,
        'supplier_id' => null,
        'customer_id' => null,
        'items' => [
            ['product_id' => 1, 'product_name' => 'Milk', 'matched' => true, 'quantity' => 2, 'unit_price' => null],
        ],
        'product' => null,
        'stock_check_ids' => [],
        'unresolved' => [],
    ];

    InventoryCommandAgent::fake([$fakeParsed]);

    $response = $this->postJson('/ai-assistant/prompt', ['prompt' => 'order 2 units of milk']);

    $response->assertSuccessful()
        ->assertJsonStructure(['conversation_id', 'parsed'])
        ->assertJsonPath('parsed.action', 'create_purchase_order');

    InventoryCommandAgent::assertPrompted('order 2 units of milk');
});

// ── Confirm — validation ──────────────────────────────────────────────────────

it('validates confirm action is required', function () {
    actingAsUser();

    $this->postJson('/ai-assistant/confirm', [])->assertUnprocessable()
        ->assertJsonValidationErrors(['action']);
});

it('rejects unknown actions in confirm', function () {
    actingAsUser();

    $this->postJson('/ai-assistant/confirm', ['action' => 'nuke_database'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['action']);
});

// ── Confirm — create_purchase_order ──────────────────────────────────────────

it('creates a draft purchase order on confirm', function () {
    actingAsUser();

    $product = Product::factory()->create();

    $response = $this->postJson('/ai-assistant/confirm', [
        'action' => 'create_purchase_order',
        'supplier_id' => null,
        'items' => [
            ['product_id' => $product->id, 'product_name' => $product->name, 'quantity' => 3, 'unit_price' => 5.00],
        ],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('result.message', fn ($msg) => str_contains($msg, 'purchase order'));

    $this->assertDatabaseHas('purchase_orders', ['status' => PurchaseOrderStatus::Draft->value]);
    $this->assertDatabaseHas('purchase_order_items', ['product_id' => $product->id, 'quantity_ordered' => 3]);
});

it('rejects confirm purchase order with non-existent product', function () {
    actingAsUser();

    $this->postJson('/ai-assistant/confirm', [
        'action' => 'create_purchase_order',
        'items' => [
            ['product_id' => 99999, 'product_name' => 'Ghost Product', 'quantity' => 1, 'unit_price' => null],
        ],
    ])->assertUnprocessable();
});

// ── Confirm — create_sales_order ─────────────────────────────────────────────

it('creates a draft sales order on confirm', function () {
    actingAsUser();

    $product = Product::factory()->create();

    $response = $this->postJson('/ai-assistant/confirm', [
        'action' => 'create_sales_order',
        'customer_id' => null,
        'items' => [
            ['product_id' => $product->id, 'product_name' => $product->name, 'quantity' => 2, 'unit_price' => 10.00],
        ],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('sales_orders', ['status' => SalesOrderStatus::Draft->value]);
    $this->assertDatabaseHas('sales_order_items', ['product_id' => $product->id, 'quantity' => 2]);
});

// ── Confirm — add_product ────────────────────────────────────────────────────

it('creates a new product on confirm add_product', function () {
    actingAsUser();

    $response = $this->postJson('/ai-assistant/confirm', [
        'action' => 'add_product',
        'product' => [
            'name' => 'Coconut Water 330ml',
            'sku' => 'CW330',
            'cost_price' => 1.50,
            'selling_price' => 2.50,
            'unit' => 'piece',
        ],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('products', ['name' => 'Coconut Water 330ml']);
});

// ── Confirm — edit_product ───────────────────────────────────────────────────

it('updates a product on confirm edit_product', function () {
    actingAsUser();

    $product = Product::factory()->create(['name' => 'Old Name', 'selling_price' => 1.00]);

    $response = $this->postJson('/ai-assistant/confirm', [
        'action' => 'edit_product',
        'product' => [
            'id' => $product->id,
            'selling_price' => 3.50,
        ],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('products', ['id' => $product->id, 'selling_price' => 3.50]);
});

// ── Confirm — check_stock ────────────────────────────────────────────────────

it('returns stock levels on confirm check_stock', function () {
    actingAsUser();

    $products = Product::factory()->count(2)->create();

    $response = $this->postJson('/ai-assistant/confirm', [
        'action' => 'check_stock',
        'stock_check_ids' => $products->pluck('id')->toArray(),
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['result' => ['products' => [['id', 'name', 'sku', 'current_stock']]]]);
});

// ── History ───────────────────────────────────────────────────────────────────

it('returns empty messages when no conversation_id given', function () {
    actingAsUser();

    $this->getJson('/ai-assistant/history')
        ->assertSuccessful()
        ->assertJsonPath('messages', []);
});
