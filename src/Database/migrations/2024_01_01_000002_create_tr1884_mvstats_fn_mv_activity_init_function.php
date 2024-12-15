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
            CREATE OR REPLACE FUNCTION public.tr1884_mvstats_fn_mv_activity_init()
                RETURNS SETOF text 
                LANGUAGE \'sql\'
                COST 100
                VOLATILE PARALLEL UNSAFE
                ROWS 1000
            AS $BODY$
                INSERT INTO public.tr1884_mvstats_tbl_matv_stats (mv_name)
                SELECT schemaname||\'.\'||matviewname 
                FROM pg_catalog.pg_matviews 
                WHERE schemaname||\'.\'||matviewname NOT IN (
                    SELECT mv_name FROM public.tr1884_mvstats_tbl_matv_stats
                )
                RETURNING mv_name;
            $BODY$;
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP FUNCTION IF EXISTS public.tr1884_mvstats_fn_mv_activity_init()');
    }
};
