# Arquitetura do Sistema - Dealer Management System

## 📐 Visão Geral da Arquitetura Enterprise

### Padrão Arquitetural Principal
**Laravel MVC + Strategy Pattern + Queue Architecture** - Mantém 90% da simplicidade do Laravel MVC enquanto adiciona camadas enterprise para processamento de vendas de alta demanda.

```
┌─────────────────────────────────────────────────────────────┐
│                    Presentation Layer                       │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐    │
│  │   Vue.js 3  │◄──►│  Inertia.js │◄──►│ Controllers │    │
│  │ Components  │    │   Bridge    │    │  (Laravel)  │    │
│  └─────────────┘    └─────────────┘    └─────────────┘    │
└─────────────────────────────────────────────────────────────┘
                                │
┌─────────────────────────────────────────────────────────────┐
│                Enterprise Business Logic Layer              │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐    │
│  │SaleProcessor│    │   Models    │    │Queue Workers│    │
│  │ Interface   │    │ (Eloquent)  │    │ (Async/Sync)│    │
│  │Strategy+Batch│    │+Pessimistic │    │+CircuitBreak│    │
│  └─────────────┘    └─────────────┘    └─────────────┘    │
└─────────────────────────────────────────────────────────────┘
                                │
┌─────────────────────────────────────────────────────────────┐
│              High-Performance Infrastructure                │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐    │
│  │ MySQL 8.0   │    │Redis 7.2+SQS│    │Docker Stack │    │
│  │+Locks+Index │    │LocalStack/AWS│    │4 Services   │    │
│  └─────────────┘    └─────────────┘    └─────────────┘    │
└─────────────────────────────────────────────────────────────┘
```

### 🚀 **Capacidade Enterprise Implementada**
- **Throughput**: 100-1000+ vendas/minuto (configurável)
- **Concorrência**: Zero race conditions (100 usuários testados)
- **Resilência**: Auto-healing + Circuit breaker
- **Monitoramento**: Métricas tempo real + Health checks
└─────────────────────────────────────────────────────────────┘
```

## 🏗️ Componentes Principais

### 1. Frontend Architecture (SPA)

```
┌─────────────────────────────────────────────────────────────┐
│                     Vue.js 3 + Inertia.js                  │
├─────────────────────────────────────────────────────────────┤
│ Pages/                          Components/                 │
│ ├── Dashboard/                  ├── Charts/                 │
│ │   ├── Index.vue              │   ├── SalesChart.vue      │
│ │   └── Analytics.vue          │   └── StockChart.vue      │
│ ├── Sales/                     ├── Forms/                   │
│ │   ├── Index.vue              │   ├── SaleForm.vue        │
│ │   ├── Create.vue             │   └── ProductForm.vue     │
│ │   └── Show.vue               └── UI/                      │
│ └── Products/                      ├── StatusBadge.vue     │
│     ├── Index.vue                  └── DataTable.vue       │
│     └── Manage.vue                                          │
└─────────────────────────────────────────────────────────────┘
```

### 2. Backend Architecture (Laravel MVC + Strategy)

```php
┌─────────────────────────────────────────────────────────────┐
│                    Controllers Layer                        │
│                                                             │
│  SaleController ──► SaleProcessor Interface                 │
│       │                      │                             │
│       │                      ▼                             │
│       │            ┌─────────────────────┐                 │
│       │            │ Environment-based   │                 │
│       │            │    Selection        │                 │
│       │            └─────────────────────┘                 │
│       │                      │                             │
│       │         ┌────────────┴────────────┐                │
│       │         ▼                         ▼                │
│       │  SyncSaleProcessor        QueuedSaleProcessor      │
│       │  (Development)            (Production)             │
│       │         │                         │                │
│       ▼         ▼                         ▼                │
│  Direct DB ── OrderItems ──► ProcessSaleJob ──► SQS       │
│  Processing                                                 │
└─────────────────────────────────────────────────────────────┘
```

### 3. Sales Processing Flow

```
┌─────────────────────────────────────────────────────────────┐
│                   Sales Processing Architecture             │
└─────────────────────────────────────────────────────────────┘

