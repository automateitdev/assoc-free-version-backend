<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\OpenHistory;
use Illuminate\Console\Command;

class ToggleOpenHistoryStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:toggle-open-history-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Toggle the status of OpenHistory records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();

        // Get records where end_date has passed and status is ACTIVE
        $openHistories = OpenHistory::where('status', 'ACTIVE')
            ->whereDate('end_date_time', '<=', $now->toDateString())
            ->orWhere(function ($query) use ($now) {
                // Also check if start_date_time is the present date and time
                $query->where('status', 'INACTIVE')
                    ->where('start_date_time', '<=', $now);
            })
            ->get();

        foreach ($openHistories as $openHistory) {
            if ($openHistory->start_date_time <= $now) {
                // If start_date_time is present date and time, set status to ACTIVE
                $openHistory->status = 'ACTIVE';
            } else {
                // If end_date_time has passed, set status to INACTIVE
                $openHistory->status = 'INACTIVE';
            }
            $openHistory->save();
        }
        // foreach ($openHistories as $openHistory) {
        //     $openHistory->update(['status' => 'INACTIVE']);
        // }

        $this->info('Open Portal status change successfully.');
    }
}
