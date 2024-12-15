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
            CREATE OR REPLACE FUNCTION public.tr1884_mvstats_fn_trg_mv_drop()
                RETURNS event_trigger
                LANGUAGE \'plpgsql\'
                COST 100
                VOLATILE NOT LEAKPROOF SECURITY DEFINER
            AS $BODY$
             DECLARE r RECORD; flag boolean;
                BEGIN
                  FOR r IN SELECT * FROM pg_event_trigger_dropped_objects()
                    LOOP
                      DELETE FROM public.tr1884_mvstats_tbl_matv_stats WHERE mv_name =r.object_identity ;
                    END LOOP;
                END;
            $BODY$;
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP FUNCTION IF EXISTS public.tr1884_mvstats_fn_trg_mv_drop()');
    }
};
