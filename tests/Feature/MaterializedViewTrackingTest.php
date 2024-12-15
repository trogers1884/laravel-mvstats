<?php

namespace Trogers1884\LaravelMvstats\Tests\Feature;

use Trogers1884\LaravelMvstats\Tests\TestCase;
use Illuminate\Support\Facades\DB;

class MaterializedViewTrackingTest extends TestCase
{
    private string $testViewName = 'public.test_materialized_view';

    protected function setUp(): void
    {
        parent::setUp();
        $this->dropMaterializedView($this->testViewName);
    }

    protected function tearDown(): void
    {
        $this->dropMaterializedView($this->testViewName);
        parent::tearDown();
    }

    public function test_it_tracks_materialized_view_creation(): void
    {
        // Create test materialized view
        $this->createTestMaterializedView($this->testViewName);

        // Get stats and ensure they were captured
        $stats = $this->getStatsForView($this->testViewName);

        $this->assertNotNull($stats, "Stats should exist for the created materialized view");
        $this->assertEquals($this->testViewName, $stats->mv_name);
        $this->assertNotNull($stats->create_mv);
    }

    public function test_it_tracks_materialized_view_refresh(): void
    {
        // First create the view
        $this->createTestMaterializedView($this->testViewName);

        // Verify initial state
        $beforeStats = $this->getStatsForView($this->testViewName);
        $this->assertNotNull($beforeStats, "Stats should exist before refresh");
        $this->assertEquals(0, $beforeStats->refresh_count);

        // Perform refresh
        $this->refreshMaterializedView($this->testViewName);

        // Verify stats were updated
        $afterStats = $this->getStatsForView($this->testViewName);
        $this->assertNotNull($afterStats, "Stats should exist after refresh");
        $this->assertEquals(1, $afterStats->refresh_count);
        $this->assertNotNull($afterStats->refresh_mv_last);
    }

    public function test_it_tracks_materialized_view_deletion(): void
    {
        // First create the view
        $this->createTestMaterializedView($this->testViewName);

        // Verify it's being tracked
        $beforeStats = $this->getStatsForView($this->testViewName);
        $this->assertNotNull($beforeStats, "Stats should exist before deletion");

        // Drop the view
        $this->dropMaterializedView($this->testViewName);

        // Verify stats were removed
        $afterStats = $this->getStatsForView($this->testViewName);
        $this->assertNull($afterStats, "Stats should be removed after deletion");
    }
}