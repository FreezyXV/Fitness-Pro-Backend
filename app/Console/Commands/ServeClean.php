<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class ServeClean extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'serve:clean {--port=8000 : The port to serve on}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start Laravel development server after cleaning up zombie processes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Set unlimited execution time for this command
        set_time_limit(0);
        ini_set('max_execution_time', 0);

        $port = $this->option('port');

        $this->info('🧹 Cleaning up zombie PHP processes...');

        // Kill any existing PHP artisan serve processes
        exec("pkill -9 -f 'php.*artisan serve' 2>/dev/null", $output, $returnCode);
        exec("pkill -9 -f 'php -S 127.0.0.1:800' 2>/dev/null", $output, $returnCode);
        exec("pkill -9 -f 'php -S localhost:800' 2>/dev/null", $output, $returnCode);

        // Wait a moment for processes to terminate
        sleep(1);

        // Check if port is still in use
        exec("lsof -ti:{$port}", $output, $returnCode);
        if ($returnCode === 0 && !empty($output)) {
            $this->warn("⚠️  Port {$port} is in use. Killing processes...");
            exec("kill -9 $(lsof -ti:{$port}) 2>/dev/null");
            sleep(1);
        }

        $this->info("🚀 Starting Laravel development server on port {$port}...");
        $this->info("📝 max_execution_time is set to 0 (unlimited)");
        $this->info("🌐 Server will be available at: http://localhost:{$port}");
        $this->newLine();

        // Start the server
        passthru("php artisan serve --port={$port}");

        return 0;
    }
}
