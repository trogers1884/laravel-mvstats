<?php

namespace Trogers1884\LaravelMvstats\Tests;

use Trogers1884\LaravelMvstats\LaravelMvstatsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Illuminate\Support\Facades\DB;
use Dotenv\Dotenv;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        // Load .env.testing file before parent setup
        if (file_exists(dirname(__DIR__) . '/.env.testing')) {
            (Dotenv::createImmutable(dirname(__DIR__), '.env.testing'))->load();
        }

        parent::setUp();

        // Clean database completely before each test
        $this->fullCleanup();

        try {
            // Verify connection and schema
            $result = DB::select("SELECT current_schema()");
            echo "\nCurrent schema: " . $result[0]->current_schema . "\n";

            $result = DB::select("SELECT current_database()");
            echo "Current database: " . $result[0]->current_database . "\n";

            // Create base table first
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

            // Create view
            DB::unprepared('
                CREATE OR REPLACE VIEW public.tr1884_mvstats_vw_matv_stats AS
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

            // Create functions
            DB::unprepared('
                CREATE OR REPLACE FUNCTION public.tr1884_mvstats_fn_mv_activity_init()
                    RETURNS SETOF text 
                    LANGUAGE sql
                    COST 100
                    VOLATILE PARALLEL UNSAFE
                    ROWS 1000
                AS $BODY$
                    INSERT INTO public.tr1884_mvstats_tbl_matv_stats (mv_name)
                    SELECT schemaname||\'.\'||matviewname FROM pg_catalog.pg_matviews 
                    WHERE schemaname||\'.\'||matviewname NOT IN (
                        SELECT mv_name FROM public.tr1884_mvstats_tbl_matv_stats
                    )
                    RETURNING mv_name;
                $BODY$;
            ');

            DB::unprepared('
                CREATE OR REPLACE FUNCTION public.tr1884_mvstats_fn_mv_activity_reset_stats(
                    VARIADIC mview text[] DEFAULT ARRAY[\'*\'::text])
                    RETURNS SETOF text 
                    LANGUAGE plpgsql
                    COST 100
                    VOLATILE PARALLEL UNSAFE
                    ROWS 1000
                AS $BODY$
                DECLARE v text;
                BEGIN
                  FOREACH v IN ARRAY $1 LOOP
                    IF v = \'*\' THEN
                      RETURN query UPDATE public.tr1884_mvstats_tbl_matv_stats 
                      SET refresh_mv_last = NULL,
                          refresh_count = 0,
                          refresh_mv_time_last = NULL,
                          refresh_mv_time_total = \'00:00:00\',
                          refresh_mv_time_min = NULL,
                          refresh_mv_time_max = NULL,
                          reset_last = now()
                      RETURNING mv_name;
                    ELSE
                      RETURN query UPDATE public.tr1884_mvstats_tbl_matv_stats 
                      SET refresh_mv_last = NULL,
                          refresh_count = 0,
                          refresh_mv_time_last = NULL,
                          refresh_mv_time_total = \'00:00:00\',
                          refresh_mv_time_min = NULL,
                          refresh_mv_time_max = NULL,
                          reset_last = now()
                      WHERE mv_name = v
                      RETURNING mv_name;
                    END IF;
                  END LOOP;
                  RETURN;
                END;
                $BODY$;
            ');

            DB::unprepared('
                CREATE OR REPLACE FUNCTION public.tr1884_mvstats_fn_trg_mv_start()
                    RETURNS event_trigger
                    LANGUAGE plpgsql
                    COST 100
                    VOLATILE NOT LEAKPROOF SECURITY DEFINER
                AS $BODY$
                BEGIN
                  perform set_config(\'mv_stats.start\', clock_timestamp()::text, true);
                END;
                $BODY$;
            ');

            DB::unprepared('
                CREATE OR REPLACE FUNCTION public.tr1884_mvstats_fn_trg_mv()
                    RETURNS event_trigger
                    LANGUAGE plpgsql
                    COST 100
                    VOLATILE NOT LEAKPROOF SECURITY DEFINER
                AS $BODY$
                DECLARE r RECORD; flag boolean; t_refresh_total interval;
                BEGIN
                  FOR r IN SELECT * FROM pg_event_trigger_ddl_commands()
                    LOOP
                      IF tg_tag = \'CREATE MATERIALIZED VIEW\' THEN
                        INSERT INTO public.tr1884_mvstats_tbl_matv_stats (mv_name, create_mv)
                        VALUES (r.object_identity, now());
                      END IF;
                      IF tg_tag = \'ALTER MATERIALIZED VIEW\' THEN
                        SELECT TRUE INTO flag FROM public.tr1884_mvstats_tbl_matv_stats
                        WHERE mv_name = r.object_identity;
                        IF NOT FOUND THEN
                          INSERT INTO public.tr1884_mvstats_tbl_matv_stats (mv_name, create_mv)
                          VALUES (r.object_identity, now());
                        ELSE
                          UPDATE public.tr1884_mvstats_tbl_matv_stats
                          SET mod_mv = now()
                          WHERE mv_name = r.object_identity;
                        END IF;
                      END IF;
                      IF tg_tag = \'REFRESH MATERIALIZED VIEW\' THEN
                        t_refresh_total := clock_timestamp()-(select current_setting(\'mv_stats.start\')::timestamp);
                        SET mv_stats.start to default;
                        UPDATE public.tr1884_mvstats_tbl_matv_stats
                        SET refresh_mv_last = now(),
                            refresh_count = refresh_count + 1,
                            refresh_mv_time_last = t_refresh_total,
                            refresh_mv_time_total = refresh_mv_time_total + t_refresh_total,
                            refresh_mv_time_min = CASE
                                WHEN refresh_mv_time_min IS NULL THEN t_refresh_total
                                WHEN refresh_mv_time_min > t_refresh_total THEN t_refresh_total
                                ELSE refresh_mv_time_min
                            END,
                            refresh_mv_time_max = CASE
                                WHEN refresh_mv_time_max IS NULL THEN t_refresh_total
                                WHEN refresh_mv_time_max < t_refresh_total THEN t_refresh_total
                                ELSE refresh_mv_time_max
                            END
                        WHERE mv_name = r.object_identity;
                      END IF;
                    END LOOP;
                END;
                $BODY$;
            ');

            DB::unprepared('
                CREATE OR REPLACE FUNCTION public.tr1884_mvstats_fn_trg_mv_drop()
                    RETURNS event_trigger
                    LANGUAGE plpgsql
                    COST 100
                    VOLATILE NOT LEAKPROOF SECURITY DEFINER
                AS $BODY$
                DECLARE r RECORD;
                BEGIN
                  FOR r IN SELECT * FROM pg_event_trigger_dropped_objects()
                    LOOP
                      DELETE FROM public.tr1884_mvstats_tbl_matv_stats
                      WHERE mv_name = r.object_identity;
                    END LOOP;
                END;
                $BODY$;
            ');

            // Create event triggers
            DB::unprepared('
                CREATE EVENT TRIGGER tr1884_mvstats_trg_mv_info_start
                ON DDL_COMMAND_START
                WHEN TAG IN (\'REFRESH MATERIALIZED VIEW\')
                EXECUTE PROCEDURE public.tr1884_mvstats_fn_trg_mv_start();
            ');

            DB::unprepared('
                CREATE EVENT TRIGGER tr1884_mvstats_trg_mv_info
                ON DDL_COMMAND_END
                WHEN TAG IN (\'CREATE MATERIALIZED VIEW\', \'ALTER MATERIALIZED VIEW\', \'REFRESH MATERIALIZED VIEW\')
                EXECUTE PROCEDURE public.tr1884_mvstats_fn_trg_mv();
            ');

            DB::unprepared('
                CREATE EVENT TRIGGER tr1884_mvstats_trg_mv_info_drop
                ON SQL_DROP
                WHEN TAG IN (\'DROP MATERIALIZED VIEW\')
                EXECUTE PROCEDURE public.tr1884_mvstats_fn_trg_mv_drop();
            ');

            // Verify objects were created
            echo "\nVerifying database objects:\n";

            $tableExists = DB::select("
                SELECT EXISTS (
                    SELECT FROM information_schema.tables 
                    WHERE table_schema = 'public' 
                    AND table_name = 'tr1884_mvstats_tbl_matv_stats'
                ) as exists
            ");
            echo "Base table exists: " . json_encode($tableExists[0]) . "\n";

            $triggers = DB::select("
                SELECT evtname, evtenabled 
                FROM pg_event_trigger 
                WHERE evtname LIKE 'tr1884_mvstats%'
            ");
            echo "Event triggers:\n";
            foreach ($triggers as $trigger) {
                echo "- {$trigger->evtname}: " . ($trigger->evtenabled === 'O' ? 'enabled' : 'disabled') . "\n";
            }

            $functions = DB::select("
                SELECT proname 
                FROM pg_proc 
                WHERE proname LIKE 'tr1884_mvstats%'
            ");
            echo "Functions:\n";
            foreach ($functions as $function) {
                echo "- {$function->proname}\n";
            }

        } catch (\Exception $e) {
            echo "Setup error: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    protected function tearDown(): void
    {
        $this->fullCleanup();
        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [
            LaravelMvstatsServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'pgsql');
        $app['config']->set('database.connections.pgsql', [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'mvstats_test'),
            'username' => env('DB_USERNAME', 'postgres'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => 'prefer',
        ]);
    }

    protected function fullCleanup(): void
    {
        try {
            // Drop everything in reverse order
            DB::unprepared("DROP EVENT TRIGGER IF EXISTS tr1884_mvstats_trg_mv_info_drop CASCADE");
            DB::unprepared("DROP EVENT TRIGGER IF EXISTS tr1884_mvstats_trg_mv_info CASCADE");
            DB::unprepared("DROP EVENT TRIGGER IF EXISTS tr1884_mvstats_trg_mv_info_start CASCADE");
            DB::unprepared("DROP FUNCTION IF EXISTS public.tr1884_mvstats_fn_trg_mv_drop() CASCADE");
            DB::unprepared("DROP FUNCTION IF EXISTS public.tr1884_mvstats_fn_trg_mv() CASCADE");
            DB::unprepared("DROP FUNCTION IF EXISTS public.tr1884_mvstats_fn_trg_mv_start() CASCADE");
            DB::unprepared("DROP FUNCTION IF EXISTS public.tr1884_mvstats_fn_mv_activity_reset_stats(VARIADIC text[]) CASCADE");
            DB::unprepared("DROP FUNCTION IF EXISTS public.tr1884_mvstats_fn_mv_activity_init() CASCADE");
            DB::unprepared("DROP VIEW IF EXISTS public.tr1884_mvstats_vw_matv_stats CASCADE");
            DB::unprepared("DROP TABLE IF EXISTS public.tr1884_mvstats_tbl_matv_stats CASCADE");

            echo "Cleanup completed successfully\n";
        } catch (\Exception $e) {
            echo "Cleanup error: " . $e->getMessage() . "\n";
        }
    }

    protected function createTestMaterializedView(string $name): void
    {
        DB::statement("
            CREATE MATERIALIZED VIEW {$name} AS
            SELECT 1 as test_column
        ");
    }

    protected function refreshMaterializedView(string $name): void
    {
        DB::statement("REFRESH MATERIALIZED VIEW {$name}");
    }

    protected function dropMaterializedView(string $name): void
    {
        DB::statement("DROP MATERIALIZED VIEW IF EXISTS {$name} CASCADE");
    }

    protected function getStatsForView(string $name): ?object
    {
        return DB::table('tr1884_mvstats_tbl_matv_stats')
            ->where('mv_name', $name)
            ->first();
    }
}