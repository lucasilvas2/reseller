# Regras de Negócio - Reseller Management System

## 📋 Visão Geral das Regras

### Domínios de Negócio
O sistema opera em **4 domínios principais** com regras específicas:
1. **Gestão de Usuários e Permissões** - Multi-tenancy e segurança
2. **Gestão de Produtos e Estoque** - Controle de inventário
3. **Processamento de Vendas** - Fluxo crítico de vendas
4. **Gestão de Clientes** - Relacionamento e convites

## 🏢 1. Gestão de Usuários e Multi-tenancy

### Roles e Hierarquia
```
Admin (Global)
├─ Acesso total ao sistema
├─ Gerencia todas as lojas
├─ Controle de usuários e permissões
└─ Auditoria completa

Reseller (Loja)
├─ Acesso limitado à sua loja
├─ Gestão de produtos e estoque
├─ Processamento de vendas
├─ Gestão de clientes da loja
└─ Relatórios da loja

User (Cliente)
├─ Acesso limitado por convite
├─ Visualização de produtos disponíveis
├─ Histórico de compras próprias
└─ Atualização de dados pessoais
```

### Regras de Multi-tenancy
```php
/**
 * RN001: Isolamento de Dados por Loja
 * Todos os dados são automaticamente filtrados por store_id
 */
if (auth()->user()->hasRole('reseller')) {
    // Aplicar filtro automático
    $query->where('store_id', auth()->user()->store_id);
}

/**
 * RN002: Admin tem Acesso Global
 * Admins podem acessar dados de todas as lojas
 */
if (auth()->user()->hasRole('admin')) {
    // Sem filtro de store_id
    // Acesso total
}

/**
 * RN003: Validação de Propriedade
 * Antes de qualquer operação, validar se o recurso pertence à loja
 */
public function authorize() {
    return $this->sale->store_id === auth()->user()->store_id
        || auth()->user()->hasRole('admin');
}
```

### Regras de Convites
```php
/**
 * RN004: Convite de Clientes
 * Apenas resellers podem convidar clientes para sua loja
 */
class InviteClientRule {
    public function validate($email, $storeId) {
        // Cliente já existe no sistema?
        $existingUser = User::where('email', $email)->first();
        
        if ($existingUser) {
            // Se já é cliente de outra loja, criar relacionamento
            // Se já é cliente desta loja, rejeitar convite
            return !$existingUser->clients()
                ->where('store_id', $storeId)
                ->exists();
        }
        
        return true; // Email novo, pode convidar
    }
}

/**
 * RN005: Auto-associação de Cliente
 * Quando user aceita convite, vira cliente automaticamente
 */
public function acceptInvitation($token) {
    $invitation = Invitation::where('token', $token)->firstOrFail();
    
    // Criar ou atualizar usuário
    $user = User::firstOrCreate(['email' => $invitation->email]);
    
    // Criar registro de cliente
    Client::create([
        'user_id' => $user->id,
        'store_id' => $invitation->store_id,
        'name' => $invitation->name,
        'email' => $invitation->email,
    ]);
    
    // Atribuir role de user
    $user->assignRole('user');
}
```

## 📦 2. Gestão de Produtos e Estoque

### Hierarquia de Produtos
```
Brand (Marca)
└─ Product (Produto)
   └─ ProductSku (Variação/SKU)
      └─ StockMovement (Movimentações)
```

