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
            CREATE OR REPLACE FUNCTION public.tr1884_mvstats_fn_trg_mv()
                RETURNS event_trigger
                LANGUAGE \'plpgsql\'
                COST 100
                VOLATILE NOT LEAKPROOF SECURITY DEFINER
            AS $BODY$
             DECLARE r RECORD; flag boolean; t_refresh_total interval;
                BEGIN
                  FOR r IN SELECT * FROM pg_event_trigger_ddl_commands()
                    LOOP
                      IF tg_tag = \'CREATE MATERIALIZED VIEW\' THEN
                       INSERT INTO public.tr1884_mvstats_tbl_matv_stats (mv_name , create_mv ) VALUES ( r.object_identity,now());
                      END IF;
                      IF tg_tag = \'ALTER MATERIALIZED VIEW\' THEN
                        SELECT TRUE INTO flag from public.tr1884_mvstats_tbl_matv_stats where mv_name=r.object_identity;
                        IF NOT FOUND THEN
                          INSERT INTO public.tr1884_mvstats_tbl_matv_stats (mv_name , create_mv ) VALUES ( r.object_identity,now());
                          DELETE FROM public.tr1884_mvstats_tbl_matv_stats WHERE mv_name NOT IN (SELECT schemaname||\'.\'||matviewname FROM pg_catalog.pg_matviews);
                        ELSE
                          UPDATE  public.tr1884_mvstats_tbl_matv_stats SET mod_mv=now() WHERE mv_name= r.object_identity;
                        END IF;
                      END IF;
                      IF tg_tag = \'REFRESH MATERIALIZED VIEW\' THEN
                       t_refresh_total:=clock_timestamp()-(select current_setting (\'mv_stats.start\')::timestamp);
                       SET mv_stats.start to default;
                       UPDATE  public.tr1884_mvstats_tbl_matv_stats SET refresh_mv_last=now(),refresh_count=refresh_count+1,refresh_mv_time_last=t_refresh_total, refresh_mv_time_total=refresh_mv_time_total+t_refresh_total,
                        refresh_mv_time_min = (CASE WHEN refresh_mv_time_min IS NULL THEN t_refresh_total
                                                    WHEN refresh_mv_time_min IS NOT NULL AND refresh_mv_time_min > t_refresh_total THEN t_refresh_total
                                                    ELSE  refresh_mv_time_min
                                                    END),
                        refresh_mv_time_max = (CASE WHEN refresh_mv_time_max IS NULL THEN t_refresh_total
                                                    WHEN refresh_mv_time_max IS NOT NULL AND refresh_mv_time_max < t_refresh_total THEN t_refresh_total
                                                    ELSE  refresh_mv_time_max
                                                    END)
                        WHERE mv_name= r.object_identity;
                      END if;
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
        DB::unprepared('DROP FUNCTION IF EXISTS public.tr1884_mvstats_fn_trg_mv()');
    }
};
