<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Aws\Sqs\SqsClient;
use Aws\Exception\AwsException;

class SetupSqsQueues extends Command
{
    protected $signature = 'sqs:setup {--create : Criar as filas} {--delete : Deletar todas as filas}';
    protected $description = 'Setup SQS queues for LocalStack';

    public function handle()
    {
        $this->info('🔧 Configurando filas SQS no LocalStack...');

        // Verificar configuração
        $config = config('queue.connections.sqs');
        $this->table(['Configuração', 'Valor'], [
            ['Driver', $config['driver']],
            ['Endpoint', $config['endpoint'] ?? 'AWS Default'],
            ['Region', $config['region']],
            ['Access Key', $config['key']],
            ['Prefix', $config['prefix']],
        ]);

        if ($this->option('delete')) {
            $this->deleteAllQueues();
        }

        if ($this->option('create')) {
            $this->createQueues();
        }

        $this->listQueues();

        return 0;
    }

    private function getSqsClient(): SqsClient
    {
        $config = config('queue.connections.sqs');

        $clientConfig = [
            'version' => 'latest',
            'region' => $config['region'],
            'credentials' => [
                'key' => $config['key'],
                'secret' => $config['secret'],
            ],
        ];

        // Adicionar endpoint para LocalStack
        if (!empty($config['endpoint'])) {
            $clientConfig['endpoint'] = $config['endpoint'];
            $clientConfig['use_path_style_endpoint'] = true;
        }

        return new SqsClient($clientConfig);
    }

    private function deleteAllQueues(): void
    {
        $this->info('🗑️  Deletando todas as filas...');

        try {
            $sqsClient = $this->getSqsClient();
            $result = $sqsClient->listQueues();

            if (empty($result['QueueUrls'])) {
                $this->info('   Nenhuma fila encontrada para deletar');
                return;
            }

            foreach ($result['QueueUrls'] as $queueUrl) {
                $queueName = basename($queueUrl);
                try {
                    $sqsClient->deleteQueue(['QueueUrl' => $queueUrl]);
                    $this->line("✅ Fila deletada: {$queueName}");
                } catch (AwsException $e) {
                    $this->error("❌ Erro ao deletar fila {$queueName}: {$e->getMessage()}");
                }
            }

        } catch (AwsException $e) {
            $this->error("❌ Erro ao listar filas: {$e->getMessage()}");
        }
    }

    private function createQueues(): void
    {
        $this->info('📝 Criando filas...');

        $queues = [
            config('sales.queues.high_priority'),
            config('sales.queues.retry'),
            config('sales.queues.recovery'),
        ];

        $sqsClient = $this->getSqsClient();

        foreach ($queues as $queueName) {
            try {
                $result = $sqsClient->createQueue([
                    'QueueName' => $queueName,
                    'Attributes' => [
                        'VisibilityTimeoutSeconds' => '300',
                        'MessageRetentionPeriod' => '1209600', // 14 dias
                        'ReceiveMessageWaitTimeSeconds' => '20',
                        'MaxReceiveCount' => '3',
                    ],
                ]);

                $this->line("✅ Fila criada: {$queueName}");
                $this->line("   URL: {$result['QueueUrl']}");

            } catch (AwsException $e) {
                $this->error("❌ Erro ao criar fila {$queueName}: {$e->getMessage()}");
                $this->line("   Código: {$e->getAwsErrorCode()}");
                $this->line("   Tipo: {$e->getAwsErrorType()}");
            }
        }
    }

    private function listQueues(): void
    {
        $this->info('📋 Listando filas existentes...');

        try {
            $sqsClient = $this->getSqsClient();
            $result = $sqsClient->listQueues();

            if (empty($result['QueueUrls'])) {
                $this->warn('⚠️  Nenhuma fila encontrada');
                return;
            }

            foreach ($result['QueueUrls'] as $queueUrl) {
                $queueName = basename($queueUrl);

                // Obter atributos da fila
                try {
                    $attributes = $sqsClient->getQueueAttributes([
                        'QueueUrl' => $queueUrl,
                        'AttributeNames' => ['ApproximateNumberOfMessages', 'VisibilityTimeoutSeconds']
                    ]);

                    $messageCount = $attributes['Attributes']['ApproximateNumberOfMessages'] ?? '0';
                    $timeout = $attributes['Attributes']['VisibilityTimeoutSeconds'] ?? 'N/A';

                    $this->line("📤 {$queueName}:");
                    $this->line("   URL: {$queueUrl}");
                    $this->line("   Mensagens: {$messageCount}");
                    $this->line("   Timeout: {$timeout}s");

                } catch (AwsException $e) {
                    $this->line("📤 {$queueName}: {$queueUrl} (erro ao obter detalhes)");
                }
            }

        } catch (AwsException $e) {
            $this->error("❌ Erro ao listar filas: {$e->getMessage()}");
            $this->line("   Código: {$e->getAwsErrorCode()}");
            $this->line("   Tipo: {$e->getAwsErrorType()}");
        }
    }
}
