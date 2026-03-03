<?php

namespace Nomanur\Console\Commands;

use Illuminate\Console\Command;

class MarkdownRouteCommand extends Command
{
    protected $signature = 'markdownrag:route';

    protected $description = 'Generate markdown routes';

    public function handle()
    {
        $this->components->info('Generating markdown routes...');
        $this->exportBackend();
        $this->components->info('Markdown routes generated successfully!');
    }

    public function exportBackend()
    {
        $this->components->info('Exporting backend...');

        file_put_contents(
            base_path('routes/web.php'), 
            file_get_contents(__DIR__ . '/../../../routes/web.stub.php'),
            FILE_APPEND);
        
        $this->components->info('Backend exported successfully!');
    }
}