┌─────────────┐    ┌──────────────────┐    ┌─────────────────┐
│  HTTP       │───▶│  SaleController  │───▶│ SaleProcessor   │
│ Request     │    │                  │    │   Interface     │
└─────────────┘    └──────────────────┘    └─────────────────┘
                                                    │
                          ┌─────────────────────────┼─────────────────────────┐
                          ▼                         ▼                         ▼
                 ┌──────────────────┐    ┌──────────────────┐    ┌──────────────────┐
                 │ SyncSaleProcessor│    │QueuedSaleProcessor│    │ FutureSaleProcessor│
                 │ (APP_ENV=local)  │    │(APP_ENV=production)│    │   (Extensible)   │
                 └──────────────────┘    └──────────────────┘    └──────────────────┘
                          │                         │
                          ▼                         ▼
                 ┌──────────────────┐    ┌──────────────────┐
                 │ Direct Database  │    │  ProcessSaleJob  │
                 │   Processing     │    │   (SQS Queue)    │
                 └──────────────────┘    └──────────────────┘
                          │                         │
                          ▼                         ▼
                 ┌──────────────────┐    ┌──────────────────┐
                 │   OrderItems     │    │  Database with   │
                 │  + StockMovement │    │ Pessimistic Lock │
                 └──────────────────┘    └──────────────────┘
```

## 🔄 Data Flow Architecture

### 1. Sale Creation Flow
```
User Request ──► Controller ──► Transaction {
                                    ├─ Create Sale (status: pending)
                                    ├─ Create OrderItems (status: pending)
                                    └─ Dispatch to SaleProcessor
                                }
                                    ├─ Sync: Process immediately
                                    └─ Async: Queue for processing
```

### 2. Queue Processing Flow
```
SQS Queue ──► ProcessSaleJob ──► For each OrderItem {
                                    ├─ lockForUpdate() ProductSku
                                    ├─ Check stock availability
                                    ├─ Create StockMovement
                                    ├─ Update OrderItem status
                                    └─ Release lock
                                }
                                ──► Update Sale status from OrderItems
```

### 3. Recovery Flow
```
Scheduled Job ──► RecoverPendingSalesJob ──► Find orphaned Sales {
                                                ├─ status = 'pending'
                                                ├─ created_at < 10 minutes ago
                                                └─ Dispatch to recovery queue
                                            }
```

## 🗄️ Database Architecture

### Core Tables Structure
```sql
┌─────────────────────────────────────────────────────────────┐
│                    Database Schema                          │
├─────────────────────────────────────────────────────────────┤
│ sales                           order_items                 │
│ ├─ id (PK)                     ├─ id (PK)                   │
│ ├─ client_id (FK)              ├─ sale_id (FK)              │
│ ├─ store_id (FK)               ├─ product_sku_id (FK)       │
│ ├─ status (enum)               ├─ quantity                  │
│ ├─ total_amount                ├─ unit_price                │
│ └─ created_at                  ├─ total_price               │
│                                ├─ status (enum)             │
│                                └─ error_message             │
├─────────────────────────────────────────────────────────────┤
│ stock_movements                 products_skus               │
│ ├─ id (PK)                     ├─ id (PK)                   │
│ ├─ product_sku_id (FK)         ├─ product_id (FK)           │
│ ├─ store_id (FK)               ├─ sku_code                  │
│ ├─ type ('in'/'out')           ├─ price                     │
│ ├─ quantity                    └─ active                    │
│ ├─ reference_type              │                            │
│ ├─ reference_id                │                            │
│ └─ created_at                  │                            │
└─────────────────────────────────────────────────────────────┘
```

### Relationships Map
```
User ──┬─ Sales ──┬─ OrderItems ──► ProductsSku ──► Products
       │          │                     │
       │          └─ StockMovements ─────┘
       │
       └─ Stores ──┬─ StockMovements
                   └─ Sales
