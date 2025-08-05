<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Primeiro, vamos alterar as vendas existentes que estão 'paid' para 'completed'
        DB::table('sales')->where('status', 'paid')->update(['status' => 'pending']);

        // Agora alterar o enum para incluir os novos status
        DB::statement("ALTER TABLE sales MODIFY COLUMN status ENUM('pending', 'processing', 'completed', 'failed', 'canceled') NOT NULL");

        // Atualizar vendas que devem ser 'completed' baseadas nos order_items
        DB::statement("
            UPDATE sales s
            SET status = 'completed'
            WHERE EXISTS (
                SELECT 1 FROM order_items oi
                WHERE oi.sale_id = s.id
                AND oi.status = 'completed'
            )
            AND NOT EXISTS (
                SELECT 1 FROM order_items oi2
                WHERE oi2.sale_id = s.id
                AND oi2.status IN ('pending', 'processing', 'failed')
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Voltar ao enum original
        DB::statement("ALTER TABLE sales MODIFY COLUMN status ENUM('pending', 'paid', 'canceled') NOT NULL");

        // Reverter completed para paid
        DB::table('sales')->where('status', 'completed')->update(['status' => 'paid']);
    }
};
