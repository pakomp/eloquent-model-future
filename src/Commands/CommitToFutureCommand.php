<?php

namespace Dixie\EloquentModelFuture\Commands;

use Illuminate\Console\Command;
use Dixie\EloquentModelFuture\Models\Future;
use Carbon\Carbon;

class CommitToFutureCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'future:schedule';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'A command to automatically commit future plans.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $today = Carbon::now();
        $futures = Future::with('futureable')
            ->untilDate($today)
            ->uncommitted()
            ->get();
        foreach(getSubclassesOf(Future::class) as $class) {
            $futures->concat($class::with('futureable')
                ->untilDate($today)
                ->uncommitted()
                ->get());
        }

        if ($futures->isEmpty()) {
            $this->outputMessage('No future plans for today.');
            return;
        }

        $futures->each(function (Future $future) use ($today) {
            $modelWithFuture = $future->futureable;

            $modelWithFuture->future()
                ->see($today)
                ->commit();
        });

        $this->outputMessage("{$futures->count()} futures updated.");
    }

    private function getSubclassesOf($parent) {
        $result = array();
        foreach (get_declared_classes() as $class) {
            if (is_subclass_of($class, $parent))
                $result[] = $class;
        }
        return $result;
    }

    /**
     * Write a line to the commandline
     *
     * @return void
     */
    private function outputMessage($message)
    {
        $laravel = $this->laravel ?: false;

        if (! $laravel) {
            return;
        }

        if (! $laravel->runningInConsole()) {
            return;
        }

        $this->info($message);
    }
}