```

## 🌐 Queue Architecture

### SQS Queue Structure
```
┌─────────────────────────────────────────────────────────────┐
│                    SQS Queue Architecture                   │
├─────────────────────────────────────────────────────────────┤
│ sales-high-priority     sales-retry         sales-recovery  │
│ ├─ New sales           ├─ Failed sales     ├─ Orphaned sales│
│ ├─ Priority: High      ├─ Priority: Medium ├─ Priority: Low │
│ ├─ Workers: 3          ├─ Workers: 2       ├─ Workers: 1   │
│ └─ Timeout: 300s       └─ Timeout: 300s    └─ Timeout: 600s│
├─────────────────────────────────────────────────────────────┤
│ Dead Letter Queues (DLQ)                                    │
│ ├─ sales-high-priority-dlq                                  │
│ ├─ sales-retry-dlq                                          │
│ └─ sales-recovery-dlq                                       │
└─────────────────────────────────────────────────────────────┘
```

### Worker Configuration
```php
// config/sales.php
'workers' => [
    'sales-high' => [
        'connection' => 'sqs',
        'queue' => 'sales-high-priority',
        'sleep' => 1,
        'tries' => 3,
        'timeout' => 300,
    ],
    // ... other workers
]
```

## 🔒 Security Architecture

### Authentication & Authorization
```
┌─────────────────────────────────────────────────────────────┐
│                   Security Layers                           │
├─────────────────────────────────────────────────────────────┤
│ Laravel Jetstream (2FA + Session Management)                │
│ ├─ Two-Factor Authentication                                │
│ ├─ Session Management                                       │
│ └─ Password Reset Flow                                      │
├─────────────────────────────────────────────────────────────┤
│ Spatie Laravel Permission                                   │
│ ├─ Roles: admin, dealer, user                              │
│ ├─ Permissions: manage_sales, view_reports, etc.           │
│ └─ Multi-tenant isolation by store_id                      │
├─────────────────────────────────────────────────────────────┤
│ Application Level Security                                  │
│ ├─ CSRF Protection (all forms)                             │
│ ├─ Rate Limiting (API endpoints)                           │
│ ├─ SQL Injection Prevention (Eloquent ORM)                 │
│ └─ XSS Protection (Blade escaping + Vue.js)                │
└─────────────────────────────────────────────────────────────┘
```

### Multi-tenant Data Isolation
```php
// Global scope for multi-tenancy
class StoreScope implements Scope {
    public function apply(Builder $builder, Model $model) {
        if (auth()->user()->hasRole('dealer')) {
            $builder->where('store_id', auth()->user()->store_id);
        }
    }
}
```

## 🚀 Deployment Architecture

### Environment Configurations

#### Development (Local)
```
┌─────────────────────────────────────────────────────────────┐
│                   Development Environment                   │
├─────────────────────────────────────────────────────────────┤
│ PHP 8.2 + Laravel 12 (Artisan serve)                       │
│ ├─ SQLite Database                                          │
│ ├─ SyncSaleProcessor (Direct processing)                    │
│ ├─ File-based cache and sessions                           │
│ └─ LocalStack SQS for testing                              │
└─────────────────────────────────────────────────────────────┘
```

#### Production (Docker + AWS)
```
┌─────────────────────────────────────────────────────────────┐
│                   Production Environment                    │
├─────────────────────────────────────────────────────────────┤
│ Load Balancer ──► Multiple PHP-FPM instances               │
│ ├─ MySQL 8.0 (RDS)                                         │
│ ├─ Redis (ElastiCache)                                     │
│ ├─ SQS (AWS Native)                                        │
│ ├─ S3 for file storage                                     │
│ └─ CloudWatch for monitoring                               │
├─────────────────────────────────────────────────────────────┤
│ Queue Workers (Supervisor managed)                         │
│ ├─ 3x sales-high-priority workers                          │
│ ├─ 2x sales-retry workers                                  │
│ └─ 1x sales-recovery worker                                │
└─────────────────────────────────────────────────────────────┘
```

## 🔧 Extension Points

### Adding New Sale Processors
```php
class CustomSaleProcessor implements SaleProcessor {
    public function process(Sale $sale): string {
        // Custom implementation
        // Example: Integration with external ERP
        // Example: Complex approval workflow
        // Example: Multi-step validation
    }
}

// Register in AppServiceProvider
$this->app->bind(SaleProcessor::class, function ($app) {
    return match(config('sales.processor_type')) {
        'sync' => new SyncSaleProcessor(),
        'queued' => new QueuedSaleProcessor(),
        'custom' => new CustomSaleProcessor(),
        default => new QueuedSaleProcessor(),
    };
});
```

### Adding New Queue Types
```php
// config/sales.php
'queues' => [
    'high_priority' => 'sales-high-priority',
    'retry' => 'sales-retry',
    'recovery' => 'sales-recovery',
    'custom_workflow' => 'sales-custom-workflow', // New queue
],
```

---

**Conclusão**: Arquitetura flexível e escalável, projetada para crescer de MVP para enterprise sem refatorações massivas.
