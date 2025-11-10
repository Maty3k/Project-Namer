<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearAllCaches extends Command
{
    protected $signature = 'cache:clear-all';

    protected $description = 'Clear all application caches';

    public function handle(): int
    {
        $this->info('Clearing all caches...');
        
        $this->call('cache:clear');
        $this->call('config:clear');
        $this->call('route:clear');
        $this->call('view:clear');
        $this->call('event:clear');
        
        $this->info('✅ All caches cleared successfully!');
        
        return self::SUCCESS;
    }
}
