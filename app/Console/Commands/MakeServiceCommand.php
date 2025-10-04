<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeServiceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:service {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create new service extending BaseService';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $path = app_path("Services/{$name}.php");

        if (File::exists($path)) {
            $this->error("Service {$name} already exists!");
            return;
        }

        if (!File::isDirectory(app_path('Services'))) {
            File::makeDirectory(app_path('Services'));
        }

        $stub = <<<PHP
<?php

namespace App\Services;

use App\Models\\{$name};

class {$name}Service extends BaseService
{
    public function __construct({$name} \$model)
    {
        parent::__construct(\$model);
    }

    // Add new function here
}
PHP;

        File::put($path, $stub);

        $this->info("Service {$name} created successfully!");
    }
}
