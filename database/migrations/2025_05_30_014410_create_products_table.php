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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->string('image_url')->nullable();
            $table->string('sku')->unique();
            $table->string('barcode')->nullable();
            $table->decimal('cost_price', 10, 2);
            $table->decimal('sale_price', 10, 2);

            $table->foreignId('brand_id')->nullable()->constrained('brands');
            $table->foreignId('store_id')->nullable()->constrained('stores');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['store_id', 'sku']);
            $table->index(['store_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
