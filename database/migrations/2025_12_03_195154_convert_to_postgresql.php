<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the old CHECK constraint created from ENUM if it exists
        // PostgreSQL creates constraints with names like "users_role_check"
        if (Schema::hasTable('users')) {
            // Find and drop all CHECK constraints related to the role column
            // This handles constraints created from ENUM types
            DB::statement("
                DO $$
                DECLARE
                    constraint_rec record;
                BEGIN
                    -- Loop through all CHECK constraints that might be related to role column
                    FOR constraint_rec IN
                        SELECT conname
                        FROM pg_constraint 
                        WHERE conrelid = 'users'::regclass 
                        AND contype = 'c'
                        AND (conname ILIKE '%role%')
                    LOOP
                        EXECUTE format('ALTER TABLE users DROP CONSTRAINT IF EXISTS %I', constraint_rec.conname);
                    END LOOP;
                END $$;
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: We don't recreate the old constraint in down() 
        // because the column is now VARCHAR and should allow any value
    }
};
