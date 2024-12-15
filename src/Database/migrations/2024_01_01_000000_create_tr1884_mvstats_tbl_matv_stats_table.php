<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared('
            CREATE TABLE IF NOT EXISTS public.tr1884_mvstats_tbl_matv_stats
            (
                mv_name text COLLATE pg_catalog."default",
                create_mv timestamp without time zone,
                mod_mv timestamp without time zone,
                refresh_mv_last timestamp without time zone,
                refresh_count integer DEFAULT 0,
                refresh_mv_time_last interval,
                refresh_mv_time_total interval DEFAULT \'00:00:00\'::interval,
                refresh_mv_time_min interval,
                refresh_mv_time_max interval,
                reset_last timestamp without time zone
            )
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TABLE IF EXISTS public.tr1884_mvstats_tbl_matv_stats');
    }
};