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
            CREATE OR REPLACE VIEW public.tr1884_mvstats_vw_matv_stats
            AS
            SELECT mv_name,
                create_mv,
                mod_mv,
                refresh_mv_last,
                refresh_count,
                refresh_mv_time_last,
                refresh_mv_time_total,
                refresh_mv_time_min,
                refresh_mv_time_max,
                reset_last
            FROM public.tr1884_mvstats_tbl_matv_stats
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP VIEW IF EXISTS public.tr1884_mvstats_vw_matv_stats');
    }
};
