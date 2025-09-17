<?php

namespace App\Console\Commands;

use App\Jobs\AssignRolls;
use Illuminate\Console\Command;

class AssignRollsCommand extends Command
{
    protected $signature = 'assign:rolls';
    protected $description = 'Assign rolls to successful admission applications';


    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        AssignRolls::dispatch();
        return 0;
    }
}
