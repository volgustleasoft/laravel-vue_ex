<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\DCCron::class,
        Commands\VideoCallCron::class,
        Commands\ClientReminderCron::class,
        Commands\NewQuestionCron::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('dc:cron')
                 ->everyMinute();
        $schedule->command('videoCall:cron')
                ->everyMinute();
        $schedule->command('clientReminder:cron')
                 ->everyTenMinutes();
        $schedule->command('newQuestion:cron')
                 ->everyTenMinutes();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
