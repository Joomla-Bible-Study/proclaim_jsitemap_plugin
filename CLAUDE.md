# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

JSitemap Professional external plugin that extends **Proclaim** (`com_proclaim`) data into JSitemap sitemaps. This is a companion plugin to the Proclaim Joomla component — it implements the `JMapFilePlugin` interface to feed Proclaim studies, series, and teachers into JSitemap for HTML, XML, and RSS sitemap generation.

**PHP Requirement:** 8.3.0+
**Joomla Compatibility:** 5.x and 6.x
**JSitemap Compatibility:** v4.28+ (Pro)
**Parent Project:** [Proclaim](https://github.com/Joomla-Bible-Study/Proclaim) — all coding standards flow from Proclaim

## Build

```bash
# Build the installable zip package (outputs to build/proclaim.zip)
composer build
# or directly:
php build/build.php
```

The zip is installed via **JSitemap Admin > Data Sources > Import Plugin** (upload the zip file). JSitemap extracts it to `administrator/components/com_jmap/plugins/proclaim/`.

## Test

```bash
composer test
# or directly:
./vendor/bin/phpunit
```

Unit tests use stub classes for JSitemap and Joomla dependencies — no database or Joomla runtime needed.

## Code Style

This plugin follows the **same coding standards as Proclaim** — **PSR-12** with the additional rules below. All code must conform to these rules.

### PSR-12 Core Requirements

- **Indentation**: 4 spaces, no tabs
- **Line length**: Should not exceed 120 characters
- **Braces**: Opening brace on same line for control structures, new line for classes/methods
- **Type casts**: Always include a space after the cast operator: `(int) $var`, `(string) $value`
- **Namespaces**: One blank line after namespace declaration
- **Use statements**: Grouped by type (classes, functions, constants), alphabetically sorted, one blank line after use block

### Proclaim PHP CS Fixer Rules (applied manually here)

- **Short array syntax**: Use `[]` not `array()`
- **No trailing comma in single-line**: `[$foo, $bar]` not `[$foo, $bar,]`
- **Trailing comma in multiline arrays**: Always add trailing comma on last element
- **Aligned operators**: `=>` aligned with single-space-minimal, `=` aligned, `??=` aligned
- **No unused imports**: Remove any `use` statements that aren't referenced
- **No global namespace imports**: Do not `use` classes from the global namespace (use `\stdClass` inline)
- **Ordered imports**: Alpha-sorted, grouped by class/function/const
- **No useless else**: Remove else blocks that follow a return/throw/continue/break
- **Native function invocation**: Prefix compiler-optimized native functions with `\` (e.g., `\count()`, `\is_object()`)
- **Nullable defaults**: Add `?` to type declarations for parameters with `= null`
- **No unneeded control parentheses**: Don't wrap unnecessary parentheses around control expressions
- **Combine consecutive isset/unset**: Merge adjacent `isset()` or `unset()` calls
- **No useless sprintf**: Don't call `sprintf()` with only one argument

### Naming Conventions (from Proclaim)

- Class naming: `Cwm` prefix for Proclaim entities (e.g., `CwmparamsModel`)
- This plugin uses `JMapFilePlugin{Name}` naming per JSitemap SDK convention

## Architecture

This is a single-class plugin — all logic lives in `proclaim.php`.

### How JSitemap Loads Plugins

JSitemap discovers plugins by scanning `administrator/components/com_jmap/plugins/` for `*.php` files. Each must define a class named `JMapFilePlugin{Name}` implementing `JMapFilePlugin`. JSitemap's `SitemapModel.php` (lines ~1079–1133) creates backward-compatibility aliases for legacy Joomla classes (`JRegistry`, `JDatabase`, `JMapModel`, `JMapException`, `JMapLanguageMultilang`) before loading plugins, so these type hints work on Joomla 5/6 despite being non-namespaced.

### Plugin Interface Contract

`JMapFilePlugin::getSourceData($pluginParams, $db, $sitemapModel)` must return:

| Key | Required | Description |
|-----|----------|-------------|
| `items` | Yes | Array of objects with `->title`, `->link`, and optionally `->lastmod`, `->access`, `->metakey`, `->publish_up` |
| `items_tree` | No | Items grouped by category ID (numeric array index = category ID) |
| `categories_tree` | No | Categories grouped by parent ID; objects need `->category_id`, `->category_title`, `->category_link`, `->lastmod` |

### Data Model Mapping

The plugin maps Proclaim entities to JSitemap's category/item model:
- **Series** -> JSitemap categories (flat, all parent 0)
- **Studies** -> JSitemap items, grouped under their series
- **Teachers** -> Additional items (optional, controlled by `include_teachers` param)
- **Series detail pages** -> Additional items (optional, controlled by `include_series_items` param)

Topics and message types are **not** included — they have no frontend detail views in Proclaim.

### Proclaim DB Tables (prefix: `#__bsms_`)

- `studies` — main sermons (id, alias, studytitle, series_id, published, access, language, modified, created, publish_up, publish_down, studydate, studytext)
- `series` — sermon series (id, alias, series_text, published, access, language, modified)
- `teachers` — teachers (id, alias, teachername, published, access, language, modified, created)

### Proclaim Frontend Routes

| View | URL Pattern |
|------|-------------|
| Single study | `index.php?option=com_proclaim&view=cwmsermon&id={id}:{alias}` |
| Series display | `index.php?option=com_proclaim&view=cwmseriesdisplay&id={id}:{alias}` |
| Teacher detail | `index.php?option=com_proclaim&view=cwmteacher&id={id}:{alias}` |

### Plugin Configuration Parameters (proclaim.xml)

- `publish_up_fallback` — Fall back to created date when modified is empty (default: yes)
- `include_teachers` — Include teacher pages in sitemap (default: yes)
- `include_series_items` — Include series detail pages as items (default: yes)
- `included_series` / `excluded_series` — Comma-separated series ID filters

## File Structure

```
proclaim.php              # Plugin class (JMapFilePluginProclaim)
proclaim.xml              # JSitemap data source manifest
language/en-GB/           # Language strings
vendor/                   # Reference materials (gitignored, not distributed)
  jsitemap_plugin_sdk/    # SDK docs and K2 sample plugin
  jsitemap_pro_v4.28_.../  # JSitemap Pro source for interface reference
build/                    # Build artifacts
```

## Reference Materials

- JSitemap plugin interface: `vendor/jsitemap_pro_v4.28_.../admin/Framework/File/Plugin.php`
- K2 sample plugin (reference implementation): `vendor/jsitemap_plugin_sdk/k2_sample/k2.php`
- Proclaim component source: `/Volumes/BCCExt_APFS_Extreme_Pro/GitHub/Proclaim/`
- Proclaim CLAUDE.md (canonical standards): `/Volumes/BCCExt_APFS_Extreme_Pro/GitHub/Proclaim/CLAUDE.md`
- Proclaim PHP CS Fixer config: `/Volumes/BCCExt_APFS_Extreme_Pro/GitHub/Proclaim/.php-cs-fixer.dist.php`
- Proclaim DB schema: `Proclaim/admin/sql/install.mysql.utf8.sql`
- Proclaim route helpers: `Proclaim/site/src/Helper/Cwmhelperroute.php` and `CwmrouteHelper.php`