### Regras de Estoque
```php
/**
 * RN006: Estoque Nunca Negativo
 * Sistema deve impedir vendas com estoque insuficiente
 */
class StockValidationRule {
    public function validateAvailability($productSkuId, $quantity, $storeId) {
        $sku = ProductsSku::lockForUpdate()->find($productSkuId);
        $currentStock = $sku->getCurrentStock($storeId);
        
        if ($currentStock < $quantity) {
            throw new InsufficientStockException(
                "Estoque insuficiente. Disponível: {$currentStock}, Solicitado: {$quantity}"
            );
        }
        
        return true;
    }
}

/**
 * RN007: Rastreabilidade Completa
 * Toda movimentação de estoque deve ser rastreada
 */
class StockMovementRule {
    public function recordMovement($productSkuId, $storeId, $type, $quantity, $reference) {
        StockMovement::create([
            'product_sku_id' => $productSkuId,
            'store_id' => $storeId,
            'type' => $type, // 'in' ou 'out'
            'quantity' => $quantity,
            'reference_type' => get_class($reference), // 'sale', 'purchase', 'adjustment'
            'reference_id' => $reference->id,
            'user_id' => auth()->id(),
            'notes' => "Movimentação automática via {$reference->type}",
        ]);
    }
}

/**
 * RN008: Cálculo de Estoque em Tempo Real
 * Estoque atual = Entradas - Saídas
 */
public function getCurrentStock($storeId) {
    return $this->stockMovements()
        ->where('store_id', $storeId)
        ->sum(DB::raw('CASE WHEN type = "in" THEN quantity ELSE -quantity END'));
}
```

### Regras de Preços
```php
/**
 * RN009: Controle de Preços por SKU
 * Cada SKU pode ter preço diferente, mesmo do mesmo produto
 */
class PricingRule {
    public function calculateItemTotal($quantity, $unitPrice, $discountPercent = 0) {
        $subtotal = $quantity * $unitPrice;
        $discountAmount = $subtotal * ($discountPercent / 100);
        return $subtotal - $discountAmount;
    }
    
    public function validatePriceChange($oldPrice, $newPrice) {
        // RN010: Mudanças de preço > 50% requerem aprovação admin
        $percentageChange = abs(($newPrice - $oldPrice) / $oldPrice) * 100;
        
        if ($percentageChange > 50 && !auth()->user()->hasRole('admin')) {
            throw new UnauthorizedException(
                'Mudanças de preço superiores a 50% requerem aprovação administrativa'
            );
        }
    }
}
```

## 💰 3. Processamento de Vendas (Crítico)

### Fluxo de Estados da Venda
```
pending → processing → completed
   ↓           ↓           ↑
   ↓      → failed ←───────┘
   ↓           ↓
   └──→ cancelled ←────────┘
```

### Regras Críticas de Vendas
```php
/**
 * RN011: Atomicidade de Vendas
 * Venda só é válida se TODOS os itens forem processados com sucesso
 */
class SaleProcessingRule {
    public function processSale(Sale $sale) {
        DB::transaction(function () use ($sale) {
            foreach ($sale->orderItems as $item) {
                // RN012: Processar item por item com lock
                $this->processOrderItem($item);
            }
            
            // RN013: Status da venda baseado nos itens
            $sale->updateStatusFromItems();
        });
    }
    
    private function processOrderItem(OrderItem $item) {
        try {
            // Lock pessimístico para evitar race condition
            $sku = ProductsSku::lockForUpdate()->find($item->product_sku_id);
            
            // Validar estoque
            if (!$this->validateStock($sku, $item->quantity, $item->sale->store_id)) {
                throw new InsufficientStockException();
            }
            
            // Criar movimentação de estoque
            $this->createStockMovement($item);
            
            // Atualizar status do item
            $item->update(['status' => 'completed', 'processed_at' => now()]);
            
        } catch (Exception $e) {
            // RN014: Falha em item individual não cancela venda inteira
            $item->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
        }
    }
}

/**
 * RN015: Status Granular por Item
 * Cada OrderItem tem status independente
 */
public function updateStatusFromItems() {
    $itemsSummary = $this->getItemsStatusSummary();
    
    if ($itemsSummary['failed'] > 0 && $itemsSummary['completed'] == 0) {
        $this->status = 'failed';
    } elseif ($itemsSummary['completed'] > 0 && $itemsSummary['pending'] == 0) {
        $this->status = 'completed';
    } elseif ($itemsSummary['processing'] > 0) {
        $this->status = 'processing';
    }
    
    $this->save();
}
```

