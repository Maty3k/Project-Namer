# Logo Generation Queue Worker Fix

## Problem
Logo generation appears stuck at "generating..." for 5+ minutes and never completes.

## Root Cause
The Laravel queue worker is not running. Logo generation jobs are being dispatched to the database queue but nothing is processing them.

**Evidence:**
- 4 pending jobs in the `jobs` table (attempts = 0, reserved_at = null)
- 4 pending logo_generations with status = "pending"
- No queue:work process running (checked with `ps aux | grep queue:work`)

## Solution

### Option 1: Start Queue Worker (Recommended for Development)

Open a new terminal window and run:

```bash
cd /Users/anamariaradulescu/Herd/Project-Namer
php artisan queue:work --tries=3
```

Keep this terminal window open while working on the application. The queue worker will process all pending jobs immediately.

### Option 2: Use Sync Queue Driver (For Testing Only)

If you don't want to run a separate queue worker, you can temporarily use the sync driver:

1. Edit `.env` file:
```env
QUEUE_CONNECTION=sync
```

2. Restart your development server

**Note:** This will process jobs synchronously (blocking), which means logo generation will freeze your page until it completes. Not recommended for production or user experience.

### Option 3: Process Queue Once

If you just want to process the current stuck jobs without keeping a worker running:

```bash
php artisan queue:work --stop-when-empty
```

This will process all pending jobs and then stop automatically.

## Verification

After starting the queue worker, you should see output like:

```
[2025-10-29 08:53:32][c378493a-1dac-46cc-939c-2f835238557d] Processing: App\Jobs\GenerateLogosJob
[2025-10-29 08:53:45][c378493a-1dac-46cc-939c-2f835238557d] Processed:  App\Jobs\GenerateLogosJob
```

The logos should appear in your application within 30-60 seconds (depending on OpenAI API response time).

## Long-Term Solution (Production)

For production environments, use a process manager like Supervisor or systemd to keep the queue worker running continuously:

### Using Supervisor (Recommended)

1. Install supervisor:
```bash
sudo apt-get install supervisor  # Ubuntu/Debian
```

2. Create supervisor config `/etc/supervisor/conf.d/laravel-worker.conf`:
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/your/storage/logs/worker.log
stopwaitsecs=3600
```

3. Start supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

## Checking Current Status

### Check if queue worker is running:
```bash
ps aux | grep "queue:work"
```

### Check pending jobs:
```bash
php artisan queue:monitor
```

Or directly query the database:
```bash
php artisan tinker
>>> DB::table('jobs')->count()
```

### Check logo generation status:
```bash
php artisan tinker
>>> App\Models\LogoGeneration::latest()->limit(5)->get(['id', 'business_name', 'status', 'logos_completed'])
```

## Current Pending Jobs to Process

There are currently **4 logo generation jobs** waiting to be processed:
- Logo Generation #1: ID 1 (created 2025-10-29 08:46:21)
- Logo Generation #2: ID 2 (created 2025-10-29 08:50:28) - "Brew Haven"
- Logo Generation #3: ID 3 (created 2025-10-29 08:52:02) - "Caffeine Canvas"
- Logo Generation #4: ID 4 (created 2025-10-29 08:53:32) - "Caffeine Canvas"

Once you start the queue worker, these will all be processed automatically.

## Laravel Herd Note

If you're using Laravel Herd, you can start the queue worker from the Herd UI:

1. Open Laravel Herd
2. Click on your project
3. Go to the "Queue" tab
4. Click "Start Queue Worker"

Alternatively, Herd provides a convenient way to run artisan commands directly from the UI.
