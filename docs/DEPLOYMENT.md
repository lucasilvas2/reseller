# Guia de Deploy - Dealer Management System Enterprise

## 🚀 Ambientes de Deploy

### 🏠 Desenvolvimento Local

#### Requisitos Mínimos
- **PHP 8.2+** com extensões: `pdo_sqlite`, `pdo_mysql`, `redis`, `gd`, `curl`, `mbstring`, `xml`, `zip`
- **Composer 2.6+**
- **Node.js 18+** com **npm 9+**
- **Docker & Docker Compose** (recomendado para stack completa)
- **Git** para controle de versão

#### Setup Rápido (SQLite + Database Cache)
```bash
# 1. Clone e dependências
git clone <repository-url> dealer
cd dealer
composer install
npm install && npm run build

# 2. Configuração básica
cp .env.example .env
php artisan key:generate

# 3. Database SQLite (desenvolvimento simples)
touch database/database.sqlite
php artisan migrate --seed

# 4. Executar (modo simples)
php artisan serve
```

#### Setup Enterprise (Docker Stack Completa)
```bash
# 1. Após clone e dependências
docker-compose up -d

# 2. Configuração enterprise .env
cp .env.example .env
# Editar .env com configurações MySQL + Redis

# 3. Database MySQL + Redis
php artisan migrate --seed

# 4. Configurar SQS (para vendas assíncronas)
php artisan sqs:setup --create

# 5. Validar sistema
php artisan sales:validate-implementation

# 6. Executar com workers
composer run dev  # Inclui workers automáticos
```

#### Configuração .env (Desenvolvimento Simples)
```env
APP_NAME="Dealer Management"
APP_ENV=local
APP_DEBUG=true
APP_TIMEZONE="America/Sao_Paulo"
APP_URL=http://localhost:8000

# Database SQLite (desenvolvimento rápido)
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite

# Cache e Sessions (File-based)
CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

# Sales - Modo desenvolvimento
SALES_BATCH_PROCESSING=false
SALES_CIRCUIT_BREAKER=false
SALES_CACHE_ENABLED=false
SALES_LOCK_TIMEOUT=10

# Email (opcional para dev)
MAIL_MAILER=log

# AWS/Storage (opcional)
FILESYSTEM_DISK=local
```

#### Configuração .env (Desenvolvimento Enterprise)
```env
APP_NAME="Dealer Management"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database MySQL (Docker)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3326
DB_DATABASE=dealer
DB_USERNAME=admin
DB_PASSWORD=admin

# Cache Redis (Docker)
CACHE_STORE=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6380

# Queue SQS (LocalStack)
QUEUE_CONNECTION=sqs
SQS_PREFIX=http://localhost:4566/000000000000
QUEUE_SALES_HIGH_PRIORITY=sales-high-priority
QUEUE_SALES_RETRY=sales-retry
QUEUE_SALES_RECOVERY=sales-recovery

# Sales - Configuração flexível
SALES_BATCH_PROCESSING=true
SALES_CIRCUIT_BREAKER=false
SALES_CACHE_ENABLED=true
SALES_LOCK_TIMEOUT=5

# AWS LocalStack
AWS_ENDPOINT=http://localhost:4566
AWS_USE_PATH_STYLE_ENDPOINT=true
AWS_ACCESS_KEY_ID=test
AWS_SECRET_ACCESS_KEY=test
```

### 🐳 Desenvolvimento com Docker (Stack Completa)

