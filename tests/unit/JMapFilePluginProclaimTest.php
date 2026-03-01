<?php

/**
 * Unit tests for JMapFilePluginProclaim.
 *
 * Tests the JSitemap data source plugin for Proclaim, verifying return
 * data structure, configuration toggles, ACL handling, series filtering,
 * multilingual support, and error conditions.
 *
 * @package  Tests
 */

use Joomla\CMS\Factory;

class JMapFilePluginProclaimTest extends JMapPluginTestCase
{
    /**
     * @var JMapFilePluginProclaim  Plugin instance under test
     */
    protected JMapFilePluginProclaim $plugin;

    /**
     * Set up each test.
     *
     * @return  void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->plugin = new \JMapFilePluginProclaim();
    }

    /**
     * Helper: invoke getSourceData with standard defaults.
     *
     * @param   array  $params      Plugin parameter overrides
     * @param   array  $resultSets  Database result sets [studies, series, teachers]
     * @param   array  $state       Sitemap model state overrides
     *
     * @return  array  Return data from getSourceData() plus _queries and _model keys
     */
    protected function invokePlugin(
        array $params = [],
        array $resultSets = [],
        array $state = [],
    ): array {
        $defaults = [
            'publish_up_fallback'   => 1,
            'include_teachers'      => 1,
            'include_series_items'  => 1,
            'disable_acl'           => 0,
            'rssinclude'            => 0,
            'included_series'       => null,
            'excluded_series'       => null,
            'linkable_content_cats' => 1,
        ];

        $capturedQueries = [];
        $registry        = $this->createMockRegistry(\array_merge($defaults, $params));
        $db              = $this->createMockDatabase($resultSets, $capturedQueries);
        $model           = $this->createMockSitemapModel($state);

        $result = $this->plugin->getSourceData($registry, $db, $model);

        // Attach captured queries and model for inspection
        $result['_queries'] = $capturedQueries;
        $result['_model']   = $model;

        return $result;
    }

