<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['sku', 'name', 'description', 'cost_price', 'reorder_level'])]
class Product extends Model
{
    use HasFactory;
}
