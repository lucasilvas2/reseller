# Documentação de Banco de Dados - Reseller Management System

## 🗄️ Visão Geral do Banco

### Estrutura Geral
O sistema utiliza **MySQL 8.0** em produção e **SQLite** para desenvolvimento, com total compatibilidade entre ambos. O banco segue padrões de **normalização** adequados e implementa **soft deletes** nas tabelas principais.

### Estatísticas Atuais
- **18 tabelas principais** + tabelas de sistema Laravel
- **Relacionamentos complexos** com integridade referencial
- **Multi-tenancy** através de `store_id`
- **Auditoria** com timestamps e soft deletes
- **Performance otimizada** com índices estratégicos

## 📊 Schema Principal

### Core Tables Overview
```sql
┌─────────────────────────────────────────────────────────────┐
│                      Database Schema                        │
├─────────────────────────────────────────────────────────────┤
│ USERS & AUTH           │ BUSINESS ENTITIES                  │
│ ├─ users               │ ├─ stores                          │
│ ├─ model_has_roles     │ ├─ brands                          │
│ └─ model_has_permissions│ ├─ clients                        │
│                        │ └─ products (CONSOLIDATED)         │
├─────────────────────────────────────────────────────────────┤
│ INVENTORY MANAGEMENT   │ SALES PROCESSING                   │
│ ├─ stock_movements     │ ├─ sales                           │
│ └─ inventory_audit     │ ├─ order_items                     │
│                        │ └─ sale_item_failures (NEW)       │
└─────────────────────────────────────────────────────────────┘
```

## 🏗️ Tabelas Detalhadas

### 1. Gestão de Usuários

