<?php

namespace CacheWerk\BrefLaravelBridge\Queue;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Queue\Worker as LaravelWorker;

class Worker extends LaravelWorker
{
    /**
     * Creates a new SQS queue handler instance.
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  string  $connectionName
     * @param  \Illuminate\Queue\WorkerOptions  $options
     * @return void
     */
    public function runSqsJob(Job $job, string $connectionName, WorkerOptions $options): void
    {
        $this->runJob($job, $connectionName, $options);
    }
}