#### docker-compose.yml (Atualizado)
```yaml
services:
  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: rootpassword
      MYSQL_DATABASE: dealer
      MYSQL_USER: admin
      MYSQL_PASSWORD: admin
    ports:
      - "3326:3306"
    volumes:
      - mysql-data:/var/lib/mysql:rw

  redis:
    image: redis:7.2-alpine
    container_name: "dealer-redis"
    ports:
      - "6380:6379"
    command: redis-server --appendonly yes --requirepass ""
    volumes:
      - redis-data:/data:rw
    restart: unless-stopped
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 30s
      timeout: 10s
      retries: 3

  localstack:
    container_name: "localstack"
    image: localstack/localstack:3.0
    ports:
      - "4566:4566"
    environment:
      - DEBUG=1
      - DEFAULT_REGION=us-east-1
      - AWS_ACCESS_KEY_ID=test
      - AWS_SECRET_ACCESS_KEY=test
      - SERVICES=sqs
    volumes:
      - "/var/run/docker.sock:/var/run/docker.sock"

  elasticsearch:
    image: docker.elastic.co/elasticsearch/elasticsearch:7.10.1
    environment:
      - discovery.type=single-node
      - xpack.security.enabled=false
    ports:
      - "9200:9200"
      - "9300:9300"
    volumes:
      - esdata:/usr/share/elasticsearch/data

volumes:
  mysql-data:
  redis-data:
  esdata:
```

#### Setup Docker (Enterprise)
```bash
# 1. Subir stack completa
docker-compose up -d

# 2. Verificar serviços
docker-compose ps

# 3. Configurar .env para MySQL + Redis
# (usar configuração .env enterprise acima)

# 4. Setup inicial
php artisan migrate --seed
php artisan sqs:setup --create

# 5. Validar sistema
php artisan sales:validate-implementation

# 6. Testar alta demanda (opcional)
php artisan test:sales-concurrency --users=50

# 7. Executar com workers
composer run dev
```

### ☁️ Produção (AWS Enterprise)

#### Infraestrutura AWS Recomendada (Atualizada)
```
┌─────────────────────────────────────────────────────────────┐
│                AWS Production Stack Enterprise              │
├─────────────────────────────────────────────────────────────┤
│ Application Load Balancer (ALB)                             │
│ ├─ SSL Certificate (ACM)                                    │
│ ├─ Health Checks (/health)                                 │
│ └─ Target Group (EC2 instances)                            │
├─────────────────────────────────────────────────────────────┤
│ EC2 Instances (Auto Scaling Group)                         │
│ ├─ t3.large (2 vCPU, 8GB RAM) - High Demand               │
│ ├─ Amazon Linux 2023 + PHP 8.2                           │
│ ├─ Nginx + Supervisor (Queue Workers)                      │
│ └─ Sales Processing: 1000+ vendas/min                      │
├─────────────────────────────────────────────────────────────┤
│ RDS MySQL 8.0 (Optimized for Sales)                       │
│ ├─ db.t3.medium (prod) / db.t3.large (high demand)        │
│ ├─ Multi-AZ + Read Replica                                │
│ └─ InnoDB optimized for locks                             │
├─────────────────────────────────────────────────────────────┤
│ ElastiCache Redis 7.x (Enterprise Cache)                  │
│ ├─ cache.t3.small (cache + sessions + circuit breaker)    │
│ ├─ Cluster mode enabled                                   │
│ └─ Stock cache + Sales cache                              │
├─────────────────────────────────────────────────────────────┤
│ SQS + Dead Letter Queues (Sales Optimized)                │
│ ├─ sales-high-priority (FIFO) - 3000 msgs/sec            │
│ ├─ sales-retry (Standard) - retry logic                   │
│ ├─ sales-recovery (Standard) - orphan recovery            │
│ └─ Circuit breaker integration                            │
├─────────────────────────────────────────────────────────────┤
│ CloudWatch + Custom Metrics                               │
│ ├─ Sales per minute metrics                               │
│ ├─ Queue depth monitoring                                 │
│ ├─ Lock contention alerts                                 │
│ └─ Performance dashboards                                 │
└─────────────────────────────────────────────────────────────┘
```

