# Proclaim JSitemap Plugin

JSitemap Professional external plugin for the [Proclaim](https://github.com/Joomla-Bible-Study/Proclaim) Joomla component. Indexes studies/sermons, series, and teachers for HTML, XML, and RSS sitemap generation.

| Latest Release | License | PHP | Joomla |
|---|---|---|---|
| [![Latest Release](https://img.shields.io/github/v/release/Joomla-Bible-Study/proclaim_jsitemap_plugin)](https://github.com/Joomla-Bible-Study/proclaim_jsitemap_plugin/releases/latest) | [![License](https://img.shields.io/badge/License-GPL--2.0--or--later-blue)](LICENSE.txt) | [![PHP](https://img.shields.io/badge/PHP-8.3.0+-green)](https://www.php.net/) | [![Joomla](https://img.shields.io/badge/Joomla-5.x%20%2F%206.x-blue)](https://www.joomla.org/) |

## Requirements

- [Proclaim](https://github.com/Joomla-Bible-Study/Proclaim) component installed
- [JSitemap Professional](https://storejextensions.org/extensions/jsitemap.html) v4.28+
- PHP 8.3.0+
- Joomla 5.x or 6.x

## Installation

1. Download the latest `proclaim-x.x.x.zip` from [Releases](https://github.com/Joomla-Bible-Study/proclaim_jsitemap_plugin/releases)
2. In your Joomla admin, navigate to **Components > JSitemap > Data Sources**
3. Click on any data source (or create a new one) to open the edit view
4. In the **Import Plugin** accordion section, upload the zip file
5. The plugin will appear as a new "Proclaim" data source

## What Gets Indexed

| Content Type | Sitemap Role | Configurable |
|---|---|---|
| **Studies/Sermons** | Items grouped by series | Always included |
| **Series** | Categories (flat hierarchy) | Always included as categories; detail pages optional |
| **Teachers** | Additional items | Optional (enabled by default) |

Topics and message types are not indexed -- they have no frontend detail views in Proclaim.

## Configuration

After installation, edit the Proclaim data source in JSitemap to configure:

| Parameter | Default | Description |
|---|---|---|
| Fallback to created date | Yes | Use created/publish date when last modified is unavailable |
| Include teacher pages | Yes | Add teacher detail pages as sitemap items |
| Include series pages as items | Yes | Add series detail pages as individual items (in addition to category nodes) |
| Series IDs to include | (empty) | Comma-separated list to limit to specific series |
| Series IDs to exclude | (empty) | Comma-separated list to exclude specific series |

## Building from Source

```bash
# Build the installable zip
composer build

# or directly:
php build/build.php
```

Output: `build/proclaim-x.x.x.zip`

## Contributing

1. [Fork this repository](http://help.github.com/fork-a-repo/)
2. Create a topic branch
3. Make your changes following [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards
4. Submit a [pull request](http://help.github.com/send-pull-requests/)

## Contact

- **Email:** info@christianwebministries.org
- **Issues:** [GitHub Issues](https://github.com/Joomla-Bible-Study/proclaim_jsitemap_plugin/issues)

## License

Distributed under the GNU General Public License version 2 or later. See [LICENSE.txt](LICENSE.txt) for details.

(C) 2026 CWM Team.
