<?php

/**
 * @package    JMAP::EXTERNALPLUGINS::administrator::components::com_jmap
 * @subpackage plugins
 * @author     CWM Team
 * @copyright  (C) 2026 CWM Team
 * @license    GPL-2.0-or-later
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die('Restricted access');

/**
 * External plugin data source for Proclaim (com_proclaim).
 *
 * Concrete implementation of the JMapFilePlugin interface
 * that retrieves studies, series, and teachers from Proclaim
 * and returns them for sitemap generation in HTML, XML, RSS formats.
 *
 * @package    JMAP::FRAMEWORK::components::com_jmap
 * @subpackage plugins
 * @since      1.0
 */
class JMapFilePluginProclaim implements JMapFilePlugin
{
    /**
     * Retrieves records for the Proclaim data source.
     *
     * @param   JRegistry  $pluginParams  The object holding configuration parameters
     * @param   JDatabase  $db            The database connector object
     * @param   JMapModel  $sitemapModel  The sitemap model object reference
     *
     * @return  array  Associative array with items, items_tree, and categories_tree
     */
    public function getSourceData(JRegistry $pluginParams, JDatabase $db, JMapModel $sitemapModel): array
    {
        // Check if Proclaim is installed
        if (!file_exists(JPATH_SITE . '/components/com_proclaim')) {
            throw new JMapException(
                Text::sprintf('COM_JMAP_ERROR_EXTENSION_NOTINSTALLED', 'Proclaim'),
                'warning'
            );
        }

        $returndata = [];

        $app  = Factory::getApplication();
        $user = $app->getIdentity();

        if (!\is_object($user)) {
            throw new JMapException(
                Text::_('COM_JMAP_PLGPROCLAIM_NOUSER_OBJECT'),
                'warning'
            );
        }

        $accessLevel = $user->getAuthorisedViewLevels();
        $langTag     = $app->getLanguage()->getTag();

        // Get plugin params
        $publishupFallback  = $pluginParams->get('publish_up_fallback', 1);
        $includeTeachers    = $pluginParams->get('include_teachers', 1);
        $includeSeriesItems = $pluginParams->get('include_series_items', 1);
        $disableAcl         = $pluginParams->get('disable_acl', 0);

        // Build ACL query fragments
        $aclQueryStudies  = null;
        $aclQuerySeries   = null;
        $aclQueryTeachers = null;

        if ($disableAcl !== 'disabled') {
            $accessList       = \implode(',', $accessLevel);
            $aclQueryStudies  = "\n AND #__bsms_studies.access IN ( " . $accessList . " )";
            $aclQuerySeries   = "\n AND #__bsms_series.access IN ( " . $accessList . " )";
            $aclQueryTeachers = "\n AND #__bsms_teachers.access IN ( " . $accessList . " )";
        }

        // Evaluate the RSS description field (prefer studyintro, fall back to studytext)
        $itemsDescription = null;

        if ($pluginParams->get('rssinclude', 1) && $sitemapModel->getState('documentformat') == 'rss') {
            $itemsDescription = "\n COALESCE(#__bsms_studies.studyintro, #__bsms_studies.studytext)"
                . " AS jsitemap_rss_desc,";
        }

        // Series inclusion/exclusion filters
        $includeSeriesFilters  = null;
        $arrayIncludedSeries   = [];
        $includedSeries        = $pluginParams->get('included_series', null);

        if ($includedSeries) {
            $arrayIncludedSeries  = array_map('intval', explode(',', $includedSeries));
            $includeSeriesFilters = "\n AND #__bsms_studies.series_id IN ( "
                . \implode(',', $arrayIncludedSeries) . " )";
        }

        $excludeSeriesFilters = null;
        $arrayExcludedSeries  = [];
        $excludedSeries       = $pluginParams->get('excluded_series', null);

        if ($excludedSeries) {
            $arrayExcludedSeries  = array_map('intval', explode(',', $excludedSeries));
            $excludeSeriesFilters = "\n AND #__bsms_studies.series_id NOT IN ( "
                . \implode(',', $arrayExcludedSeries) . " )";
        }

        // Multilingual filter for studies
        $studiesMultilanguage = null;

        if (JMapLanguageMultilang::isEnabled()) {
            $studiesMultilanguage = "\n AND (#__bsms_studies.language = '*'"
                . " OR #__bsms_studies.language = ''"
                . " OR #__bsms_studies.language = " . $db->quote($langTag) . ")";
        }

        // =============================================================
        // QUERY 1: Retrieve studies/sermons (primary items)
        // =============================================================
        $itemsQuery = "SELECT"
            . "\n #__bsms_studies.id,"
            . "\n #__bsms_studies.alias,"
            . "\n #__bsms_studies.studytitle AS " . $db->quoteName('title') . ","
            . $itemsDescription
            . "\n #__bsms_studies.series_id AS " . $db->quoteName('catid') . ","
            . "\n #__bsms_studies.modified AS " . $db->quoteName('lastmod') . ","
            . "\n #__bsms_studies.created,"
            . "\n #__bsms_studies.publish_up,"
            . "\n #__bsms_studies.access,"
            . "\n topics.metakey"
            . "\n FROM " . $db->quoteName('#__bsms_studies')
            . "\n LEFT JOIN " . $db->quoteName('#__bsms_series')
            . " ON #__bsms_studies.series_id = #__bsms_series.id"
            . "\n LEFT JOIN ("
            . " SELECT st.study_id, GROUP_CONCAT(t.topic_text SEPARATOR ', ') AS metakey"
            . " FROM #__bsms_studytopics st"
            . " JOIN #__bsms_topics t ON st.topic_id = t.id AND t.published = 1"
            . " GROUP BY st.study_id"
            . " ) topics ON #__bsms_studies.id = topics.study_id"
            . "\n WHERE"
            . "\n #__bsms_studies.published = 1"
            . $aclQueryStudies
            . $includeSeriesFilters
            . $excludeSeriesFilters
            . $studiesMultilanguage
            . "\n AND (#__bsms_studies.publish_down > NOW()"
            . " OR #__bsms_studies.publish_down = '0000-00-00 00:00:00'"
            . " OR #__bsms_studies.publish_down IS NULL)"
            . "\n ORDER BY"
            . "\n #__bsms_series.series_text ASC,"
            . "\n #__bsms_studies.studydate DESC";

        // Support AJAX pagination
        if (!$sitemapModel->limitRows) {
            $items = $db->setQuery($itemsQuery)->loadObjectList();
        } else {
            $items = $db->setQuery(
                $itemsQuery,
                $sitemapModel->limitStart,
                $sitemapModel->limitRows
            )->loadObjectList();
        }

        // Store affected rows for AJAX pagination
        if ($sitemapModel->limitRows) {
            $sitemapModel->setState('affected_rows', \count($items));
        }

        // Route links for each study
        if (\count($items)) {
            $itemsBySeries = [];

            foreach ($items as $item) {
                $item->link = Route::_(
                    'index.php?option=com_proclaim&view=cwmsermon&id='
                    . $item->id . ':' . $item->alias
                );

                // Fallback to created date if modified is empty
                if ($publishupFallback
                    && (!$item->lastmod || $item->lastmod === '0000-00-00 00:00:00')
                ) {
                    $item->lastmod = $item->created ?: $item->publish_up;
                }

                // Organize items by series
                $seriesId = (int) $item->catid;
                $itemsBySeries[$seriesId][] = $item;
            }

            $returndata['items']      = $items;
            $returndata['items_tree'] = $itemsBySeries;
        }

        // =============================================================
        // QUERY 2: Retrieve series (categories)
        // =============================================================
        $seriesMultilanguage = null;

        if (JMapLanguageMultilang::isEnabled()) {
            $seriesMultilanguage = "\n AND (#__bsms_series.language = '*'"
                . " OR #__bsms_series.language = ''"
                . " OR #__bsms_series.language = " . $db->quote($langTag) . ")";
        }

        // Series inclusion/exclusion for the categories query
        $seriesCatsIncludeFilter = null;

        if ($includedSeries) {
            $seriesCatsIncludeFilter = "\n AND #__bsms_series.id IN ( "
                . \implode(',', $arrayIncludedSeries) . " )";
        }

        $seriesCatsExcludeFilter = null;

        if ($excludedSeries) {
            $seriesCatsExcludeFilter = "\n AND #__bsms_series.id NOT IN ( "
                . \implode(',', $arrayExcludedSeries) . " )";
        }

        $catsQuery = "SELECT DISTINCT"
            . "\n #__bsms_series.id AS " . $db->quoteName('category_id') . ","
            . "\n #__bsms_series.alias AS " . $db->quoteName('category_alias') . ","
            . "\n #__bsms_series.series_text AS " . $db->quoteName('category_title') . ","
            . "\n #__bsms_series.description,"
            . "\n #__bsms_series.publish_up,"
            . "\n #__bsms_series.modified AS " . $db->quoteName('lastmod')
            . "\n FROM " . $db->quoteName('#__bsms_series')
            . "\n WHERE #__bsms_series.published = 1"
            . $aclQuerySeries
            . $seriesCatsIncludeFilter
            . $seriesCatsExcludeFilter
            . $seriesMultilanguage
            . "\n ORDER BY #__bsms_series.series_text ASC";

        $totalSeriesCats = $db->setQuery($catsQuery)->loadObjectList();

        // Build categories tree - series are flat (all under parent 0)
        $catsTree = [];

        if (\count($totalSeriesCats)) {
            $linkableCats = $pluginParams->get('linkable_content_cats', 1);

            foreach ($totalSeriesCats as $seriesCat) {
                if ($linkableCats) {
                    $seriesCat->category_link = Route::_(
                        'index.php?option=com_proclaim&view=cwmseriesdisplay&id='
                        . $seriesCat->category_id . ':' . $seriesCat->category_alias
                    );
                }

                // All series are top-level (parent 0)
                $catsTree[0][] = $seriesCat;
            }
        }

        $returndata['categories_tree'] = $catsTree;

        // =============================================================
        // QUERY 3: Optionally include series detail pages as items
        // =============================================================
        if ($includeSeriesItems && \count($totalSeriesCats)) {
            if (!isset($returndata['items'])) {
                $returndata['items'] = [];
            }

            foreach ($totalSeriesCats as $seriesItem) {
                $seriesRecord          = new \stdClass();
                $seriesRecord->title   = $seriesItem->category_title;
                $seriesRecord->link    = Route::_(
                    'index.php?option=com_proclaim&view=cwmseriesdisplay&id='
                    . $seriesItem->category_id . ':' . $seriesItem->category_alias
                );
                $seriesRecord->lastmod = $seriesItem->lastmod;
                $seriesRecord->access  = 1;

                // Series description for RSS feeds
                if (!empty($seriesItem->description)) {
                    $seriesRecord->jsitemap_rss_desc = $seriesItem->description;
                }

                // Publish up date for Google News
                if (!empty($seriesItem->publish_up)
                    && $seriesItem->publish_up !== '0000-00-00 00:00:00'
                ) {
                    $seriesRecord->publish_up = $seriesItem->publish_up;
                }

                $returndata['items'][] = $seriesRecord;
            }
        }

        // =============================================================
        // QUERY 4: Optionally include teachers as items
        // =============================================================
        if ($includeTeachers) {
            $teachersMultilanguage = null;

            if (JMapLanguageMultilang::isEnabled()) {
                $teachersMultilanguage = "\n AND (#__bsms_teachers.language = '*'"
                    . " OR #__bsms_teachers.language = ''"
                    . " OR #__bsms_teachers.language = " . $db->quote($langTag) . ")";
            }

            // Teacher RSS description (short bio)
            $teacherDescription = null;

            if ($pluginParams->get('rssinclude', 1) && $sitemapModel->getState('documentformat') == 'rss') {
                $teacherDescription = "\n #__bsms_teachers.short AS jsitemap_rss_desc,";
            }

            $teachersQuery = "SELECT"
                . "\n #__bsms_teachers.id,"
                . "\n #__bsms_teachers.alias,"
                . "\n #__bsms_teachers.teachername AS " . $db->quoteName('title') . ","
                . $teacherDescription
                . "\n #__bsms_teachers.modified AS " . $db->quoteName('lastmod') . ","
                . "\n #__bsms_teachers.created,"
                . "\n #__bsms_teachers.access"
                . "\n FROM " . $db->quoteName('#__bsms_teachers')
                . "\n WHERE #__bsms_teachers.published = 1"
                . $aclQueryTeachers
                . $teachersMultilanguage
                . "\n ORDER BY #__bsms_teachers.teachername ASC";

            $teachers = $db->setQuery($teachersQuery)->loadObjectList();

            if (\count($teachers)) {
                if (!isset($returndata['items'])) {
                    $returndata['items'] = [];
                }

                foreach ($teachers as $teacher) {
                    $teacher->link = Route::_(
                        'index.php?option=com_proclaim&view=cwmteacher&id='
                        . $teacher->id . ':' . $teacher->alias
                    );

                    // Fallback to created date if modified is empty
                    if ($publishupFallback
                        && (!$teacher->lastmod || $teacher->lastmod === '0000-00-00 00:00:00')
                    ) {
                        $teacher->lastmod = $teacher->created;
                    }

                    // Teachers have no publish_up column — use created date
                    if (!empty($teacher->created)
                        && $teacher->created !== '0000-00-00 00:00:00'
                    ) {
                        $teacher->publish_up = $teacher->created;
                    }

                    $returndata['items'][] = $teacher;
                }
            }
        }

        return $returndata;
    }
}