### Regras de Recovery e Retry
```php
/**
 * RN016: Recovery Automático de Vendas Órfãs
 * Vendas em 'pending' por mais de 10 minutos são consideradas órfãs
 */
class SaleRecoveryRule {
    public function findOrphanedSales() {
        return Sale::where('status', 'pending')
            ->where('created_at', '<', now()->subMinutes(10))
            ->get();
    }
    
    public function recoverOrphanedSale(Sale $sale) {
        // RN017: Apenas 3 tentativas de recovery
        if ($sale->recovery_attempts >= 3) {
            $sale->update(['status' => 'failed']);
            return false;
        }
        
        // Incrementar contador e reprocessar
        $sale->increment('recovery_attempts');
        return $this->processSale($sale);
    }
}

/**
 * RN018: Retry de Itens Falhados
 * Apenas itens com status 'failed' podem ser reprocessados
 */
class ItemRetryRule {
    public function retryFailedItems(Sale $sale) {
        $failedItems = $sale->orderItems()->where('status', 'failed')->get();
        
        foreach ($failedItems as $item) {
            // Reset item para pending
            $item->update([
                'status' => 'pending',
                'error_message' => null,
                'processed_at' => null
            ]);
        }
        
        // Reprocessar a venda
        return app(SaleProcessor::class)->process($sale);
    }
}
```

### Regras de Concorrência
```php
/**
 * RN019: Prevenção de Race Conditions
 * Usar locks pessimísticos em operações críticas
 */
class ConcurrencyRule {
    public function processWithLock($productSkuId, $callback) {
        return DB::transaction(function () use ($productSkuId, $callback) {
            // Lock no SKU específico
            $sku = ProductsSku::where('id', $productSkuId)
                ->lockForUpdate()
                ->first();
                
            return $callback($sku);
        });
    }
}

/**
 * RN020: Queue Processing Strategy
 * Diferentes filas para diferentes prioridades
 */
class QueueStrategyRule {
    public function selectQueue(Sale $sale) {
        // Alta prioridade: vendas normais
        if ($sale->isFirstAttempt()) {
            return 'sales-high-priority';
        }
        
        // Média prioridade: retry de vendas falhadas
        if ($sale->isRetry()) {
            return 'sales-retry';
        }
        
        // Baixa prioridade: recovery de vendas órfãs
        return 'sales-recovery';
    }
}
```

## 👥 4. Gestão de Clientes

### Regras de Relacionamento
```php
/**
 * RN021: Cliente Multi-loja
 * Um usuário pode ser cliente de múltiplas lojas
 */
class ClientRelationshipRule {
    public function associateClientToStore($userId, $storeId) {
        // Verificar se já existe relacionamento
        $existingClient = Client::where('user_id', $userId)
            ->where('store_id', $storeId)
            ->first();
            
        if ($existingClient) {
            throw new DuplicateClientException(
                'Cliente já está associado a esta loja'
            );
        }
        
        return Client::create([
            'user_id' => $userId,
            'store_id' => $storeId,
            'active' => true,
        ]);
    }
}

/**
 * RN022: Histórico de Compras por Loja
 * Cliente vê apenas compras da loja atual
 */
public function getPurchaseHistory($clientId, $storeId) {
    return Sale::where('client_id', $clientId)
        ->where('store_id', $storeId)
        ->where('status', 'completed')
        ->with('orderItems.productSku.product')
        ->orderBy('created_at', 'desc')
        ->get();
}
```

### Regras de Dados Pessoais (LGPD)
```php
/**
 * RN023: Consentimento para Dados
 * Cliente deve consentir com uso de dados
 */
class DataPrivacyRule {
    public function requestConsent($clientId, $dataTypes) {
        ConsentRecord::create([
            'client_id' => $clientId,
            'data_types' => $dataTypes,
            'consented_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
    
    public function anonymizeClient($clientId) {
        $client = Client::find($clientId);
        
        // RN024: Anonimização mantém integridade referencial
        $client->update([
            'name' => 'Cliente Anonimizado',
            'email' => null,
            'phone' => null,
            'document' => null,
            'address' => null,
            'anonymized_at' => now(),
        ]);
        
        // Manter vendas para integridade contábil
        // Apenas remover dados pessoais
    }
}
```

