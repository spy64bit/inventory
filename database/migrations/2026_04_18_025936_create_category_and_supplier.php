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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('parent_id')->nullable(); // subcategories
            $table->foreign('parent_id')->references('id')->on('categories')->onDelete('set null');
            $table->string('slug')->unique(); // for filtering/URLs
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->unsignedInteger('lead_time_days')->default(0); // critical for reorder logic
            $table->timestamps();
            $table->softDeletes();
        });

        // add foreign key to product table
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable()->after('reorder_level');
            $table->unsignedBigInteger('supplier_id')->nullable()->after('category_id');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['supplier_id']);
            $table->dropColumn('category_id');
            $table->dropColumn('supplier_id');
        });

        Schema::dropIfExists('categories');
        Schema::dropIfExists('suppliers');
    }
};
