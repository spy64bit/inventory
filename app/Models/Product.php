<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['sku', 'name', 'description', 'cost_price', 'reorder_level', 'supplier_id', 'category_id', 'stock_quantity'])]
class Product extends Model
{
    use HasFactory, SoftDeletes;
}
