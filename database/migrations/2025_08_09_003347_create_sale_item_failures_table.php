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
        Schema::create('sale_item_failures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales');
            $table->foreignId('order_item_id')->constrained('order_items');
            $table->foreignId('product_id')->constrained('products');
            $table->string('failure_type');
            $table->text('error_message');
            $table->json('error_context')->nullable();
            $table->timestamp('attempted_at');
            $table->integer('attempt_number')->default(1);
            $table->boolean('is_retry')->default(false);
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->foreignId('store_id')->constrained('stores');
            $table->timestamps();
            $table->index(['sale_id', 'is_resolved']);
            $table->index(['store_id', 'attempted_at']);
            $table->index(['failure_type', 'is_resolved']);
            $table->index(['order_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_item_failures');
    }
};
