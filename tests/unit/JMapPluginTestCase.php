<?php

/**
 * Base test case for JSitemap plugin tests.
 *
 * Provides mock factory methods for JSitemap dependencies (JRegistry,
 * JDatabase, JMapModel) and shared assertion helpers. Adapted from
 * Proclaim's ProclaimTestCase pattern.
 *
 * @package  Tests
 */

use Joomla\CMS\Factory;
use PHPUnit\Framework\TestCase;

class JMapPluginTestCase extends TestCase
{
    /**
     * Set up the default Joomla application stub with a user identity.
     *
     * @return  void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Reset factory state
        Factory::reset();

        // Ensure multilang is disabled by default
        \JMapLanguageMultilang::setEnabled(false);

        // Set up a default user with standard view levels
        $app  = Factory::getApplication();
        $user = new \stdClass();

        $user->id = 42;
        $user->authorisedViewLevels = [1, 2, 3];

        // Add getAuthorisedViewLevels method
        $user = $this->createMockUser([1, 2, 3]);
        $app->setIdentity($user);

        // Ensure the com_proclaim fixture directory exists
        $proclaimDir = JPATH_SITE . '/components/com_proclaim';

        if (!\file_exists($proclaimDir)) {
            \mkdir($proclaimDir, 0755, true);
        }
    }

    /**
     * Tear down test state.
     *
     * @return  void
     */
    protected function tearDown(): void
    {
        Factory::reset();
        \JMapLanguageMultilang::setEnabled(false);

        parent::tearDown();
    }

    /**
     * Create a mock user object with getAuthorisedViewLevels().
     *
     * @param   array  $viewLevels  Authorised view levels
     *
     * @return  object
     */
    protected function createMockUser(array $viewLevels = [1, 2, 3]): object
    {
        return new class ($viewLevels) {
            private array $viewLevels;

            public function __construct(array $viewLevels)
            {
                $this->viewLevels = $viewLevels;
            }

            public function getAuthorisedViewLevels(): array
            {
                return $this->viewLevels;
            }
        };
    }

    /**
     * Create a JRegistry mock with predefined parameter values.
     *
     * @param   array  $data  Key-value pairs for get() lookups
     *
     * @return  \JRegistry
     */
    protected function createMockRegistry(array $data = []): \JRegistry
    {
        $registry = new \JRegistry();

        foreach ($data as $key => $value) {
            $registry->set($key, $value);
        }

        return $registry;
    }

    /**
     * Create a JDatabase mock that captures queries and returns result sets.
     *
     * The mock's loadObjectList() returns successive result sets from the
     * provided array. Queries passed to setQuery() are captured via the
     * $capturedQueries reference parameter.
     *
     * @param   array  $resultSets       Array of result arrays for successive loadObjectList() calls
     * @param   array  &$capturedQueries Reference to array that will receive captured SQL queries
     *
     * @return  \JDatabase
     */
    protected function createMockDatabase(array $resultSets = [], array &$capturedQueries = []): \JDatabase
    {
        $mock      = $this->createMock(\JDatabase::class);
        $callIndex = 0;

        $mock->method('quoteName')
            ->willReturnCallback(static function (string $name): string {
                return '`' . $name . '`';
            });

        $mock->method('quote')
            ->willReturnCallback(static function (string $text): string {
                return "'" . $text . "'";
            });

        $mock->method('setQuery')
            ->willReturnCallback(function () use ($mock, &$capturedQueries): \JDatabase {
                $args                = \func_get_args();
                $capturedQueries[]   = $args[0];

                return $mock;
            });

        $mock->method('loadObjectList')
            ->willReturnCallback(function () use (&$callIndex, $resultSets): array {
                $result = $resultSets[$callIndex] ?? [];
                $callIndex++;

                return $result;
            });

        return $mock;
    }