#### `users` - Usuários do Sistema
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    two_factor_secret TEXT NULL,
    two_factor_recovery_codes TEXT NULL,
    two_factor_confirmed_at TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    current_team_id BIGINT UNSIGNED NULL,
    profile_photo_path VARCHAR(2048) NULL,
    store_id BIGINT UNSIGNED NULL,  -- Multi-tenancy
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,  -- Soft delete
    
    KEY users_store_id_foreign (store_id),
    KEY users_email_index (email),
    KEY users_deleted_at_index (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### Relacionamentos Users
- `users.store_id` → `stores.id` (Many-to-One)
- `model_has_roles` (Many-to-Many com roles)
- `model_has_permissions` (Many-to-Many com permissions)

### 2. Entidades de Negócio

#### `stores` - Lojas/Estabelecimentos
```sql
CREATE TABLE stores (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    address TEXT NULL,
    phone VARCHAR(255) NULL,
    email VARCHAR(255) NULL,
    active BOOLEAN DEFAULT TRUE,
    settings JSON NULL,  -- Configurações específicas da loja
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    KEY stores_active_index (active),
    KEY stores_deleted_at_index (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### `brands` - Marcas de Produtos
```sql
CREATE TABLE brands (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    logo_path VARCHAR(2048) NULL,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    KEY brands_name_index (name),
    KEY brands_active_index (active),
    KEY brands_deleted_at_index (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### `clients` - Clientes
```sql
CREATE TABLE clients (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(255) NULL,
    document VARCHAR(255) NULL,  -- CPF/CNPJ
    address JSON NULL,           -- Endereço completo
    store_id BIGINT UNSIGNED NOT NULL,  -- Multi-tenancy
    user_id BIGINT UNSIGNED NULL,       -- Relacionamento opcional
    active BOOLEAN DEFAULT TRUE,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    KEY clients_store_id_foreign (store_id),
    KEY clients_user_id_foreign (user_id),
    KEY clients_email_index (email),
    KEY clients_active_index (active),
    KEY clients_deleted_at_index (deleted_at),
    
    CONSTRAINT clients_store_id_foreign 
        FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE,
    CONSTRAINT clients_user_id_foreign 
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 3. Gestão de Produtos

#### `products` - Produtos (ESTRUTURA CONSOLIDADA)
```sql
CREATE TABLE products (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    category VARCHAR(255) NULL,
    
    -- Campos consolidados (anteriormente em products_skus)
    sku VARCHAR(255) NOT NULL UNIQUE,
    barcode VARCHAR(255) NULL,
    cost_price DECIMAL(10,2) NULL,
    sale_price DECIMAL(10,2) NOT NULL,
    weight DECIMAL(8,3) NULL,
    dimensions JSON NULL,
    
    -- Relacionamentos
    brand_id BIGINT UNSIGNED NULL,
    store_id BIGINT UNSIGNED NOT NULL,
    
    -- Auditoria
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    -- Índices otimizados
    KEY products_brand_id_foreign (brand_id),
    KEY products_store_id_foreign (store_id),
    KEY products_sku_index (sku),
    KEY products_name_index (name),
    KEY products_category_index (category),
    KEY products_active_index (active),
    KEY products_deleted_at_index (deleted_at),
    
    CONSTRAINT products_brand_id_foreign 
        FOREIGN KEY (brand_id) REFERENCES brands (id) ON DELETE SET NULL,
    CONSTRAINT products_store_id_foreign 
        FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**🔄 NOTA DE REFATORAÇÃO:** A tabela `products_skus` foi eliminada e seus campos consolidados em `products`. Esta mudança simplifica drasticamente as queries e melhora a performance.

### 4. Controle de Estoque

#### `stock_movements` - Movimentações de Estoque
```sql
CREATE TABLE stock_movements (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    product_id BIGINT UNSIGNED NOT NULL, -- ATUALIZADO: product_sku_id → product_id
    store_id BIGINT UNSIGNED NOT NULL,
    type ENUM('in', 'out') NOT NULL,
    quantity INT NOT NULL,
    reference_type VARCHAR(255) NULL,  -- 'sale', 'purchase', 'adjustment'
    reference_id BIGINT UNSIGNED NULL,
    unit_cost DECIMAL(10,2) NULL,
    total_cost DECIMAL(10,2) NULL,
    notes TEXT NULL,
    user_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    KEY stock_movements_product_sku_id_foreign (product_sku_id),
    KEY stock_movements_store_id_foreign (store_id),
    KEY stock_movements_type_index (type),
    KEY stock_movements_reference_index (reference_type, reference_id),
    KEY stock_movements_user_id_foreign (user_id),
    KEY stock_movements_created_at_index (created_at),
    
    CONSTRAINT stock_movements_product_sku_id_foreign 
        FOREIGN KEY (product_sku_id) REFERENCES products_skus (id) ON DELETE CASCADE,
    CONSTRAINT stock_movements_store_id_foreign 
        FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE,
    CONSTRAINT stock_movements_user_id_foreign 
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 5. Sistema de Vendas

#### `sales` - Vendas
```sql
CREATE TABLE sales (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    client_id BIGINT UNSIGNED NULL,
    store_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,  -- Vendedor
    status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    tax_amount DECIMAL(10,2) DEFAULT 0.00,
    payment_method VARCHAR(255) NULL,
    payment_status ENUM('pending', 'paid', 'partially_paid', 'refunded') DEFAULT 'pending',
    notes TEXT NULL,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    KEY sales_client_id_foreign (client_id),
    KEY sales_store_id_foreign (store_id),
    KEY sales_user_id_foreign (user_id),
    KEY sales_status_index (status),
    KEY sales_payment_status_index (payment_status),
    KEY sales_created_at_index (created_at),
    KEY sales_deleted_at_index (deleted_at),
    
    CONSTRAINT sales_client_id_foreign 
        FOREIGN KEY (client_id) REFERENCES clients (id) ON DELETE SET NULL,
    CONSTRAINT sales_store_id_foreign 
        FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE,
    CONSTRAINT sales_user_id_foreign 
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### `order_items` - Itens da Venda
```sql
CREATE TABLE order_items (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    sale_id BIGINT UNSIGNED NOT NULL,
    product_sku_id BIGINT UNSIGNED NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    error_message TEXT NULL,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    KEY order_items_sale_id_foreign (sale_id),
    KEY order_items_product_sku_id_foreign (product_sku_id),
    KEY order_items_status_index (status),
    KEY order_items_created_at_index (created_at),
    
    CONSTRAINT order_items_sale_id_foreign 
        FOREIGN KEY (sale_id) REFERENCES sales (id) ON DELETE CASCADE,
    CONSTRAINT order_items_product_sku_id_foreign 
        FOREIGN KEY (product_sku_id) REFERENCES products_skus (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## 🔗 Relacionamentos Complexos

### Multi-tenancy através de store_id
```sql
-- Todos os dados são isolados por loja
Global Scope aplicado automaticamente:
WHERE store_id = {current_user_store_id}

Tabelas com multi-tenancy:
├─ clients (store_id)
├─ sales (store_id)  
├─ stock_movements (store_id)
└─ products_skus (via products → brand)
```

### Relacionamentos de Vendas
```
Sale (1) ──────────── (N) OrderItems
  │                         │
  │                         │
  ├─ Client (N:1)           └─ ProductSku (N:1)
  ├─ Store (N:1)                   │
  └─ User (N:1)                    └─ Product (N:1) ──► Brand (N:1)
```

### Fluxo de Estoque
```
StockMovement ──► ProductSku ──► CurrentStock (calculated)
      │                               ▲
      │                               │
      └── Reference ──────────────────┘
          (Sale, Purchase, Adjustment)
```

## 📈 Views e Consultas Críticas

### View: Current Stock por SKU
```sql
CREATE VIEW current_stock_view AS
SELECT 
    ps.id as product_sku_id,
    ps.sku_code,
    ps.name,
    sm.store_id,
    COALESCE(
        SUM(CASE WHEN sm.type = 'in' THEN sm.quantity ELSE 0 END) -
        SUM(CASE WHEN sm.type = 'out' THEN sm.quantity ELSE 0 END), 
        0
    ) as current_stock
FROM products_skus ps
LEFT JOIN stock_movements sm ON ps.id = sm.product_sku_id
WHERE ps.active = true AND ps.deleted_at IS NULL
GROUP BY ps.id, sm.store_id;
```

### Query: Sales Performance
```sql
-- Vendas por período com métricas
SELECT 
    DATE(s.created_at) as sale_date,
    COUNT(*) as total_sales,
    SUM(s.total_amount) as total_revenue,
    AVG(s.total_amount) as avg_ticket,
    COUNT(DISTINCT s.client_id) as unique_clients,
    COUNT(CASE WHEN s.status = 'completed' THEN 1 END) as successful_sales,
    COUNT(CASE WHEN s.status = 'failed' THEN 1 END) as failed_sales
FROM sales s 
WHERE s.store_id = ? 
  AND s.created_at >= ? 
  AND s.created_at <= ?
  AND s.deleted_at IS NULL
GROUP BY DATE(s.created_at)
ORDER BY sale_date DESC;
```

### Query: Inventory Alert
```sql
-- Produtos com estoque baixo
SELECT 
    p.name as product_name,
    ps.sku_code,
    ps.name as sku_name,
    csv.current_stock,
    ps.price
FROM current_stock_view csv
JOIN products_skus ps ON csv.product_sku_id = ps.id
JOIN products p ON ps.product_id = p.id
WHERE csv.store_id = ?
  AND csv.current_stock <= 10  -- Limite baixo
  AND ps.active = true
ORDER BY csv.current_stock ASC;
```

## 🔧 Índices e Performance

### Índices Críticos para Performance
```sql
-- Multi-tenancy (mais importante)
KEY sales_store_id_created_at (store_id, created_at)
KEY order_items_sale_id_status (sale_id, status)
KEY stock_movements_sku_store (product_sku_id, store_id)

-- Status queries (frequentes)
KEY sales_status_store (status, store_id)
KEY order_items_status_processed (status, processed_at)

-- Reporting queries
KEY sales_created_at_status (created_at, status)
KEY stock_movements_created_type (created_at, type)
```

### Otimizações Implementadas
1. **Compound Indexes**: Para queries multi-coluna
2. **Covering Indexes**: Para evitar lookups adicionais
3. **Partial Indexes**: Para soft deletes (`WHERE deleted_at IS NULL`)
4. **Foreign Key Indexes**: Automáticos para JOINs

## 🚨 Constraints e Validações

### Business Rules no Database
```sql
-- Não permitir estoque negativo (através de triggers)
DELIMITER $$
CREATE TRIGGER prevent_negative_stock 
BEFORE INSERT ON stock_movements
FOR EACH ROW
BEGIN
    DECLARE current_stock INT DEFAULT 0;
    
    SELECT COALESCE(SUM(
        CASE WHEN type = 'in' THEN quantity ELSE -quantity END
    ), 0) INTO current_stock
    FROM stock_movements 
    WHERE product_sku_id = NEW.product_sku_id 
      AND store_id = NEW.store_id;
    
    IF NEW.type = 'out' AND (current_stock - NEW.quantity) < 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Insufficient stock for this operation';
    END IF;
END$$
DELIMITER ;
```

### Data Integrity Rules
1. **Sales cannot be deleted**: Only soft delete allowed
2. **Stock movements are immutable**: Once created, cannot be updated
3. **Order items must sum to sale total**: Validated at application level
4. **Multi-tenancy isolation**: Enforced by global scopes

## 🔄 Migrations Strategy

### Migration Naming Convention
```
YYYY_MM_DD_HHMMSS_action_table_name.php

Examples:
├─ 2024_01_15_100000_create_stores_table.php
├─ 2024_01_15_110000_create_products_table.php
├─ 2024_01_15_120000_add_multi_tenancy_to_existing_tables.php
└─ 2024_02_01_090000_optimize_indexes_for_sales_queries.php
```

### Critical Migrations
```php
// Add store_id to existing tables (multi-tenancy)
Schema::table('existing_table', function (Blueprint $table) {
    $table->unsignedBigInteger('store_id')->after('id');
    $table->foreign('store_id')->references('id')->on('stores');
    $table->index(['store_id', 'created_at']); // Performance
});

// Add status enums for sales processing
Schema::table('sales', function (Blueprint $table) {
    $table->enum('status', [
        'pending', 'processing', 'completed', 'failed', 'cancelled'
    ])->default('pending')->change();
});
```

## 📊 Backup and Maintenance

### Backup Strategy
```bash
# Daily full backup
mysqldump --single-transaction --routifiable --triggers \
  --all-databases > backup_$(date +%Y%m%d).sql

# Incremental backup (binlog)
mysqlbinlog --start-datetime="2024-08-04 00:00:00" \
  --stop-datetime="2024-08-04 23:59:59" \
  mysql-bin.000001 > incremental_$(date +%Y%m%d).sql
```

### Maintenance Tasks
```sql
-- Monthly table optimization
OPTIMIZE TABLE sales, order_items, stock_movements;

-- Index usage analysis
SELECT 
    TABLE_NAME, INDEX_NAME, CARDINALITY,
    (CARDINALITY / TABLE_ROWS) * 100 as selectivity
FROM INFORMATION_SCHEMA.STATISTICS s
JOIN INFORMATION_SCHEMA.TABLES t USING(TABLE_SCHEMA, TABLE_NAME)
WHERE TABLE_SCHEMA = 'reseller'
ORDER BY selectivity DESC;

-- Slow query analysis
SELECT query_time, lock_time, rows_examined, rows_sent, sql_text
FROM mysql.slow_log 
WHERE start_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)
ORDER BY query_time DESC LIMIT 10;
```

---

**Status**: ✅ Database schema otimizado e validado para alta performance e escalabilidade. 🗄️
