<?php

namespace Trogers1884\LaravelMvstats\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetMaterializedViewStats extends Command
{
    protected $signature = 'mvstats:reset-stats 
                          {matview? : The name of the materialized view (e.g., schema.matview_name)}
                          {--all : Reset stats for all materialized views}';

    protected $description = 'Reset statistics for materialized view(s) in the stats table';

    public function handle()
    {
        $matview = $this->argument('matview');
        $all = $this->option('all');

        if (!$matview && !$all) {
            $this->error('Please provide either a materialized view name or use --all option');
            return 1;
        }

        try {
            if ($all) {
                DB::select("SELECT public.tr1884_mvstats_fn_mv_activity_reset_stats('*')");
                $this->info('Statistics reset for all materialized views');
            } else {
                DB::select("SELECT public.tr1884_mvstats_fn_mv_activity_reset_stats(?)", [$matview]);
                $this->info("Statistics reset for materialized view: {$matview}");
            }
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to reset statistics: " . $e->getMessage());
            return 1;
        }
    }
}