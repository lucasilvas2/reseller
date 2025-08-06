# Guia de Contribuição - Dealer Management System

## 🤝 Como Contribuir

### Bem-vindo ao Projeto!
Agradecemos seu interesse em contribuir com o **Dealer Management System**. Este é um projeto Laravel 12 focado em gestão de vendas com processamento assíncrono e arquitetura robusta.

## 🏗️ Preparação do Ambiente

### Pré-requisitos
- **PHP 8.2+** com extensões: `pdo_sqlite`, `pdo_mysql`, `gd`, `curl`, `mbstring`, `xml`, `zip`
- **Composer 2.6+**
- **Node.js 18+** com **npm 9+**
- **Git** configurado
- **Docker** (opcional - para ambiente completo)

### Setup de Desenvolvimento
```bash
# 1. Fork e clone
git clone https://github.com/your-username/dealer.git
cd dealer

# 2. Instalar dependências
composer install
npm install

# 3. Configurar ambiente
cp .env.example .env.dev
php artisan key:generate

# 4. Database e assets
touch database/database.sqlite
php artisan migrate --seed
npm run dev

# 5. Executar
php artisan serve
```

### Configuração Recomendada (.env.dev)
```env
APP_ENV=local
APP_DEBUG=true
DB_CONNECTION=sqlite
QUEUE_CONNECTION=sync
CACHE_STORE=file
SESSION_DRIVER=file
MAIL_MAILER=log
```

## 🎯 Áreas de Contribuição

### 🔥 Prioridade Alta (Contribuições Mais Valiosas)
1. **Sales Processing System**
   - Otimizações de performance em locks
   - Novos processadores (integração ERP, workflows personalizados)
   - Melhorias no sistema de recovery

2. **Queue Management**
   - Implementação de Laravel Horizon
   - Métricas de performance de filas
   - Auto-scaling de workers

3. **Frontend Dashboard**
   - Componentes Vue.js para recovery manual
   - Dashboards de monitoramento em tempo real
   - Interface para análise de vendas falhadas

### 🟡 Prioridade Média
1. **API REST Completa**
   - Endpoints para integração externa
   - Documentação OpenAPI/Swagger
   - Rate limiting avançado

2. **Relatórios e Analytics**
   - Exportação PDF/Excel
   - Gráficos avançados com ECharts
   - Dashboards customizáveis

3. **Sistema de Notificações**
   - Notificações push em tempo real
   - Email templates personalizáveis
   - Integration com Slack/Discord

### 🟢 Prioridade Baixa (Boas para Iniciantes)
1. **Documentação**
   - Tutoriais de setup
   - Exemplos de uso
   - Tradução de documentos

2. **Testes**
   - Testes de integração
   - Testes de interface (Dusk)
   - Testes de carga

3. **DevX Improvements**
   - Docker compose otimizations
   - CLI commands úteis
   - Code quality tools

## 🌟 Guidelines de Código

### Padrões Laravel
Seguimos rigorosamente os **Laravel Best Practices**:

```php
// ✅ BOM: Controller simples, delegando lógica
class SaleController extends Controller {
    public function store(StoreSaleRequest $request, SaleProcessor $processor) {
        $sale = Sale::create($request->validated());
        $status = $processor->process($sale);
        
        return redirect()->route('sales.show', $sale)
            ->with('success', 'Venda criada com sucesso!');
    }
}

// ❌ RUIM: Lógica complexa no controller
class SaleController extends Controller {
    public function store(Request $request) {
        // 50 linhas de lógica de negócio aqui...
        // Validação manual
        // Processamento direto
        // Etc.
    }
}
```

### Padrões de Arquitetura

#### Strategy Pattern (Sales Processing)
```php
// ✅ Implementar nova estratégia
class CustomSaleProcessor implements SaleProcessor {
    public function process(Sale $sale): string {
        // Sua implementação personalizada
        return 'completed';
    }
    
    public function retry(int $saleId): bool {
        // Sua lógica de retry
        return true;
    }
    
    public function recoverOrphanedSales(): array {
        // Sua lógica de recovery
        return [];
    }
}

// Registrar no AppServiceProvider
$this->app->bind(SaleProcessor::class, function ($app) {
    return match(config('sales.processor_type')) {
        'custom' => new CustomSaleProcessor(),
        'sync' => new SyncSaleProcessor(),
        default => new QueuedSaleProcessor(),
    };
});
```

#### Multi-tenancy Pattern
```php
// ✅ Sempre aplicar filtro de loja
class ProductController extends Controller {
    public function index() {
        $products = Product::when(
            auth()->user()->hasRole('dealer'),
            fn($query) => $query->whereHas('brand.store', 
                fn($q) => $q->where('id', auth()->user()->store_id)
            )
        )->get();
        
        return inertia('Products/Index', compact('products'));
    }
}

// ❌ Esquecer filtro de multi-tenancy
class ProductController extends Controller {
    public function index() {
        $products = Product::all(); // ❌ Vaza dados entre lojas!
        return inertia('Products/Index', compact('products'));
    }
}
```