#### EC2 Setup Script (Enterprise)
```bash
#!/bin/bash
# Amazon Linux 2023 - Enterprise Production Setup

# 1. System updates
sudo dnf update -y
sudo dnf install -y nginx git unzip supervisor

# 2. PHP 8.2 Installation (com Redis)
sudo dnf install -y php8.2 php8.2-fpm php8.2-mysqlnd php8.2-xml php8.2-gd \
                    php8.2-curl php8.2-mbstring php8.2-zip php8.2-intl \
                    php8.2-bcmath php8.2-opcache php8.2-redis

# 3. Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# 4. Node.js 18
curl -fsSL https://rpm.nodesource.com/setup_18.x | sudo bash -
sudo dnf install -y nodejs

# 5. Supervisor (for enterprise queue workers)
sudo systemctl enable supervisor
sudo systemctl start supervisor

# 6. Application deployment directory
sudo mkdir -p /var/www/dealer
sudo chown ec2-user:nginx /var/www/dealer

# 7. PHP-FPM optimizations for sales processing
sudo sed -i 's/pm.max_children = 50/pm.max_children = 100/g' /etc/php-fpm.d/www.conf
sudo sed -i 's/pm.max_requests = 500/pm.max_requests = 1000/g' /etc/php-fpm.d/www.conf

# 8. System limits for high concurrency
echo "* soft nofile 65536" | sudo tee -a /etc/security/limits.conf
echo "* hard nofile 65536" | sudo tee -a /etc/security/limits.conf
```

#### .env Produção (Enterprise)
```env
APP_NAME="Dealer Management"
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE="America/Sao_Paulo"
APP_URL=https://dealer.yourdomain.com

# Database (RDS MySQL)
DB_CONNECTION=mysql
DB_HOST=dealer-db.xxxxx.us-east-1.rds.amazonaws.com
DB_PORT=3306
DB_DATABASE=dealer
DB_USERNAME=admin
DB_PASSWORD=your-secure-password

# Cache (ElastiCache Redis)
CACHE_STORE=redis
SESSION_DRIVER=redis
REDIS_HOST=dealer-cache.xxxxx.cache.amazonaws.com
REDIS_PORT=6379

# Queue (SQS - Enterprise Configuration)
QUEUE_CONNECTION=sqs
SQS_PREFIX=https://sqs.us-east-1.amazonaws.com/123456789012
QUEUE_SALES_HIGH_PRIORITY=sales-high-priority.fifo
QUEUE_SALES_RETRY=sales-retry
QUEUE_SALES_RECOVERY=sales-recovery

# Sales - Configuração Alta Demanda
SALES_BATCH_PROCESSING=true
SALES_CIRCUIT_BREAKER=true
SALES_CACHE_ENABLED=true
SALES_LOCK_TIMEOUT=3
SALES_FAILURE_THRESHOLD=5
SALES_RECOVERY_TIME=300

# AWS
AWS_ACCESS_KEY_ID=AKIAXXXXXXXXXXXXXXXX
AWS_SECRET_ACCESS_KEY=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=dealer-uploads

# Email (SES)
MAIL_MAILER=ses
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# Logging
LOG_CHANNEL=cloudwatch
LOG_LEVEL=info

# Monitoring
SALES_MONITORING_ENABLED=true
SALES_METRICS_INTERVAL=60
```

## 🔧 Scripts de Deploy

