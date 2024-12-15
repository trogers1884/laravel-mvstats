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
            CREATE OR REPLACE FUNCTION public.tr1884_mvstats_fn_trg_mv_start()
                RETURNS event_trigger
                LANGUAGE \'plpgsql\'
                COST 100
                VOLATILE NOT LEAKPROOF SECURITY DEFINER
            AS $BODY$
                BEGIN
                  perform set_config(\'mv_stats.start\', clock_timestamp()::text, true);
                END;
            $BODY$;
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP FUNCTION IF EXISTS public.tr1884_mvstats_fn_trg_mv_start()');
    }
};