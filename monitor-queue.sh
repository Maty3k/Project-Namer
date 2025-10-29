#!/bin/bash

# Queue Worker Monitor Script
# This script ensures the Laravel queue worker stays running
# Usage: ./monitor-queue.sh

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

echo "🔍 Starting queue worker monitor..."
echo "📂 Working directory: $SCRIPT_DIR"
echo "⏰ Check interval: 10 seconds"
echo ""

while true; do
    # Check if queue worker is running
    WORKER_COUNT=$(ps aux | grep "php artisan queue:work --queue=image-processing,default" | grep -v grep | wc -l | tr -d ' ')

    if [ "$WORKER_COUNT" -eq 0 ]; then
        echo "⚠️  [$(date '+%Y-%m-%d %H:%M:%S')] Queue worker not running! Starting..."

        # Start the queue worker in background
        nohup php artisan queue:work --queue=image-processing,default --tries=3 > storage/logs/queue-worker.log 2>&1 &

        WORKER_PID=$!
        echo "✅ [$(date '+%Y-%m-%d %H:%M:%S')] Queue worker started with PID: $WORKER_PID"
    else
        echo "✓ [$(date '+%Y-%m-%d %H:%M:%S')] Queue worker is running ($WORKER_COUNT process)"
    fi

    # Wait 10 seconds before checking again
    sleep 10
done