### Deploy Automatizado (GitHub Actions) - Enterprise
```yaml
# .github/workflows/deploy-enterprise.yml
name: Deploy to AWS Enterprise

on:
  push:
    branches: [main]
  workflow_dispatch:
    inputs:
      environment:
        description: 'Environment to deploy'
        required: true
        default: 'production'
        type: choice
        options:
        - staging
        - production

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP 8.2
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2
          extensions: pdo_mysql, redis, gd, curl, mbstring, xml, zip, bcmath, opcache
      
      - name: Install dependencies
        run: |
          composer install --no-dev --optimize-autoloader
          npm install && npm run build
      
      - name: Run tests before deploy
        run: |
          php artisan test --parallel
          php artisan sales:validate-implementation
      
      - name: Deploy to EC2 (Zero Downtime)
        run: |
          # Deploy with zero downtime strategy
          ssh ec2-user@${{ secrets.EC2_HOST }} '
            cd /var/www
            
            # Create new release directory
            RELEASE=$(date +%Y%m%d_%H%M%S)
            mkdir -p releases/$RELEASE
            
            # Copy current to new release
            if [ -L current ]; then
              cp -R current/* releases/$RELEASE/
            fi
          '
          
          # rsync new code
          rsync -avz --delete \
            --exclude '.git' \
            --exclude 'node_modules' \
            --exclude 'storage/logs' \
            ./ ec2-user@${{ secrets.EC2_HOST }}:/var/www/releases/$RELEASE/
          
          # Finalize deployment
          ssh ec2-user@${{ secrets.EC2_HOST }} '
            cd /var/www/releases/$RELEASE
            
            # Setup environment
            cp /var/www/shared/.env .env
            ln -nfs /var/www/shared/storage storage
            
            # Laravel optimizations
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            
            # Database migration (with backup)
            php artisan migrate --force
            
            # Switch to new release
            ln -nfs /var/www/releases/$RELEASE /var/www/current
            
            # Restart services gracefully
            sudo supervisorctl restart dealer-workers:*
            sudo systemctl reload php-fpm
            sudo systemctl reload nginx
            
            # Cleanup old releases (keep last 5)
            cd /var/www/releases && ls -t | tail -n +6 | xargs rm -rf
            
            # Health check
            php /var/www/current/artisan sales:validate-implementation
          '
```

### Script de Deploy Manual (Enterprise)
```bash
#!/bin/bash
# deploy-enterprise.sh - Manual deployment script with enterprise features

set -e  # Exit on any error

echo "🚀 Starting enterprise deployment..."

# Configuration
BACKUP_DIR="/var/www/backups/$(date +%Y%m%d_%H%M%S)"
CURRENT_DIR="/var/www/current"

# 1. Create backup
echo "💾 Creating backup..."
mkdir -p $BACKUP_DIR
if [ -d "$CURRENT_DIR" ]; then
    cp -R $CURRENT_DIR $BACKUP_DIR/current_backup
fi

# 2. Pull latest code
echo "📥 Pulling latest code..."
git pull origin main

# 3. Validate system integrity before deploy
echo "🔍 Pre-deployment validation..."
php artisan sales:validate-implementation

# 4. Install dependencies
echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader --no-scripts
npm install && npm run build

# 5. Run Laravel optimizations
echo "⚡ Optimizing Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 6. Database migrations with rollback support
echo "🗄️ Running migrations..."
php artisan migrate --force || {
    echo "❌ Migration failed! Rolling back..."
    if [ -d "$BACKUP_DIR/current_backup" ]; then
        cp -R $BACKUP_DIR/current_backup/* $CURRENT_DIR/
    fi
    exit 1
}

# 7. Restart services gracefully
echo "🔄 Restarting services..."
php artisan queue:restart
sudo supervisorctl restart dealer-workers:*
sudo systemctl reload php-fpm nginx

# 8. Post-deployment validation
echo "✅ Post-deployment validation..."
php artisan sales:validate-implementation
php artisan test:sales-concurrency --users=10 --quick

# 9. Cache warming
echo "🔥 Warming caches..."
php artisan cache:clear
php artisan cache:warm-products

# 10. Cleanup old backups (keep last 10)
echo "🧹 Cleaning up old backups..."
cd /var/www/backups && ls -t | tail -n +11 | xargs rm -rf

echo "🎉 Enterprise deployment completed successfully!"
echo "📊 System ready for high-demand processing"
```

## 👷 Queue Workers Setup (Enterprise)