    // =================================================================
    // Interface and Structure Tests
    // =================================================================

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(\JMapFilePlugin::class, $this->plugin);
    }

    public function testGetSourceDataReturnsExpectedKeys(): void
    {
        $studies = [$this->createStudyResult()];
        $series  = [$this->createSeriesResult()];

        $result = $this->invokePlugin([], [$studies, $series]);

        $this->assertArrayHasKeys(['items', 'items_tree', 'categories_tree'], $result);
    }

    public function testItemsHaveRequiredProperties(): void
    {
        $studies = [$this->createStudyResult()];
        $series  = [$this->createSeriesResult()];

        $result = $this->invokePlugin([], [$studies, $series]);

        $this->assertNotEmpty($result['items']);

        foreach ($result['items'] as $item) {
            $this->assertObjectHasProperty('title', $item);
            $this->assertObjectHasProperty('link', $item);
        }
    }

    public function testItemsHaveLastmodProperty(): void
    {
        $studies = [$this->createStudyResult()];
        $series  = [$this->createSeriesResult()];

        $result = $this->invokePlugin([], [$studies, $series]);

        // Study items (not series detail items) should have lastmod
        $studyItem = $result['items'][0];
        $this->assertObjectHasProperty('lastmod', $studyItem);
        $this->assertNotEmpty($studyItem->lastmod);
    }

    public function testCategoriesHaveRequiredProperties(): void
    {
        $studies = [$this->createStudyResult()];
        $series  = [$this->createSeriesResult()];

        $result = $this->invokePlugin([], [$studies, $series]);

        $this->assertNotEmpty($result['categories_tree']);
        $this->assertArrayHasKey(0, $result['categories_tree']);

        foreach ($result['categories_tree'][0] as $cat) {
            $this->assertObjectHasProperty('category_id', $cat);
            $this->assertObjectHasProperty('category_title', $cat);
            $this->assertObjectHasProperty('category_link', $cat);
        }
    }

    public function testCategoriesTreeUsesParentZero(): void
    {
        $studies = [$this->createStudyResult()];
        $series  = [
            $this->createSeriesResult(10, 'Series A', 'series-a'),
            $this->createSeriesResult(20, 'Series B', 'series-b'),
        ];

        $result = $this->invokePlugin([], [$studies, $series]);

        // All categories should be under parent key 0
        $this->assertArrayHasKey(0, $result['categories_tree']);
        $this->assertCount(1, $result['categories_tree'], 'Should only have parent key 0');
        $this->assertCount(2, $result['categories_tree'][0]);
    }

    public function testItemsTreeGroupedBySeriesId(): void
    {
        $studies = [
            $this->createStudyResult(1, 'Study A', 'study-a', 10),
            $this->createStudyResult(2, 'Study B', 'study-b', 10),
            $this->createStudyResult(3, 'Study C', 'study-c', 20),
        ];
        $series = [
            $this->createSeriesResult(10, 'Series A', 'series-a'),
            $this->createSeriesResult(20, 'Series B', 'series-b'),
        ];

        $result = $this->invokePlugin([], [$studies, $series]);

        $this->assertArrayHasKey(10, $result['items_tree']);
        $this->assertArrayHasKey(20, $result['items_tree']);
        $this->assertCount(2, $result['items_tree'][10]);
        $this->assertCount(1, $result['items_tree'][20]);
    }

    // =================================================================
    // Configuration Toggle Tests
    // =================================================================

    public function testTeachersExcludedWhenDisabled(): void
    {
        $studies  = [$this->createStudyResult()];
        $series   = [$this->createSeriesResult()];
        $teachers = [$this->createTeacherResult()];

        // With teachers enabled: studies + series + teachers queries
        $resultWith = $this->invokePlugin(
            ['include_teachers' => 1],
            [$studies, $series, $teachers],
        );

        // With teachers disabled: studies + series queries only
        $resultWithout = $this->invokePlugin(
            ['include_teachers' => 0],
            [$studies, $series],
        );

        // When teachers included, should have 3 queries (studies, series, teachers)
        $this->assertCount(3, $resultWith['_queries']);

        // When teachers excluded, should have 2 queries (studies, series)
        $this->assertCount(2, $resultWithout['_queries']);

        // Count items: with teachers should have more items
        $countWith    = \count($resultWith['items']);
        $countWithout = \count($resultWithout['items']);
        $this->assertGreaterThan($countWithout, $countWith);
    }

    public function testSeriesItemsExcludedWhenDisabled(): void
    {
        $studies = [$this->createStudyResult()];
        $series  = [$this->createSeriesResult()];

        // With series items enabled
        $resultWith = $this->invokePlugin(
            ['include_series_items' => 1, 'include_teachers' => 0],
            [$studies, $series],
        );

        // With series items disabled
        $resultWithout = $this->invokePlugin(
            ['include_series_items' => 0, 'include_teachers' => 0],
            [$studies, $series],
        );

        // With series items: study + series detail = 2 items
        // Without series items: study only = 1 item
        $this->assertCount(2, $resultWith['items']);
        $this->assertCount(1, $resultWithout['items']);
    }

    // =================================================================
    // Publish Up Fallback Tests
    // =================================================================

    public function testPublishUpFallbackUsesCreatedDate(): void
    {
        $study = $this->createStudyResult(
            id: 1,
            lastmod: null,
            created: '2025-01-01 08:00:00',
            publishUp: '2025-01-02 09:00:00',
        );
        $series = [$this->createSeriesResult()];

        $result = $this->invokePlugin(
            ['publish_up_fallback' => 1, 'include_teachers' => 0, 'include_series_items' => 0],
            [[$study], $series],
        );

        $item = $result['items'][0];
        $this->assertEquals('2025-01-01 08:00:00', $item->lastmod);
    }

    public function testPublishUpFallbackDisabled(): void
    {
        $study = $this->createStudyResult(
            id: 1,
            lastmod: null,
            created: '2025-01-01 08:00:00',
        );
        $series = [$this->createSeriesResult()];

        $result = $this->invokePlugin(
            ['publish_up_fallback' => 0, 'include_teachers' => 0, 'include_series_items' => 0],
            [[$study], $series],
        );

        $item = $result['items'][0];
        $this->assertNull($item->lastmod);
    }

    public function testPublishUpFallbackUsesPublishUpWhenCreatedEmpty(): void
    {
        $study = $this->createStudyResult(
            id: 1,
            lastmod: null,
            created: null,
            publishUp: '2025-03-15 10:00:00',
        );
        $series = [$this->createSeriesResult()];

        $result = $this->invokePlugin(
            ['publish_up_fallback' => 1, 'include_teachers' => 0, 'include_series_items' => 0],
            [[$study], $series],
        );

        $item = $result['items'][0];
        $this->assertEquals('2025-03-15 10:00:00', $item->lastmod);
    }

    // =================================================================
    // Error Condition Tests
    // =================================================================

    public function testThrowsExceptionWhenProclaimNotInstalled(): void
    {
        // Remove the com_proclaim fixture directory
        $proclaimDir = JPATH_SITE . '/components/com_proclaim';

        if (\file_exists($proclaimDir)) {
            \rmdir($proclaimDir);
        }

        $this->expectException(\JMapException::class);

        $this->invokePlugin();
    }

    // =================================================================
    // ACL Query Tests
    // =================================================================

    public function testAclQueryAppliedWhenNotDisabled(): void
    {
        $studies = [$this->createStudyResult()];
        $series  = [$this->createSeriesResult()];

        $result = $this->invokePlugin(
            ['disable_acl' => 0, 'include_teachers' => 1],
            [$studies, $series, [$this->createTeacherResult()]],
        );

        // All queries should contain ACL fragments
        foreach ($result['_queries'] as $query) {
            $this->assertStringContainsString(
                '.access IN (',
                $query,
                'Query should contain ACL filter',
            );
        }
    }

    public function testAclQuerySkippedWhenDisabled(): void
    {
        $studies = [$this->createStudyResult()];
        $series  = [$this->createSeriesResult()];

        $result = $this->invokePlugin(
            ['disable_acl' => 'disabled', 'include_teachers' => 1],
            [$studies, $series, [$this->createTeacherResult()]],
        );

        // No queries should contain ACL fragments
        foreach ($result['_queries'] as $query) {
            $this->assertStringNotContainsString(
                '.access IN (',
                $query,
                'Query should not contain ACL filter when disabled',
            );
        }
    }

    // =================================================================
    // Series Filter Tests
    // =================================================================

    public function testSeriesIncludeFilter(): void
    {
        $studies = [$this->createStudyResult()];
        $series  = [$this->createSeriesResult()];

        $result = $this->invokePlugin(
            ['included_series' => '1,2', 'include_teachers' => 0],
            [$studies, $series],
        );

        // Studies query should contain inclusion filter
        $studiesQuery = $result['_queries'][0];
        $this->assertStringContainsString('series_id IN ( 1,2 )', $studiesQuery);

        // Series query should contain inclusion filter on series.id
        $seriesQuery = $result['_queries'][1];
        $this->assertStringContainsString('.id IN ( 1,2 )', $seriesQuery);
    }

    public function testSeriesExcludeFilter(): void
    {
        $studies = [$this->createStudyResult()];
        $series  = [$this->createSeriesResult()];

        $result = $this->invokePlugin(
            ['excluded_series' => '3', 'include_teachers' => 0],
            [$studies, $series],
        );

        // Studies query should contain exclusion filter
        $studiesQuery = $result['_queries'][0];
        $this->assertStringContainsString('series_id NOT IN ( 3 )', $studiesQuery);

        // Series query should contain exclusion filter on series.id
        $seriesQuery = $result['_queries'][1];
        $this->assertStringContainsString('.id NOT IN ( 3 )', $seriesQuery);
    }

    // =================================================================
    // RSS Description Test
    // =================================================================

    public function testRssDescriptionIncluded(): void
    {
        $studies = [$this->createStudyResult()];
        $series  = [$this->createSeriesResult()];

        $result = $this->invokePlugin(
            ['rssinclude' => 1, 'include_teachers' => 0],
            [$studies, $series],
            ['documentformat' => 'rss'],
        );

        $studiesQuery = $result['_queries'][0];
        $this->assertStringContainsString('jsitemap_rss_desc', $studiesQuery);
    }

    public function testRssDescriptionExcludedForNonRss(): void
    {
        $studies = [$this->createStudyResult()];
        $series  = [$this->createSeriesResult()];

        $result = $this->invokePlugin(
            ['rssinclude' => 1, 'include_teachers' => 0],
            [$studies, $series],
            ['documentformat' => 'xml'],
        );

        $studiesQuery = $result['_queries'][0];
        $this->assertStringNotContainsString('jsitemap_rss_desc', $studiesQuery);
    }

    // =================================================================
    // Empty Results Test
    // =================================================================

    public function testEmptyResultsReturnsEmptyStructure(): void
    {
        $result = $this->invokePlugin(
            ['include_teachers' => 1],
            [[], [], []],
        );

        // categories_tree should always be set (possibly empty)
        $this->assertArrayHasKey('categories_tree', $result);
        $this->assertEmpty($result['categories_tree']);

        // items and items_tree are only set when studies exist
        $this->assertArrayNotHasKey('items', $result);
        $this->assertArrayNotHasKey('items_tree', $result);
    }

    // =================================================================
    // URL Routing Tests
    // =================================================================

    public function testStudyLinksAreRouted(): void
    {
        $study  = $this->createStudyResult(1, 'My Study', 'my-study');
        $series = [$this->createSeriesResult()];

        $result = $this->invokePlugin(
            ['include_teachers' => 0, 'include_series_items' => 0],
            [[$study], $series],
        );

        $item = $result['items'][0];
        $this->assertStringContainsString('com_proclaim', $item->link);
        $this->assertStringContainsString('view=cwmsermon', $item->link);
        $this->assertStringContainsString('1:my-study', $item->link);
    }

    public function testTeacherLinksAreRouted(): void
    {
        $teacher = $this->createTeacherResult(100, 'Pastor Smith', 'pastor-smith');
        $series  = [$this->createSeriesResult()];

        $result = $this->invokePlugin(
            ['include_teachers' => 1, 'include_series_items' => 0],
            [[], $series, [$teacher]],
        );

        // Teachers are appended to items (studies were empty, so first item is teacher)
        $teacherItem = $result['items'][0];
        $this->assertStringContainsString('com_proclaim', $teacherItem->link);
        $this->assertStringContainsString('view=cwmteacher', $teacherItem->link);
        $this->assertStringContainsString('100:pastor-smith', $teacherItem->link);
    }

    public function testSeriesDetailLinksAreRouted(): void
    {
        $series = [$this->createSeriesResult(10, 'Test Series', 'test-series')];

        $result = $this->invokePlugin(
            ['include_teachers' => 0, 'include_series_items' => 1],
            [[], $series],
        );

        // Series detail items should be in the items array
        $this->assertNotEmpty($result['items']);
        $seriesItem = $result['items'][0];
        $this->assertStringContainsString('com_proclaim', $seriesItem->link);
        $this->assertStringContainsString('view=cwmseriesdisplay', $seriesItem->link);
        $this->assertStringContainsString('10:test-series', $seriesItem->link);
    }

    // =================================================================
    // Pagination Tests
    // =================================================================

    public function testPaginationSetsAffectedRows(): void
    {
        $studies = [
            $this->createStudyResult(1),
            $this->createStudyResult(2),
        ];
        $series = [$this->createSeriesResult()];

        $registry = $this->createMockRegistry([
            'publish_up_fallback'   => 1,
            'include_teachers'      => 0,
            'include_series_items'  => 0,
            'disable_acl'           => 0,
            'rssinclude'            => 0,
            'included_series'       => null,
            'excluded_series'       => null,
            'linkable_content_cats' => 1,
        ]);
        $capturedQueries = [];
        $db              = $this->createMockDatabase([$studies, $series], $capturedQueries);
        $model = $this->createMockSitemapModel([], 10, 0);

        $this->plugin->getSourceData($registry, $db, $model);

        $this->assertEquals(2, $model->getState('affected_rows'));
    }
}
