<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DispatchJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job:dispatch {job} {parameter?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch job';

    const JOB_DIRECTORY = '\\App\Jobs\\';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $jobClassName = trim($this->argument('job'));
        if (stripos($jobClassName, "/")) {
            $jobClassName = str_replace('/', '\\', $jobClassName);
        }
        $class = self::JOB_DIRECTORY . $jobClassName;

        if (!class_exists($class)) {
            $this->error("{$class} class Not exists");
        } else {
            if ($this->argument('parameter')) {
                $job = new $class($this->argument('parameter'));
            } else {
                $job = new $class();
            }

            dispatch($job);
            $this->info("Successfully Dispatch {$class} ");
        }

        return 0;
    }
}
