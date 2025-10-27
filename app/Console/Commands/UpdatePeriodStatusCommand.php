<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Period;
use Carbon\Carbon;

class UpdatePeriodStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'periods:update-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the status of academic periods based on their dates';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();
        $this->info('Checking for period status updates...');

        $periodsToProgress = Period::where('status', 'registration_open')
                                   ->where('registration_end', '<', $now)
                                   ->get();

        foreach ($periodsToProgress as $period) {
            $period->status = 'in_progress';
            $period->save();
            $this->info("Period '{$period->name}' moved to In Progress.");
        }

        $periodsToComplete = Period::where('status', 'in_progress')
                                   ->where('end_date', '<', $now)
                                   ->get();

        foreach ($periodsToComplete as $period) {
            $period->status = 'completed';
            $period->is_active = false;
            $period->save();
            $this->info("Period '{$period->name}' has been Completed.");
        }

        $this->info('Period status update check complete.');
        return 0;
    }
}