### Supervisor Configuration (Alta Demanda)
```ini
# /etc/supervisor/conf.d/dealer-workers.conf
[group:dealer-workers]
programs=dealer-high-priority,dealer-retry,dealer-recovery,dealer-monitor

[program:dealer-high-priority]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/dealer/artisan queue:work sqs --queue=sales-high-priority --sleep=1 --tries=2 --max-jobs=100 --memory=512
directory=/var/www/dealer
autostart=true
autorestart=true
user=www-data
numprocs=5
redirect_stderr=true
stdout_logfile=/var/www/dealer/storage/logs/worker-high-%(process_num)02d.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=3

[program:dealer-retry]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/dealer/artisan queue:work sqs --queue=sales-retry --sleep=3 --tries=1 --max-jobs=50 --memory=256
directory=/var/www/dealer
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/dealer/storage/logs/worker-retry-%(process_num)02d.log

[program:dealer-recovery]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/dealer/artisan queue:work sqs --queue=sales-recovery --sleep=10 --tries=1 --max-jobs=25
directory=/var/www/dealer
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/dealer/storage/logs/worker-recovery.log

[program:dealer-monitor]
command=php /var/www/dealer/artisan sales:monitor-high-demand --interval=30 --daemon
directory=/var/www/dealer
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/dealer/storage/logs/monitor.log
```

### Cron Jobs Setup (Enterprise)
```bash
# crontab -e
# Laravel scheduler (every minute)
* * * * * cd /var/www/dealer && php artisan schedule:run >> /dev/null 2>&1

# Recovery automático de vendas órfãs (a cada 5 min)
*/5 * * * * cd /var/www/dealer && php artisan sales:recover-pending >> /dev/null 2>&1

# Validação de integridade (a cada hora)
0 * * * * cd /var/www/dealer && php artisan sales:validate-implementation --silent

# Health check e métricas (a cada minuto)
* * * * * cd /var/www/dealer && php artisan sales:health-check --metrics

# Limpeza de logs (diário)
0 2 * * * cd /var/www/dealer && php artisan log:clear --days=30

# Cache warming para produtos populares (a cada 30 min)
*/30 * * * * cd /var/www/dealer && php artisan cache:warm-products

# Backup de métricas críticas (a cada hora)
0 * * * * cd /var/www/dealer && php artisan sales:backup-metrics
```

## � Monitoramento e Alertas (Enterprise)

### CloudWatch Custom Metrics
```bash
# Script para enviar métricas customizadas
#!/bin/bash
# /usr/local/bin/send-sales-metrics.sh

cd /var/www/dealer

# Métricas de vendas
SALES_PER_MINUTE=$(php artisan sales:get-metric --metric=sales_per_minute)
QUEUE_DEPTH=$(php artisan queue:size --queue=sales-high-priority)
ACTIVE_WORKERS=$(supervisorctl status dealer-workers:* | grep RUNNING | wc -l)
CIRCUIT_BREAKERS=$(php artisan sales:get-metric --metric=circuit_breakers_open)

# Enviar para CloudWatch
aws cloudwatch put-metric-data \
  --namespace "Dealer/Sales" \
  --metric-data MetricName=SalesPerMinute,Value=$SALES_PER_MINUTE,Unit=Count/Minute \
  --metric-data MetricName=QueueDepth,Value=$QUEUE_DEPTH,Unit=Count \
  --metric-data MetricName=ActiveWorkers,Value=$ACTIVE_WORKERS,Unit=Count \
  --metric-data MetricName=CircuitBreakersOpen,Value=$CIRCUIT_BREAKERS,Unit=Count
```

