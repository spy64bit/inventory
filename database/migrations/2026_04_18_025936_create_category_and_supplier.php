<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('category', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('supplier', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // add foreign key to product table
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable()->after('reorder_level');
            $table->unsignedBigInteger('supplier_id')->nullable()->after('category_id');
            $table->unsignedBigInteger('stock_quantity')->default(0)->after('supplier_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('category_id');
            $table->dropColumn('supplier_id');
            $table->dropColumn('stock_quantity');
        });

        Schema::dropIfExists('category');
        Schema::dropIfExists('supplier');
    }
};
