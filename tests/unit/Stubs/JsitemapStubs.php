<?php

/**
 * Minimal stub definitions for JSitemap classes used by the Proclaim plugin.
 *
 * These stubs replicate the backward-compatibility aliases that JSitemap's
 * SitemapModel.php creates at runtime (lines ~1079–1133). In production,
 * these classes are provided by JSitemap Pro before the plugin is loaded.
 *
 * @package    Tests
 * @subpackage Stubs
 */

/**
 * JSitemap plugin interface.
 *
 * @since  4.0
 */
interface JMapFilePlugin
{
    /**
     * Retrieve source data for sitemap generation.
     *
     * @param   JRegistry  $pluginParams  Plugin configuration parameters
     * @param   JDatabase  $db            Database connector
     * @param   JMapModel  $sitemapModel  Sitemap model reference
     *
     * @return  array
     */
    public function getSourceData(JRegistry $pluginParams, JDatabase $db, JMapModel $sitemapModel): array;
}

/**
 * JSitemap registry (backward-compat alias for Joomla\Registry\Registry).
 *
 * @since  4.0
 */
class JRegistry
{
    /**
     * @var array Internal data store
     */
    protected array $data = [];

    /**
     * Get a registry value.
     *
     * @param   string  $path     Registry path
     * @param   mixed   $default  Default value
     *
     * @return  mixed
     */
    public function get(string $path, mixed $default = null): mixed
    {
        return $this->data[$path] ?? $default;
    }

    /**
     * Set a registry value.
     *
     * @param   string  $path   Registry path
     * @param   mixed   $value  Value to set
     *
     * @return  void
     */
    public function set(string $path, mixed $value): void
    {
        $this->data[$path] = $value;
    }
}

/**
 * JSitemap database connector (backward-compat alias for Joomla\Database\DatabaseDriver).
 *
 * @since  4.0
 */
class JDatabase
{
    /**
     * Quote a database identifier.
     *
     * @param   string  $name  Identifier name
     *
     * @return  string
     */
    public function quoteName(string $name): string
    {
        return '`' . $name . '`';
    }

    /**
     * Quote a string value for SQL.
     *
     * @param   string  $text  Text to quote
     *
     * @return  string
     */
    public function quote(string $text): string
    {
        return "'" . $text . "'";
    }

    /**
     * Set the SQL query.
     *
     * @param   string    $query   SQL query
     * @param   int|null  $offset  Query offset
     * @param   int|null  $limit   Query limit
     *
     * @return  self
     */
    public function setQuery(string $query, ?int $offset = null, ?int $limit = null): self
    {
        return $this;
    }

    /**
     * Load a list of result objects.
     *
     * @return  array
     */
    public function loadObjectList(): array
    {
        return [];
    }
}

/**
 * JSitemap model (backward-compat alias for JMap\Component\JMap\Administrator\Model\SitemapModel).
 *
 * @since  4.0
 */
class JMapModel
{
    /**
     * @var int|null  Row limit for AJAX pagination
     */
    public ?int $limitRows = null;

    /**
     * @var int  Start offset for AJAX pagination
     */
    public int $limitStart = 0;

    /**
     * @var array  Internal state store
     */
    protected array $state = [];

    /**
     * Get a model state value.
     *
     * @param   string  $property  State property name
     * @param   mixed   $default   Default value
     *
     * @return  mixed
     */
    public function getState(string $property, mixed $default = null): mixed
    {
        return $this->state[$property] ?? $default;
    }

    /**
     * Set a model state value.
     *
     * @param   string  $property  State property name
     * @param   mixed   $value     Value to set
     *
     * @return  void
     */
    public function setState(string $property, mixed $value): void
    {
        $this->state[$property] = $value;
    }
}

/**
 * JSitemap exception class.
 *
 * @since  4.0
 */
class JMapException extends \RuntimeException
{
    /**
     * @var string  Exception severity level
     */
    protected string $severity;

    /**
     * Constructor.
     *
     * @param   string  $message   Error message
     * @param   string  $severity  Severity level (warning, error, notice)
     */
    public function __construct(string $message, string $severity = 'error')
    {
        $this->severity = $severity;
        parent::__construct($message);
    }

    /**
     * Get the exception severity.
     *
     * @return  string
     */
    public function getSeverity(): string
    {
        return $this->severity;
    }
}

/**
 * JSitemap multilanguage helper.
 *
 * @since  4.0
 */
class JMapLanguageMultilang
{
    /**
     * @var bool  Whether multilanguage is enabled
     */
    protected static bool $enabled = false;

    /**
     * Check if multilanguage is enabled.
     *
     * @return  bool
     */
    public static function isEnabled(): bool
    {
        return static::$enabled;
    }

    /**
     * Set multilanguage state (for testing only).
     *
     * @param   bool  $enabled  Whether to enable multilanguage
     *
     * @return  void
     */
    public static function setEnabled(bool $enabled): void
    {
        static::$enabled = $enabled;
    }
}