    /**
     * Create a JMapModel mock with configurable state.
     *
     * @param   array     $stateOverrides  State key-value overrides
     * @param   int|null  $limitRows       Pagination row limit (null = no limit)
     * @param   int       $limitStart      Pagination start offset
     *
     * @return  \JMapModel
     */
    protected function createMockSitemapModel(
        array $stateOverrides = [],
        ?int $limitRows = null,
        int $limitStart = 0,
    ): \JMapModel {
        $model             = new \JMapModel();
        $model->limitRows  = $limitRows;
        $model->limitStart = $limitStart;

        foreach ($stateOverrides as $key => $value) {
            $model->setState($key, $value);
        }

        return $model;
    }

    /**
     * Create a standard study result object.
     *
     * @param   int          $id        Study ID
     * @param   string       $title     Study title
     * @param   string       $alias     URL alias
     * @param   int          $seriesId  Series/category ID
     * @param   string|null  $lastmod   Last modified date
     * @param   string|null  $created   Created date
     * @param   string|null  $publishUp Publish up date
     * @param   int          $access    Access level
     * @param   string|null  $metakey   Topic keywords (comma-separated)
     *
     * @return  object
     */
    protected function createStudyResult(
        int $id = 1,
        string $title = 'Test Study',
        string $alias = 'test-study',
        int $seriesId = 10,
        ?string $lastmod = '2025-01-15 10:00:00',
        ?string $created = '2025-01-01 08:00:00',
        ?string $publishUp = '2025-01-01 09:00:00',
        int $access = 1,
        ?string $metakey = null,
    ): object {
        $study              = new \stdClass();
        $study->id          = $id;
        $study->title       = $title;
        $study->alias       = $alias;
        $study->catid       = $seriesId;
        $study->lastmod     = $lastmod;
        $study->created     = $created;
        $study->publish_up  = $publishUp;
        $study->access      = $access;
        $study->metakey     = $metakey;

        return $study;
    }

    /**
     * Create a standard series/category result object.
     *
     * @param   int          $id           Series ID
     * @param   string       $title        Series title
     * @param   string       $alias        URL alias
     * @param   string|null  $lastmod      Last modified date
     * @param   string|null  $description  Series description (for RSS)
     * @param   string|null  $publishUp    Publish up date (for Google News)
     *
     * @return  object
     */
    protected function createSeriesResult(
        int $id = 10,
        string $title = 'Test Series',
        string $alias = 'test-series',
        ?string $lastmod = '2025-01-10 12:00:00',
        ?string $description = null,
        ?string $publishUp = null,
    ): object {
        $series                  = new \stdClass();
        $series->category_id    = $id;
        $series->category_alias = $alias;
        $series->category_title = $title;
        $series->lastmod        = $lastmod;
        $series->description    = $description;
        $series->publish_up     = $publishUp;

        return $series;
    }

    /**
     * Create a standard teacher result object.
     *
     * @param   int          $id               Teacher ID
     * @param   string       $title            Teacher name
     * @param   string       $alias            URL alias
     * @param   string|null  $lastmod          Last modified date
     * @param   string|null  $created          Created date
     * @param   int          $access           Access level
     * @param   string|null  $jsitemapRssDesc  Short bio for RSS feeds
     *
     * @return  object
     */
    protected function createTeacherResult(
        int $id = 100,
        string $title = 'Pastor Smith',
        string $alias = 'pastor-smith',
        ?string $lastmod = '2025-02-01 14:00:00',
        ?string $created = '2024-06-15 09:00:00',
        int $access = 1,
        ?string $jsitemapRssDesc = null,
    ): object {
        $teacher          = new \stdClass();
        $teacher->id      = $id;
        $teacher->title   = $title;
        $teacher->alias   = $alias;
        $teacher->lastmod = $lastmod;
        $teacher->created = $created;
        $teacher->access  = $access;

        if ($jsitemapRssDesc !== null) {
            $teacher->jsitemap_rss_desc = $jsitemapRssDesc;
        }

        return $teacher;
    }

    /**
     * Assert that an array contains all expected keys.
     *
     * @param   array  $expectedKeys  Keys that must be present
     * @param   array  $array         Array to check
     *
     * @return  void
     */
    protected function assertArrayHasKeys(array $expectedKeys, array $array): void
    {
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $array, "Missing expected key: {$key}");
        }
    }
}