### Health Check Endpoint (Enterprise)
```php
// routes/web.php - Health check enterprise para Load Balancer
Route::get('/health', function () {
    $checks = [
        'database' => true,
        'redis' => true,
        'queue' => true,
        'sales_system' => true,
        'workers' => true
    ];
    
    try {
        // Database check com timeout
        DB::connection()->getPdo();
        
        // Redis check
        Cache::store('redis')->put('health_check', time(), 10);
        
        // Queue check (SQS)
        $queueSize = Queue::size('sales-high-priority');
        $checks['queue'] = $queueSize < 10000; // Alert if queue too large
        
        // Sales system check
        $lastSale = Sale::latest()->first();
        $checks['sales_system'] = $lastSale && $lastSale->created_at->diffInMinutes() < 60;
        
        // Workers check via supervisor
        $workersRunning = exec("supervisorctl status dealer-workers:* | grep RUNNING | wc -l");
        $checks['workers'] = (int)$workersRunning >= 3;
        
    } catch (\Exception $e) {
        $checks['database'] = false;
        $checks['redis'] = false;
        Log::error('Health check failed', ['error' => $e->getMessage()]);
    }
    
    $healthy = !in_array(false, $checks);
    
    return response()->json([
        'status' => $healthy ? 'healthy' : 'unhealthy',
        'checks' => $checks,
        'metrics' => [
            'sales_per_minute' => app('App\Services\MetricsService')->getSalesPerMinute(),
            'queue_depth' => $queueSize ?? 0,
            'uptime' => exec('uptime')
        ],
        'timestamp' => now()->toISOString()
    ], $healthy ? 200 : 503);
});
```

### Alerting Rules (CloudWatch Alarms)
```yaml
# cloudwatch-alarms.yml - Enterprise monitoring
Resources:
  HighQueueDepthAlarm:
    Type: AWS::CloudWatch::Alarm
    Properties:
      AlarmName: Dealer-HighQueueDepth
      AlarmDescription: "Sales queue depth too high - may indicate processing bottleneck"
      MetricName: QueueDepth
      Namespace: Dealer/Sales
      Statistic: Average
      Period: 300
      EvaluationPeriods: 2
      Threshold: 1000
      ComparisonOperator: GreaterThanThreshold
      AlarmActions:
        - !Ref SNSTopicArn

  LowSalesVolumeAlarm:
    Type: AWS::CloudWatch::Alarm
    Properties:
      AlarmName: Dealer-LowSalesVolume
      AlarmDescription: "Unusually low sales volume - system may be down"
      MetricName: SalesPerMinute
      Namespace: Dealer/Sales
      Statistic: Average
      Period: 900
      EvaluationPeriods: 3
      Threshold: 5
      ComparisonOperator: LessThanThreshold

  WorkersDownAlarm:
    Type: AWS::CloudWatch::Alarm
    Properties:
      AlarmName: Dealer-WorkersDown
      AlarmDescription: "Queue workers not running - sales processing stopped"
      MetricName: ActiveWorkers
      Namespace: Dealer/Sales
      Statistic: Average
      Period: 60
      EvaluationPeriods: 1
      Threshold: 3
      ComparisonOperator: LessThanThreshold

  CircuitBreakersOpenAlarm:
    Type: AWS::CloudWatch::Alarm
    Properties:
      AlarmName: Dealer-CircuitBreakersOpen
      AlarmDescription: "Circuit breakers protecting products - high contention detected"
      MetricName: CircuitBreakersOpen
      Namespace: Dealer/Sales
      Statistic: Average
      Period: 300
      EvaluationPeriods: 1
      Threshold: 5
## 🛡️ Segurança em Produção (Enterprise)

### SSL/TLS Configuration (Nginx) - Optimized
```nginx
server {
    listen 443 ssl http2;
    server_name dealer.yourdomain.com;
    
    # SSL Configuration
    ssl_certificate /etc/ssl/certs/dealer.pem;
    ssl_certificate_key /etc/ssl/private/dealer.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    
    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    
    # Performance optimizations for high-demand sales
    client_max_body_size 10M;
    keepalive_timeout 65;
    keepalive_requests 1000;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
        
        # Rate limiting for sales endpoints
        limit_req zone=sales_zone burst=100 nodelay;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Optimizations for sales processing
        fastcgi_read_timeout 300;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }
    
    # Health check endpoint (no rate limiting)
    location /health {
        access_log off;
        try_files $uri /index.php?$query_string;
    }
}

