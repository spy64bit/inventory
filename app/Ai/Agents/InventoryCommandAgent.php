<?php

namespace App\Ai\Agents;

use App\Models\Product;
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

Rules:
1. Match product names from user input to the product list above using fuzzy/partial matching.
2. If a product is matched, set matched=true and fill in product_id with the correct id.
3. If a product cannot be matched, set matched=false, product_id=null, and add the product name to "unresolved".
4. For quantities, default to 1 if not specified.
5. For prices, leave null if not mentioned by the user.
6. For supplier_id and customer_id, leave null unless the user explicitly identifies one by id.
7. Set confidence between 0 and 1 based on how certain you are about the interpretation.
8. For check_stock, put the matched product ids in stock_check_ids.
9. For add_product and edit_product, fill the "product" object. For edit_product, set product.id if mentioned.
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
            'customer_id' => $schema->integer()->nullable(),
            'items' => $schema->array()->items(
                $schema->object([
                    'product_id' => $schema->integer()->nullable(),
                    'product_name' => $schema->string()->required(),
                    'matched' => $schema->boolean()->required(),
                    'quantity' => $schema->integer()->required(),
                    'unit_price' => $schema->number()->nullable(),
                ])
            )->required(),
            'product' => $schema->object([
                'id' => $schema->integer()->nullable(),
                'name' => $schema->string()->nullable(),
                'sku' => $schema->string()->nullable(),
                'cost_price' => $schema->number()->nullable(),
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
