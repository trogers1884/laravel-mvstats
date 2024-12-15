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
            CREATE OR REPLACE FUNCTION public.tr1884_mvstats_fn_mv_activity_reset_stats(
                VARIADIC mview text[] DEFAULT ARRAY[\'*\'::text])
                RETURNS SETOF text 
                LANGUAGE \'plpgsql\'
                COST 100
                VOLATILE PARALLEL UNSAFE
                ROWS 1000
            AS $BODY$
             DECLARE v text;
                    BEGIN
                      FOREACH v IN ARRAY $1 LOOP
                        IF v = \'*\' THEN
                          RETURN query UPDATE public.tr1884_mvstats_tbl_matv_stats SET refresh_mv_last= NULL , refresh_count= 0,refresh_mv_time_last= NULL, refresh_mv_time_total= \'00:00:00\', refresh_mv_time_min= NULL, refresh_mv_time_max= NULL, reset_last = now() RETURNING mv_name;
                        ELSE
                          RETURN query UPDATE public.tr1884_mvstats_tbl_matv_stats SET refresh_mv_last= NULL , refresh_count= 0,refresh_mv_time_last= NULL, refresh_mv_time_total= \'00:00:00\', refresh_mv_time_min= NULL, refresh_mv_time_max= NULL, reset_last = now() where mv_name=v RETURNING mv_name;
                        END IF;
                      END LOOP;
                      RETURN ;
                    END;
            $BODY$;
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP FUNCTION IF EXISTS public.tr1884_mvstats_fn_mv_activity_reset_stats(VARIADIC text[])');
    }
};