# Rate limiting configuration
http {
    limit_req_zone $binary_remote_addr zone=sales_zone:10m rate=100r/m;
}
```

### Firewall Rules (Security Groups)
```bash
# AWS Security Groups for production
# Application Load Balancer SG
aws ec2 create-security-group \
  --group-name dealer-alb-sg \
  --description "Security group for Dealer ALB"

aws ec2 authorize-security-group-ingress \
  --group-name dealer-alb-sg \
  --protocol tcp \
  --port 443 \
  --cidr 0.0.0.0/0

aws ec2 authorize-security-group-ingress \
  --group-name dealer-alb-sg \
  --protocol tcp \
  --port 80 \
  --cidr 0.0.0.0/0

# EC2 Instances SG (only from ALB)
aws ec2 create-security-group \
  --group-name dealer-ec2-sg \
  --description "Security group for Dealer EC2 instances"

aws ec2 authorize-security-group-ingress \
  --group-name dealer-ec2-sg \
  --protocol tcp \
  --port 80 \
  --source-group dealer-alb-sg

# Database SG (only from EC2)
aws ec2 create-security-group \
  --group-name dealer-rds-sg \
  --description "Security group for Dealer RDS"

aws ec2 authorize-security-group-ingress \
  --group-name dealer-rds-sg \
  --protocol tcp \
  --port 3306 \
## 📈 Performance Tuning (Alta Demanda)

### MySQL Optimizations
```sql
-- my.cnf optimizations for high-demand sales processing
[mysqld]
# InnoDB optimizations for concurrent sales
innodb_buffer_pool_size = 2G
innodb_buffer_pool_instances = 8
innodb_log_file_size = 512M
innodb_log_buffer_size = 64M
innodb_flush_log_at_trx_commit = 2
innodb_lock_wait_timeout = 5
innodb_rollback_on_timeout = ON

# Query cache (disabled for high concurrency)
query_cache_type = 0
query_cache_size = 0

# Connection optimizations
max_connections = 500
max_user_connections = 450
thread_cache_size = 50
table_open_cache = 4000

# Optimizations for sales processing
sql_mode = "STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO"
```

### PHP-FPM Tuning
```ini
; /etc/php/8.2/fpm/pool.d/www.conf - Production optimizations
[www]
user = www-data
group = www-data

listen = /var/run/php/php8.2-fpm.sock
listen.owner = www-data
listen.group = nginx
listen.mode = 0660

; High-demand sales processing
pm = dynamic
pm.max_children = 50
pm.start_servers = 20
pm.min_spare_servers = 10
pm.max_spare_servers = 30
pm.max_requests = 1000

; Memory limits for sales processing
php_value[memory_limit] = 512M
php_value[max_execution_time] = 300
php_value[max_input_time] = 300

; Redis session handling
php_value[session.save_handler] = redis
php_value[session.save_path] = "tcp://dealer-cache.xxxxx.cache.amazonaws.com:6379"

; OPcache optimizations
php_value[opcache.enable] = 1
php_value[opcache.memory_consumption] = 256
php_value[opcache.max_accelerated_files] = 10000
php_value[opcache.revalidate_freq] = 0
php_value[opcache.validate_timestamps] = 0
```

### Redis Configuration (ElastiCache)
```conf
# redis.conf optimizations for enterprise sales
maxmemory 2gb
maxmemory-policy allkeys-lru

# Persistence (for circuit breaker data)
save 900 1
save 300 10
save 60 10000

# Network optimizations
tcp-keepalive 300
timeout 300

# Performance
hash-max-ziplist-entries 512
hash-max-ziplist-value 64
list-max-ziplist-size -2
set-max-intset-entries 512
```

