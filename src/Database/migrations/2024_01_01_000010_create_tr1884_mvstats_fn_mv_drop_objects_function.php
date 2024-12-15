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
            CREATE OR REPLACE FUNCTION public.tr1884_mvstats_fn_mv_drop_objects()
                RETURNS void
                LANGUAGE \'sql\'
                COST 100
                VOLATILE PARALLEL UNSAFE
            AS $BODY$
                DROP FUNCTION public.tr1884_mvstats_fn_mv_activity_reset_stats;
                DROP FUNCTION public.tr1884_mvstats_fn_mv_activity_init;
                DROP EVENT TRIGGER tr1884_mvstats_trg_mv_info_start;
                DROP FUNCTION public.tr1884_mvstats_fn_trg_mv_start;
                DROP EVENT TRIGGER  tr1884_mvstats_trg_mv_info_drop;
                DROP EVENT TRIGGER  tr1884_mvstats_trg_mv_info;
                DROP FUNCTION public.tr1884_mvstats_fn_trg_mv_drop;
                DROP FUNCTION public.tr1884_mvstats_fn_trg_mv;
                DROP VIEW public.tr1884_mvstats_vw_matv_stats;
                DROP TABLE public.tr1884_mvstats_tbl_matv_stats;
                DROP FUNCTION public.tr1884_mvstats_fn_mv_drop_objects;
            $BODY$;
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP FUNCTION IF EXISTS public.tr1884_mvstats_fn_mv_drop_objects()');
    }
};
