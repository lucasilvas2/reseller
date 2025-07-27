# Sistema de Gestão para Lojas

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

> **Status do Projeto**: Em desenvolvimento ativo  
> **Branch atual**: `feature/create-pages-stock`  
> **Funcionalidade em foco**: Sistema completo de gestão de estoque

## Sobre o Projeto

Este é um sistema completo de gestão para lojas, desenvolvido para facilitar o controle de estoque, produtos, clientes e movimentações. O sistema oferece diferentes níveis de acesso para administradores, lojistas e usuários finais.

### Funcionalidades Principais

- **Gestão de Produtos**: Cadastro e controle de produtos com SKUs, preços e categorias
- **Controle de Estoque**: Movimentações de entrada e saída com rastreamento completo
- **Dashboard Analítico**: Gráficos e métricas de desempenho em tempo real
- **Gestão de Clientes**: Sistema de convites e controle de acesso por loja
- **Sistema Multi-tenant**: Isolamento de dados por loja
- **Controle de Permissões**: Sistema robusto de roles e permissões
- **Área Administrativa**: Painel completo para gerenciamento do sistema

## Tecnologias Utilizadas

### Stack Principal
- **Laravel 12.0** - Framework PHP
- **PHP 8.2+** - Backend
- **Vue.js 3** - Frontend SPA
- **Inertia.js** - Conexão Laravel + Vue
- **MySQL 8.0** - Banco de dados (produção)
- **SQLite** - Banco de dados (desenvolvimento)

### Interface e Gráficos
- **Tailwind CSS** - Framework CSS
- **ECharts** - Biblioteca de gráficos
- **Vite** - Build tool

### Infraestrutura
- **Docker & Docker Compose** - Containerização
- **Elasticsearch** - Busca avançada
- **LocalStack** - Simulação AWS S3
- **Redis** - Cache (opcional)

### Ferramentas
- **Spatie Laravel Permission** - Sistema de permissões
- **PHPUnit** - Testes

## Estrutura do Sistema

### Tipos de Usuário

1. **Admin**: Acesso completo ao sistema
   - Gerenciamento de usuários, lojas e marcas
   - Controle total de permissões
   - Auditoria de ações administrativas

2. **Dealer**: Gestores de lojas
   - Gestão de produtos e estoque da loja
   - Controle de clientes e movimentações
   - Dashboard com métricas da loja

3. **User**: Usuários finais/clientes
   - Visualização de lojas disponíveis
   - Acesso limitado conforme permissões

## Arquitetura

### Stack Tecnológico
- **Backend**: Laravel 12 + PHP 8.2+
- **Frontend**: Vue.js 3 + Inertia.js (SPA)
- **Banco**: MySQL 8.0 (produção) / SQLite (desenvolvimento)
- **Cache**: Database/Redis (configurável)
- **Armazenamento**: Local / AWS S3
- **Busca**: Elasticsearch (Docker)

### Ambientes

**Desenvolvimento Local**: SQLite + Database cache + Log files  
**Produção/Docker**: MySQL + Redis + SMTP + S3 + Elasticsearch

## Funcionalidades

### ✅ Implementado
- **Autenticação completa** - Login, 2FA, roles e permissões
- **Gestão de produtos** - CRUD com SKUs, preços e categorias
- **Controle de estoque** - Movimentações, histórico e alertas
- **Gestão de clientes** - Convites por email e associações
- **Dashboard analítico** - Gráficos ECharts em tempo real
- **Sistema de busca** - Filtros avançados e paginação server-side

### 🚀 Em desenvolvimento
- Integração Elasticsearch
- Relatórios exportáveis
- API REST completa
- Notificações push

## Instalação

### Pré-requisitos
- **PHP 8.2+** e **Composer**
- **Node.js 18+** e **npm**
- **Docker** (opcional - para ambiente completo)

### Desenvolvimento Local (Simples)

```bash
# Clone e instale
git clone <repository-url>
cd dealer
composer install && npm install

# Configure
cp .env.example .env
php artisan key:generate

# Banco SQLite e assets
php artisan migrate --seed
npm run build

# Inicie tudo
composer run dev
```

### Ambiente Completo (Docker)

```bash
# Após clonar e instalar dependências
docker-compose up -d

# Configure MySQL no .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3326

# Migre e inicie
php artisan migrate --seed
composer run dev
```

### Serviços Disponíveis
- **Aplicação**: `localhost:8000`
- **MySQL**: `localhost:3326`
- **Elasticsearch**: `localhost:9200`
- **LocalStack**: `localhost:4566`

### Executar o Projeto

### Comandos Úteis

```bash
# Desenvolvimento
composer run dev          # Inicia servidor + filas + logs + vite

# Docker
docker-compose up -d      # Inicia containers
docker-compose down       # Para containers

# Banco
php artisan migrate       # Executa migrações
php artisan migrate:fresh --seed  # Reset completo

# Cache
php artisan optimize:clear   # Limpa cache
php artisan optimize        # Otimiza para produção
```

## Configuração

### Variáveis de Ambiente Principais

```env
# Banco de dados
DB_CONNECTION=sqlite                    # ou mysql
DB_HOST=127.0.0.1                      # se mysql
DB_PORT=3326                           # se mysql

# Cache e Sessões  
CACHE_STORE=database                   # ou redis
SESSION_DRIVER=database                # ou redis

# Email
MAIL_MAILER=smtp
MAIL_HOST=seu-servidor-smtp

# AWS/LocalStack
AWS_ENDPOINT=http://localhost:4566
AWS_BUCKET=meu-bucket
```

### Deploy

```bash
# Produção
composer install --no-dev --optimize-autoloader
npm run build
php artisan config:cache
php artisan route:cache
php artisan migrate --force
```

## Testes

```bash
php artisan test                    # Todos os testes
php artisan test --filter AdminUserTest  # Teste específico
```

## Estrutura

```
app/
├── Http/Controllers/     # Controllers
├── Models/              # Models Eloquent  
├── Enums/              # Enumerações
└── Mail/               # Classes de email

resources/js/
├── Pages/              # Páginas Vue.js
└── Components/         # Componentes reutilizáveis

database/
├── migrations/         # Migrações
└── seeders/           # Dados iniciais
```

## Segurança

- Autenticação 2FA
- Rate limiting  
- Proteção CSRF
- Auditoria de ações
- Isolamento multi-tenant

## Contribuição

Para contribuir com o projeto:

1. Fork o repositório
2. Crie uma branch para sua feature (`git checkout -b feature/nova-funcionalidade`)
3. Commit suas mudanças (`git commit -am 'Adiciona nova funcionalidade'`)
4. Push para a branch (`git push origin feature/nova-funcionalidade`)
5. Abra um Pull Request

## Licença

Este projeto está licenciado sob a [MIT license](https://opensource.org/licenses/MIT).
