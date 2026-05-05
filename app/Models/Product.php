<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['sku', 'name', 'description', 'cost_price',
    'selling_price', 'unit_of_measure', 'current_stock', 'reorder_level', 'supplier_id', 'category_id'])]
class Product extends Model
{
    use HasFactory, SoftDeletes;
}
