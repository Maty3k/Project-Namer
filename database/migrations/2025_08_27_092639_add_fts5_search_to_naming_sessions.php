<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create FTS5 virtual table for full-text search
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop triggers
        DB::statement('DROP TRIGGER IF EXISTS naming_sessions_fts_insert');
        DB::statement('DROP TRIGGER IF EXISTS naming_sessions_fts_update');
        DB::statement('DROP TRIGGER IF EXISTS naming_sessions_fts_delete');
        
        // Drop FTS table
        DB::statement('DROP TABLE IF EXISTS naming_sessions_fts');
    }
};