### Naming Conventions

#### Classes
```php
// Controllers
SaleController, ProductController, ClientController

// Models
Sale, Product, OrderItem, StockMovement

// Jobs
ProcessSaleJob, RecoverPendingSalesJob

// Services
SyncSaleProcessor, QueuedSaleProcessor

// Requests
StoreSaleRequest, UpdateProductRequest

// Resources
SaleResource, ProductCollection
```

#### Database
```sql
-- Tabelas: snake_case plural
sales, order_items, stock_movements, products_skus

-- Colunas: snake_case
created_at, product_sku_id, store_id, total_amount

-- Indexes: table_column_index
sales_store_id_index, order_items_status_index
```

#### Frontend (Vue.js)
```javascript
// Componentes: PascalCase
SaleForm.vue, ProductCard.vue, StatusBadge.vue

// Props: camelCase
productData, isLoading, canEdit

// Methods: camelCase
submitForm(), validateInput(), fetchData()
```

## 🧪 Testes e Qualidade

### Executar Testes
```bash
# Todos os testes
php artisan test

# Testes específicos
php artisan test --filter SaleTest
php artisan test tests/Feature/SaleProcessingTest.php

# Testes de concorrência (importantes!)
php artisan test:sales-concurrency --users=50 --quantity=2
```

### Tipos de Testes Requeridos

#### Unit Tests
```php
// Testar regras de negócio
class SaleTest extends TestCase {
    /** @test */
    public function sale_updates_status_from_items() {
        $sale = Sale::factory()->create(['status' => 'pending']);
        
        // Criar itens com diferentes status
        $sale->orderItems()->create(['status' => 'completed']);
        $sale->orderItems()->create(['status' => 'failed']);
        
        $sale->updateStatusFromItems();
        
        $this->assertEquals('processing', $sale->fresh()->status);
    }
}
```

#### Feature Tests
```php
// Testar fluxos completos
class SaleProcessingTest extends TestCase {
    /** @test */
    public function can_process_sale_synchronously() {
        $this->app->bind(SaleProcessor::class, SyncSaleProcessor::class);
        
        $response = $this->post('/sales', [
            'client_id' => Client::factory()->create()->id,
            'items' => [
                [
                    'product_sku_id' => ProductsSku::factory()->create()->id,
                    'quantity' => 2,
                    'unit_price' => 100.00
                ]
            ]
        ]);
        
        $response->assertStatus(302);
        $this->assertDatabaseHas('sales', ['status' => 'completed']);
    }
}
```

#### Integration Tests
```php
// Testar com SQS LocalStack
class QueueProcessingTest extends TestCase {
    /** @test */
    public function processes_sale_through_sqs_queue() {
        // Setup SQS LocalStack
        $this->artisan('sqs:setup', ['--create' => true]);
        
        $sale = Sale::factory()->create();
        
        // Dispatch to queue
        app(QueuedSaleProcessor::class)->process($sale);
        
        // Process queue
        $this->artisan('queue:work', ['--once' => true]);
        
        $this->assertEquals('completed', $sale->fresh()->status);
    }
}
```

### Code Quality Tools
```bash
# PHP CS Fixer (Laravel Pint)
./vendor/bin/pint

# PHPStan (análise estática)
./vendor/bin/phpstan analyse

# Pest (testes mais expressivos - opcional)
./vendor/bin/pest
```

## 🔄 Workflow de Contribuição

### 1. Issue First
Antes de qualquer contribuição, **crie ou comente em uma issue** explicando:
- O problema que está resolvendo
- A abordagem que pretende usar
- Como isso se alinha com a arquitetura existente

### 2. Branch Strategy
```bash
# Para features
git checkout -b feature/improve-sales-performance

# Para bugs
git checkout -b bugfix/fix-race-condition-in-stock

# Para documentação
git checkout -b docs/update-api-documentation
```

### 3. Commits Semânticos
```bash
# Features
git commit -m "feat(sales): add Redis distributed locks for better performance"

# Bug fixes
git commit -m "fix(stock): prevent race condition in concurrent sales"

# Documentation
git commit -m "docs(api): add OpenAPI specification for sales endpoints"

# Tests
git commit -m "test(sales): add integration tests for queue processing"

# Refactor
git commit -m "refactor(queue): extract queue configuration to dedicated service"
```