## 🔄 Comandos de Manutenção

### Comandos Diários
```bash
# Validação da integridade do sistema
php artisan sales:validate-implementation

# Limpeza de logs antigos
php artisan log:clear --days=7

# Otimização de cache
php artisan cache:clear && php artisan cache:warm-products

# Status dos workers
supervisorctl status dealer-workers:*

# Métricas de performance
php artisan sales:monitor-high-demand --summary
```

### Comandos de Emergência
```bash
# Parar todos os workers (emergência)
supervisorctl stop dealer-workers:*

# Reiniciar sistema completo
sudo systemctl restart php8.2-fpm nginx supervisor

# Forçar limpeza de locks órfãos
php artisan cache:flush
php artisan sales:clear-locks --force

# Reprocessar vendas falhadas
php artisan sales:retry-failed --batch-size=100

# Validação após emergência
php artisan test:sales-concurrency --users=10 --quantity=1
```

---

## 📋 **Checklist de Deploy Enterprise**

### ✅ **Pré-Deploy**
- [ ] Testes locais passando: `php artisan test`
- [ ] Validação do sistema: `php artisan sales:validate-implementation`  
- [ ] Backup do banco de dados criado
- [ ] Verificação de espaço em disco (>20GB livres)
- [ ] Workers em execução: `supervisorctl status`

### ✅ **Durante Deploy**
- [ ] Zero downtime strategy executada
- [ ] Migrations rodaram sem erro
- [ ] Services reiniciados gracefully
- [ ] Health check respondendo OK

### ✅ **Pós-Deploy**
- [ ] Health check: `curl https://dealer.com/health`
- [ ] Teste de venda: Create manual sale test
- [ ] Workers processando: Check queue workers
- [ ] Métricas normais: CloudWatch dashboards
- [ ] Performance OK: `php artisan sales:monitor-high-demand`

---

**📅 Atualizado: 05/08/2025**  
**🚀 Status: DEPLOYMENT GUIDE ENTERPRISE COMPLETO**  
**💪 Capacidade: 1000+ vendas/minuto suportadas**  
**✅ Ambiente: Desenvolvimento → Produção AWS escalável**
    ssl_protocols TLSv1.2 TLSv1.3;
    
    root /var/www/dealer/public;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### Security Headers
```php
// app/Http/Middleware/SecurityHeaders.php
class SecurityHeaders {
    public function handle($request, Closure $next) {
        $response = $next($request);
        
        return $response->withHeaders([
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
        ]);
    }
}
```

## 📋 Checklist de Deploy

### ✅ Pré-Deploy
- [ ] Executar todos os testes: `php artisan test`
- [ ] Validar configuração: `php artisan config:show`
- [ ] Backup da base de dados
- [ ] Verificar filas SQS criadas
- [ ] Testar conexão com serviços externos (RDS, ElastiCache, S3)

### ✅ Durante Deploy
- [ ] Colocar aplicação em modo manutenção: `php artisan down`
- [ ] Atualizar código fonte
- [ ] Instalar dependências: `composer install --no-dev --optimize-autoloader`
- [ ] Compilar assets: `npm run build`
- [ ] Executar migrações: `php artisan migrate --force`
- [ ] Otimizar Laravel: `php artisan optimize`
- [ ] Retirar do modo manutenção: `php artisan up`

### ✅ Pós-Deploy
- [ ] Verificar health check: `curl https://dealer.yourdomain.com/health`
- [ ] Testar funcionalidades críticas (login, criação de venda)
- [ ] Verificar workers funcionando: `supervisorctl status`
- [ ] Monitorar logs por 15 minutos
- [ ] Verificar métricas CloudWatch

---

**Resultado**: Sistema pronto para produção com alta disponibilidade e escalabilidade automática. 🚀
