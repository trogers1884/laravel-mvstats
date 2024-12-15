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
            CREATE EVENT TRIGGER tr1884_mvstats_trg_mv_info_start ON DDL_COMMAND_START
                WHEN TAG IN (\'REFRESH MATERIALIZED VIEW\')
                EXECUTE PROCEDURE public.tr1884_mvstats_fn_trg_mv_start();
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP EVENT TRIGGER IF EXISTS tr1884_mvstats_trg_mv_info_start');
    }
};