### 4. Pull Request Template
```markdown
## 📋 Descrição
Brief description of changes

## 🎯 Tipo de Mudança
- [ ] Bug fix
- [ ] Nova feature
- [ ] Breaking change
- [ ] Documentação

## 🧪 Como Testar
1. Step by step instructions
2. Expected results
3. Edge cases covered

## 📝 Checklist
- [ ] Testes passando (`php artisan test`)
- [ ] Code style ok (`./vendor/bin/pint`)
- [ ] Documentação atualizada
- [ ] Breaking changes documentadas
- [ ] Multi-tenancy considerado
- [ ] Performance testada

## 🔗 Issues Relacionadas
Closes #123
```

## 🎨 Frontend Contributions

### Vue.js + Inertia.js Pattern
```vue
<!-- ✅ Componente bem estruturado -->
<template>
    <AppLayout title="Vendas">
        <div class="py-6">
            <SaleForm 
                :products="products"
                :clients="clients"
                @submit="createSale"
                @error="handleError"
            />
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import SaleForm from '@/Components/SaleForm.vue'

// Props from controller
const props = defineProps({
    products: Array,
    clients: Array,
})

// Reactive state
const isLoading = ref(false)

// Methods
const createSale = (formData) => {
    isLoading.value = true
    
    router.post('/sales', formData, {
        onSuccess: () => {
            // Handle success
        },
        onError: (errors) => {
            handleError(errors)
        },
        onFinish: () => {
            isLoading.value = false
        }
    })
}

const handleError = (errors) => {
    // Error handling logic
}
</script>
```

### Tailwind CSS Patterns
```vue
<!-- ✅ Classes consistentes -->
<div class="bg-white shadow rounded-lg p-6">
    <h2 class="text-lg font-medium text-gray-900 mb-4">
        Título da Seção
    </h2>
    
    <button 
        class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors"
        :class="{ 'opacity-50 cursor-not-allowed': isLoading }"
        :disabled="isLoading"
    >
        Salvar
    </button>
</div>
```

## 🚀 Deploy e CI/CD

### GitHub Actions (se contribuindo para CI)
```yaml
name: Tests and Deploy

on:
  pull_request:
    branches: [main]

jobs:
  tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2
          extensions: pdo_sqlite, pdo_mysql
      
      - name: Install dependencies  
        run: |
          composer install --no-dev --optimize-autoloader
          npm install
      
      - name: Run tests
        run: |
          php artisan test
          npm run build
      
      - name: Test concurrency
        run: php artisan test:sales-concurrency --users=20
```

## 📋 Checklist de Contribuição

### ✅ Antes de Submeter PR
- [ ] **Issue criada/comentada** com contexto
- [ ] **Branch** seguindo naming convention
- [ ] **Testes** criados e passando
- [ ] **Code style** validado (`./vendor/bin/pint`)
- [ ] **Multi-tenancy** considerado em novas features
- [ ] **Performance** testada se relevante
- [ ] **Documentação** atualizada se necessário

### ✅ Especial para Sales Processing
- [ ] **Race conditions** consideradas
- [ ] **Locks pessimísticos** implementados quando necessário
- [ ] **Testes de concorrência** criados
- [ ] **Rollback** strategy definida para falhas
- [ ] **Queue behavior** testado

### ✅ Especial para Frontend
- [ ] **Responsividade** testada
- [ ] **Acessibilidade** básica considerada
- [ ] **Loading states** implementados
- [ ] **Error handling** robusto
- [ ] **Multi-tenancy** no frontend respeitado

## 🏆 Reconhecimento

### Hall da Fama
Contributors que fizeram contribuições significativas:
- [@seu-username] - Implementação inicial do sistema de sales processing
- [@contributor] - Sistema de recovery automático
- [@another] - Dashboard de monitoramento

### Como Ser Reconhecido
1. **Consistência**: Contribuições regulares e bem feitas
2. **Qualidade**: Code review positivos e testes sólidos
3. **Mentoria**: Ajudar outros contributors
4. **Documentação**: Melhorias na documentação do projeto

## 💬 Comunicação

### Canais
- **Issues GitHub**: Para bugs e feature requests
- **Discussions**: Para perguntas e ideias
- **Pull Requests**: Para code review
- **Email**: Para questões sensíveis

### Etiqueta
- **Seja respeitoso** e construtivo
- **Explique o contexto** nas suas contribuições
- **Documente decisões** importantes
- **Ajude outros contributors** quando possível

---

## 🎉 Obrigado!

Toda contribuição, por menor que seja, é valiosa. Seja você:
- 🐛 **Reportando bugs**
- 💡 **Sugerindo melhorias**  
- 📝 **Melhorando documentação**
- 🔧 **Contribuindo com código**
- 🧪 **Criando testes**

**Você está ajudando a construir um sistema mais robusto e confiável!**

---

**Status**: ✅ Guia completo para contribuições produtivas e alinhadas com a arquitetura. 🤝
