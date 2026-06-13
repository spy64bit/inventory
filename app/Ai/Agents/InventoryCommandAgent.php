<?php

namespace App\Ai\Agents;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class InventoryCommandAgent implements Agent, Conversational, HasStructuredOutput
{
    use Promptable, RemembersConversations;

    private string $productList;

    private string $customerList;

    private string $supplierList;

    public function model(): string
    {
        return config('ai.model', 'gemini-2.0-flash');
    }

    public function __construct()
    {
        $this->productList = Product::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($p) => "{$p->id}|{$p->name}")
            ->implode("\n");

        $this->customerList = Customer::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($c) => "{$c->id}|{$c->name}")
            ->implode("\n");

        $this->supplierList = Supplier::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($s) => "{$s->id}|{$s->name}")
            ->implode("\n");
    }

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<PROMPT
You are an inventory management assistant. Parse the user's natural language command and return structured JSON describing the intended action.

Supported actions:
- create_purchase_order: Create a draft purchase order with items
- create_sales_order: Create a draft sales order with items
- add_product: Create a new product record
- edit_product: Update fields on an existing product
- check_stock: Return current stock levels for one or more products
- unknown: Command not understood

Available products (format: id|name):
{$this->productList}

Available customers (format: id|name):
{$this->customerList}

Available suppliers (format: id|name):
{$this->supplierList}

Rules:
1. Match product names from user input to the product list above using fuzzy/partial matching.
2. If a product is matched, set matched=true and fill in product_id with the correct id.
3. If a product cannot be matched, set matched=false, product_id=null, and add the product name to "unresolved".
4. For quantities, default to 1 if not specified.
5. For prices, use the following field mapping — leave a field null only when that specific price is not mentioned at all:
   - items[].unit_price: the per-unit purchase or sale price within an order line. Leave null if not stated.
   - product.cost_price: what the business pays to acquire or produce the product. Set this field when the user says "cost price", "purchase price", "cost", "buying price", or similar. This is NOT the same as selling price.
   - product.selling_price: what the business charges customers. Set this field when the user says "selling price", "sale price", "retail price", or "price" in the context of defining a product. This is NOT the same as cost price.
   - Never copy the same numeric value into both product.cost_price and product.selling_price unless the user explicitly states they are equal.
6. For supplier (in purchase orders) and customer (in sales orders), apply fuzzy/partial name matching against the provided lists (same approach as rules 1–3 for products):
   - If matched: set supplier_id/customer_id to the matched id; set supplier_matched/customer_matched=true; set supplier_name/customer_name=null.
   - If mentioned but not matched: set supplier_matched/customer_matched=false; set supplier_id/customer_id=null; set supplier_name/customer_name to the exact name the user mentioned.
   - If not mentioned at all: leave supplier_id/customer_id=null; set supplier_matched/customer_matched=null; set supplier_name/customer_name=null.
7. Set confidence between 0 and 1 based on how certain you are about the interpretation.
8. For check_stock, put the matched product ids in stock_check_ids.
9. For add_product and edit_product, fill the "product" object. For edit_product, set product.id if mentioned. Apply rule 5 carefully: "cost price 10, selling price 15" must produce product.cost_price=10 AND product.selling_price=15 as two distinct values.
10. A single message may contain multiple distinct intents (e.g. "create a purchase order AND add a new product"). Return each intent as a separate entry in the "commands" array. If only one intent is detected, return a "commands" array with a single entry.
PROMPT;
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        $command = $schema->object([
            'action' => $schema->string()
                ->enum(['create_purchase_order', 'create_sales_order', 'add_product', 'edit_product', 'check_stock', 'unknown'])
                ->required(),
            'confidence' => $schema->number()->required(),
            'supplier_id' => $schema->integer()->nullable(),
            'supplier_matched' => $schema->boolean()->nullable(),
            'supplier_name' => $schema->string()->nullable(),
            'customer_id' => $schema->integer()->nullable(),
            'customer_matched' => $schema->boolean()->nullable(),
            'customer_name' => $schema->string()->nullable(),
            'items' => $schema->array()->items(
                $schema->object([
                    'product_id' => $schema->integer()->nullable(),
                    'product_name' => $schema->string()->required(),
                    'matched' => $schema->boolean()->required(),
                    'quantity' => $schema->integer()->min(1)->required(),
                    'unit_price' => $schema->number()->nullable(),
                ])
            )->required(),
            'product' => $schema->object([
                'id' => $schema->integer()->nullable(),
                'name' => $schema->string()->nullable(),
                'sku' => $schema->string()->nullable(),
                'cost_price' => $schema->number()->nullable()->required(),
                'selling_price' => $schema->number()->nullable(),
                'category_id' => $schema->integer()->nullable(),
                'unit' => $schema->string()->nullable(),
            ])->nullable(),
            'stock_check_ids' => $schema->array()->items($schema->integer())->required(),
            'unresolved' => $schema->array()->items($schema->string())->required(),
        ]);

        return [
            'commands' => $schema->array()->items($command)->required(),
        ];
    }
}
