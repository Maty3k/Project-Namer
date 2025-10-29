# Queue Worker Fix for Logo Generation & Image Processing

## Problem
Logo generation or image processing appears stuck at "processing..." for an extended period and never completes.

## Root Cause
The Laravel queue worker is not running, or it's not processing the correct queues. This application uses multiple queues:

- **`default` queue**: Logo generation jobs (`GenerateLogosJob`)
- **`image-processing` queue**: Image upload processing jobs (`ProcessUploadedImageJob`)

**Evidence:**
- Pending jobs in the `jobs` table (attempts = 0, reserved_at = null)
- Pending images/logos with status = "pending" or "processing"
- No queue:work process running (checked with `ps aux | grep queue:work`)
- OR queue worker running but not processing the correct queues

## Solution

### Option 1: Use Monitor Script (Recommended for Development)

**NEW:** Use the included monitoring script that automatically keeps the queue worker running:

```bash
cd /Users/anamariaradulescu/Herd/Project-Namer
./monitor-queue.sh
```

This script will:
- Check every 10 seconds if the worker is running
- Automatically restart it if it crashes or stops
- Log all activity to `storage/logs/queue-worker.log`
- Keep your terminal window active with status updates

Keep this terminal window open while developing. The monitor ensures jobs are always processed.

### Option 2: Start Queue Worker Manually

**IMPORTANT:** This application uses multiple queues. You must specify both queues when starting the worker.

Open a new terminal window and run:

```bash
cd /Users/anamariaradulescu/Herd/Project-Namer
php artisan queue:work --queue=image-processing,default --tries=3
```

**Why both queues?**
- `image-processing`: Processes uploaded inspiration images (fast, ~300ms)
- `default`: Generates logos with AI (slower, ~50-60 seconds per job)

**Note:** Workers can stop unexpectedly. If jobs stop processing, restart the worker manually.

### Option 3: Use Sync Queue Driver (For Testing Only)

If you don't want to run a separate queue worker, you can temporarily use the sync driver:

1. Edit `.env` file:
```env
QUEUE_CONNECTION=sync
```

2. Restart your development server

**Note:** This will process jobs synchronously (blocking), which means logo generation will freeze your page until it completes. Not recommended for production or user experience.

### Option 4: Process Queue Once

If you just want to process the current stuck jobs without keeping a worker running:

```bash
php artisan queue:work --queue=image-processing,default --stop-when-empty
```

This will process all pending jobs from both queues and then stop automatically.

**Note:** Remember to include both queues, otherwise some jobs won't be processed!

## Verification

After starting the queue worker, you should see output like:

**For Logo Generation:**
```
[2025-10-29 08:53:32][c378493a-1dac-46cc-939c-2f835238557d] Processing: App\Jobs\GenerateLogosJob
[2025-10-29 08:53:45][c378493a-1dac-46cc-939c-2f835238557d] Processed:  App\Jobs\GenerateLogosJob (50-60s)
```
The logos should appear in your application within 50-60 seconds (depending on OpenAI API response time).

**For Image Processing:**
```
[2025-10-29 09:28:34] Processing: App\Jobs\ProcessUploadedImageJob
[2025-10-29 09:28:35] Processed:  App\Jobs\ProcessUploadedImageJob (276ms)
```
The image should be marked as "completed" within 1-2 seconds, and you'll see a green "Image Ready!" banner.

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
command=php /path/to/your/artisan queue:work --queue=image-processing,default --sleep=3 --tries=3 --max-time=3600
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

**Important:** Notice the `--queue=image-processing,default` parameter is included!

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
