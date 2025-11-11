<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\AdmissionApplied;

class FixDuplicateRolls extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'fix:duplicate-rolls';

    /**
     * The console command description.
     */
    protected $description = 'Fix duplicate assigned_roll in admission_applied table';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Starting duplicate roll fix...');

        DB::transaction(function () {

            // Find duplicate rolls per academic_year + class_id + center_id
            $duplicates = AdmissionApplied::select(
                'academic_year',
                'class_id',
                'center_id',
                'assigned_roll',
                DB::raw('COUNT(*) as count')
            )
                ->groupBy('academic_year', 'class_id', 'center_id', 'assigned_roll')
                ->having('count', '>', 1)
                ->get();

            foreach ($duplicates as $dup) {
                $rows = AdmissionApplied::where('academic_year', $dup->academic_year)
                    ->where('class_id', $dup->class_id)
                    ->where('center_id', $dup->center_id)
                    ->where('assigned_roll', $dup->assigned_roll)
                    ->orderBy('id')
                    ->get();

                $nextRoll = $dup->assigned_roll;

                foreach ($rows as $index => $row) {
                    if ($index == 0) continue; // Keep first row as is

                    // Find next available roll
                    $nextRoll++;
                    while (AdmissionApplied::where('academic_year', $row->academic_year)
                        ->where('class_id', $row->class_id)
                        ->where('center_id', $row->center_id)
                        ->where('assigned_roll', $nextRoll)
                        ->exists()
                    ) {
                        $nextRoll++;
                    }

                    $row->assigned_roll = $nextRoll;
                    $row->save();

                    $this->info("Updated row ID {$row->id} to roll {$nextRoll}");
                }
            }
        });

        $this->info('Duplicate roll fix completed!');
    }
}
