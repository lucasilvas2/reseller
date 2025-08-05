<?php

namespace App\Contracts;

use App\Enums\SaleEnum;
use App\Models\Sale;

interface SaleProcessor
{
    /**
     * Processar uma venda
     *
     * @param Sale $sale
     * @return string Status da venda após processamento
     */
    public function process(Sale $sale): string;

    /**
     * Tentar novamente uma venda falhada
     *
     * @param int $saleId
     * @return bool
     */

    public function retry(int $saleId): bool;
    /**
     * Recuperar vendas órfãs (opcional - só para implementações async)
     *
     * @return array<int> Array de sale IDs recuperados
     */
    public function recoverOrphanedSales(): array;
}
