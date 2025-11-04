<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite: Use FTS5 virtual table for full-text search
            DB::statement('
                CREATE VIRTUAL TABLE naming_sessions_fts USING fts5(
                    id UNINDEXED,
                    title,
                    business_description,
                    content=naming_sessions,
                    content_rowid=id
                )
            ');

            // Insert existing data into FTS table
            DB::statement('
                INSERT INTO naming_sessions_fts(id, title, business_description)
                SELECT id, title, business_description FROM naming_sessions
            ');

            // Create triggers to keep FTS table in sync

            // Trigger for INSERT
            DB::statement('
                CREATE TRIGGER naming_sessions_fts_insert AFTER INSERT ON naming_sessions BEGIN
                    INSERT INTO naming_sessions_fts(id, title, business_description)
                    VALUES (new.id, new.title, new.business_description);
                END
            ');

            // Trigger for UPDATE
            DB::statement('
                CREATE TRIGGER naming_sessions_fts_update AFTER UPDATE ON naming_sessions BEGIN
                    DELETE FROM naming_sessions_fts WHERE id = old.id;
                    INSERT INTO naming_sessions_fts(id, title, business_description)
                    VALUES (new.id, new.title, new.business_description);
                END
            ');

            // Trigger for DELETE
            DB::statement('
                CREATE TRIGGER naming_sessions_fts_delete AFTER DELETE ON naming_sessions BEGIN
                    DELETE FROM naming_sessions_fts WHERE id = old.id;
                END
            ');
        } elseif ($driver === 'mysql') {
            // MySQL: Add FULLTEXT indexes for full-text search
            Schema::table('naming_sessions', function ($table): void {
                $table->fullText(['title', 'business_description'], 'naming_sessions_fulltext');
            });
        }
        // PostgreSQL and other databases: No action needed, will fall back to LIKE queries
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            // Drop triggers
            DB::statement('DROP TRIGGER IF EXISTS naming_sessions_fts_insert');
            DB::statement('DROP TRIGGER IF EXISTS naming_sessions_fts_update');
            DB::statement('DROP TRIGGER IF EXISTS naming_sessions_fts_delete');

            // Drop FTS table
            DB::statement('DROP TABLE IF EXISTS naming_sessions_fts');
        } elseif ($driver === 'mysql') {
            // Drop FULLTEXT index
            Schema::table('naming_sessions', function ($table): void {
                $table->dropIndex('naming_sessions_fulltext');
            });
        }
    }
};
