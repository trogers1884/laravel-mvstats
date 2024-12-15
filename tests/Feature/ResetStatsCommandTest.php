<?php

namespace Trogers1884\LaravelMvstats\Tests\Feature;

use Trogers1884\LaravelMvstats\Tests\TestCase;
use Illuminate\Support\Facades\DB;

class ResetStatsCommandTest extends TestCase
{
    private string $testViewName = 'public.test_materialized_view';
    private string $testView2Name = 'public.test_materialized_view_2';

    protected function setUp(): void
    {
        parent::setUp();

        // Clean up any existing test views
        $this->dropMaterializedView($this->testViewName);
        $this->dropMaterializedView($this->testView2Name);
    }

    protected function tearDown(): void
    {
        // Clean up test views
        $this->dropMaterializedView($this->testViewName);
        $this->dropMaterializedView($this->testView2Name);

        parent::tearDown();
    }

    public function test_it_can_reset_stats_for_single_view(): void
    {
        // Create and refresh view to generate some stats
        $this->createTestMaterializedView($this->testViewName);
        $this->refreshMaterializedView($this->testViewName);

        // Verify we have stats
        $beforeStats = $this->getStatsForView($this->testViewName);
        $this->assertEquals(1, $beforeStats->refresh_count);

        // Run reset command
        $this->artisan('mvstats:reset-stats', ['matview' => $this->testViewName])
            ->assertSuccessful();

        // Verify stats are reset
        $afterStats = $this->getStatsForView($this->testViewName);
        $this->assertEquals(0, $afterStats->refresh_count);
        $this->assertNull($afterStats->refresh_mv_last);
        $this->assertNotNull($afterStats->reset_last);
    }

    public function test_it_can_reset_all_stats(): void
    {
        // Create and refresh multiple views
        $this->createTestMaterializedView($this->testViewName);
        $this->createTestMaterializedView($this->testView2Name);

        $this->refreshMaterializedView($this->testViewName);
        $this->refreshMaterializedView($this->testView2Name);

        // Run reset command for all views
        $this->artisan('mvstats:reset-stats', ['--all' => true])
            ->assertSuccessful();

        // Verify all stats are reset
        $stats1 = $this->getStatsForView($this->testViewName);
        $stats2 = $this->getStatsForView($this->testView2Name);

        $this->assertEquals(0, $stats1->refresh_count);
        $this->assertEquals(0, $stats2->refresh_count);
    }

    public function test_it_requires_view_name_or_all_option(): void
    {
        $this->artisan('mvstats:reset-stats')
            ->assertFailed()
            ->expectsOutput('Please provide either a materialized view name or use --all option');
    }
}