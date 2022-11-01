<?php

namespace CacheWerk\BrefLaravelBridge\Queue;

use Aws\Sqs\SqsClient;
use RuntimeException;
use Bref\Context\Context;
use Bref\Event\Sqs\SqsEvent;
use Bref\Event\Sqs\SqsHandler;
use CacheWerk\BrefLaravelBridge\MaintenanceMode;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Queue\SqsQueue;
use Illuminate\Queue\Jobs\SqsJob;
use Illuminate\Queue\QueueManager;
use Illuminate\Queue\WorkerOptions;

class QueueHandler extends SqsHandler
{
    protected SqsClient $sqs;

    /**
     * Creates a new SQS queue handler instance.
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @param  \Illuminate\Contracts\Debug\ExceptionHandler  $exceptions
     * @param  string  $connection
     * @param  string  $queue
     * @return void
     */
    public function __construct(
        protected Container $container,
        protected Dispatcher $events,
        protected ExceptionHandler $exceptions,
        protected string $connection,
        protected string $queue,
    ) {
        $queue = $container->make(QueueManager::class)
            ->connection($connection);

        if (! $queue instanceof SqsQueue) {
            throw new RuntimeException('Default queue connection is not a SQS connection');
        }

        $this->sqs = $queue->getSqs();
    }

    /**
     * Handle Bref SQS event.
     *
     * @param  \Bref\Event\Sqs\SqsEvent  $event
     * @param  \Bref\Context\Context  $context
     * @return void
     */
    public function handleSqs(SqsEvent $event, Context $context): void
    {
        $worker = $this->container->makeWith(Worker::class, [
            'isDownForMaintenance' => fn () => MaintenanceMode::active(),
        ]);

        foreach ($event->getRecords() as $sqsRecord) {
            $message = $this->normalizeMessage($sqsRecord->toArray());

            $worker->runSqsJob(
                $this->buildJob($message),
                $this->connection,
                $this->getWorkerOptions()
            );
        }
    }

    protected function normalizeMessage(array $message): array
    {
        return [
            'MessageId' => $message['messageId'],
            'ReceiptHandle' => $message['receiptHandle'],
            'Body' => $message['body'],
            'Attributes' => $message['attributes'],
            'MessageAttributes' => $message['messageAttributes'],
        ];
    }

    protected function buildJob(array $message): SqsJob
    {
        return new SqsJob(
            $this->container,
            $this->sqs,
            $message,
            $this->connection,
            $this->queue,
        );
    }

    protected function getWorkerOptions(): WorkerOptions
    {
        $options = [
            $backoff = 0,
            $memory = 512,
            $timeout = 0,
            $sleep = 0,
            $maxTries = 3,
            $force = false,
            $stopWhenEmpty = false,
            $maxJobs = 0,
            $maxTime = 0,
        ];

        if (property_exists(WorkerOptions::class, 'name')) {
            $options = array_merge(['default'], $options);
        }

        return new WorkerOptions(...$options);
    }
}