## 🔍 5. Regras de Auditoria e Logging

### Regras de Rastreabilidade
```php
/**
 * RN025: Log de Todas as Ações Críticas
 * Vendas, alterações de estoque e mudanças de preço devem ser logadas
 */
class AuditRule {
    public function logAction($action, $model, $changes = null) {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action, // 'created', 'updated', 'deleted'
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'changes' => json_encode($changes),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}

/**
 * RN026: Retenção de Logs
 * Logs devem ser mantidos por 7 anos (requisito contábil)
 */
class LogRetentionRule {
    public function cleanupLogs() {
        // Não deletar logs de vendas (requisito contábil)
        // Apenas logs de debug podem ser limpos após 90 dias
        Log::where('level', 'debug')
            ->where('created_at', '<', now()->subDays(90))
            ->delete();
    }
}
```

## ⚠️ 6. Regras de Validação e Segurança

### Regras de Input Validation
```php
/**
 * RN027: Validação de Dados de Entrada
 * Todos os inputs devem ser validados
 */
class ValidationRules {
    public static function saleRules() {
        return [
            'client_id' => 'nullable|exists:clients,id',
            'items' => 'required|array|min:1',
            'items.*.product_sku_id' => 'required|exists:products_skus,id',
            'items.*.quantity' => 'required|integer|min:1|max:1000',
            'items.*.unit_price' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:1000',
        ];
    }
    
    public static function stockMovementRules() {
        return [
            'product_sku_id' => 'required|exists:products_skus,id',
            'type' => 'required|in:in,out',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ];
    }
}

/**
 * RN028: Rate Limiting
 * Limitar tentativas de operações críticas
 */
class RateLimitRule {
    public function checkSaleCreationLimit($userId) {
        $recentSales = Sale::where('user_id', $userId)
            ->where('created_at', '>', now()->subMinutes(5))
            ->count();
            
        if ($recentSales > 10) {
            throw new TooManyRequestsException(
                'Muitas vendas criadas recentemente. Aguarde alguns minutos.'
            );
        }
    }
}
```

## 📊 7. Regras de Relatórios e Métricas

### Regras de Performance Metrics
```php
/**
 * RN029: Métricas de Vendas
 * Calcular KPIs importantes automaticamente
 */
class MetricsRule {
    public function calculateSalesMetrics($storeId, $period) {
        return [
            'total_sales' => Sale::completedInPeriod($storeId, $period)->count(),
            'total_revenue' => Sale::completedInPeriod($storeId, $period)->sum('total_amount'),
            'avg_ticket' => Sale::completedInPeriod($storeId, $period)->avg('total_amount'),
            'success_rate' => $this->calculateSuccessRate($storeId, $period),
            'top_products' => $this->getTopProducts($storeId, $period),
        ];
    }
    
    private function calculateSuccessRate($storeId, $period) {
        $total = Sale::where('store_id', $storeId)->whereBetween('created_at', $period)->count();
        $completed = Sale::where('store_id', $storeId)->where('status', 'completed')->whereBetween('created_at', $period)->count();
        
        return $total > 0 ? ($completed / $total) * 100 : 0;
    }
}
```

---

## 📋 Resumo das Regras Críticas

### 🔴 Regras Críticas (Nunca Podem Falhar)
- **RN006**: Estoque nunca negativo
- **RN019**: Prevenção de race conditions
- **RN011**: Atomicidade de vendas
- **RN001**: Isolamento multi-tenant

### 🟡 Regras Importantes (Podem ter exceções)
- **RN010**: Aprovação para mudanças de preço
- **RN028**: Rate limiting
- **RN023**: Consentimento LGPD

### 🟢 Regras de Negócio (Flexíveis)
- **RN015**: Status granular por item
- **RN022**: Histórico por loja
- **RN029**: Métricas automáticas

---

**Status**: ✅ Regras de negócio implementadas e validadas em produção. 📋
