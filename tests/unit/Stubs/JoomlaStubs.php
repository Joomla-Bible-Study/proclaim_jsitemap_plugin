<?php

/**
 * Minimal stub definitions for Joomla CMS classes used by the Proclaim plugin.
 *
 * These stubs provide just enough of the Joomla framework API to allow
 * unit testing without a Joomla runtime. In production, these are provided
 * by the Joomla CMS framework.
 *
 * @package    Tests
 * @subpackage Stubs
 */

namespace Joomla\CMS {

    /**
     * Stub for Joomla\CMS\Factory.
     */
    class Factory
    {
        /**
         * @var object|null  Application instance for testing
         */
        protected static ?object $application = null;

        /**
         * Get the application object.
         *
         * @return  object
         */
        public static function getApplication(): object
        {
            if (static::$application === null) {
                static::$application = new class {
                    /** @var object|null */
                    protected ?object $identity = null;

                    /** @var object|null */
                    protected ?object $language = null;

                    /**
                     * @return  object|null
                     */
                    public function getIdentity(): ?object
                    {
                        return $this->identity;
                    }

                    /**
                     * @param   object|null  $identity  User identity
                     * @return  void
                     */
                    public function setIdentity(?object $identity): void
                    {
                        $this->identity = $identity;
                    }

                    /**
                     * @return  object
                     */
                    public function getLanguage(): object
                    {
                        if ($this->language === null) {
                            $this->language = new class {
                                /** @var string */
                                protected string $tag = 'en-GB';

                                /**
                                 * @return  string
                                 */
                                public function getTag(): string
                                {
                                    return $this->tag;
                                }

                                /**
                                 * @param   string  $tag  Language tag
                                 * @return  void
                                 */
                                public function setTag(string $tag): void
                                {
                                    $this->tag = $tag;
                                }
                            };
                        }

                        return $this->language;
                    }
                };
            }

            return static::$application;
        }

        /**
         * Set the application object (for testing).
         *
         * @param   object|null  $app  Application instance
         *
         * @return  void
         */
        public static function setApplication(?object $app): void
        {
            static::$application = $app;
        }

        /**
         * Reset factory state (for testing teardown).
         *
         * @return  void
         */
        public static function reset(): void
        {
            static::$application = null;
        }
    }
}

namespace Joomla\CMS\Language {

    /**
     * Stub for Joomla\CMS\Language\Text.
     */
    class Text
    {
        /**
         * Translate a string.
         *
         * @param   string  $string  Translation key
         *
         * @return  string  The key itself (passthrough for testing)
         */
        public static function _(string $string): string
        {
            return $string;
        }

        /**
         * Translate and format a string.
         *
         * @param   string  $string  Translation key
         * @param   mixed   ...$args  Format arguments
         *
         * @return  string
         */
        public static function sprintf(string $string, mixed ...$args): string
        {
            return \sprintf($string, ...$args);
        }
    }
}

namespace Joomla\CMS\Router {

    /**
     * Stub for Joomla\CMS\Router\Route.
     */
    class Route
    {
        /**
         * Route a URL (passthrough for testing).
         *
         * @param   string  $url  The URL to route
         *
         * @return  string  The URL unchanged
         */
        public static function _(string $url): string
        {
            return $url;
        }
    }
}
