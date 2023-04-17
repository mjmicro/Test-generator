<?php

namespace App;

use App\Generator2;
use Illuminate\Console\Command;

class TestGenerator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravel-test:generate
                            {--filter= : Filter to a specific controller}
                            {--dir= : Directory to which the test file are to be stored within the feature folder}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically generates test cases for this application';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $options = [
            'directory' => $this->option('dir') ? $this->option('dir') : '', 
            'filter' => $this->option('filter')
        ];
        $generator = new Generator2($options);
        $generator->generate();
    }
}