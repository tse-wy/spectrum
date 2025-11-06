
# Spectrum

## License

GPL-3.0-or-later


A WordPress plugin that dynamically fetches, caches, and registers reusable block patterns from a remote Supabase database.

## Table of Contents
- [Overview](#overview)
- [Features](#features)
- [Setup](#setup)
- [Example Pattern Data](#example-pattern-data)
- [Testing](#testing)
- [Folder Structure](#folder-structure)
- [Developer Notes](#developer-notes)
- [Credits](#credits)
- [Contributing](#contributing)
- [Adding New Patterns](#adding-new-patterns)


## Overview

The Spectrum plugin allows developers and content editors to access a centralised library of block patterns that update automatically from a remote database.

Patterns are securely fetched from a Supabase REST API, cached locally using WordPress transients, and registered under the “Spectrum Patterns” category in the Block Editor.
This ensures all connected sites share the same up-to-date design components without requiring manual imports.


## Features

- Secure API authentication using a key stored in `wp-config.php` (`SPECTRUM_API_KEY`)
- Fetches block pattern data directly from Supabase REST API
- Caches data locally with WordPress transients (default: 12 hours)
- Automatic fallback to local JSON file (`mock-patterns.json`) if API fails
- Registers each pattern dynamically in the Block Editor under Spectrum Patterns
- Includes automated tests using PHPUnit and WP_Mock

## Setup

1. **Add your API key**

Add the following line to your WordPress `wp-config.php` file:

`define( 'SPECTRUM_API_KEY', 'your-supabase-service-key' );`

This key is used to authenticate with your Supabase REST API.

2. **Activate the Plugin**

Upload or clone the plugin into your WordPress installation:

`/wp-content/plugins/spectrum`

Then activate Spectrum from the WordPress Admin → Plugins.

3. **Fetch and Register Patterns**

When activated, the plugin will:

- Request pattern data from Supabase (`/rest/v1/patterns`).
- Cache the result using `set_transient()` (12-hour default)
- Register each pattern using `register_block_pattern()` under the Spectrum Patterns category.

## Example Pattern Data (from Supabase)

Each pattern record should include the following fields:

| Field       | Type           | Example                                                                 |
|-------------|----------------|-------------------------------------------------------------------------|
| `name`      | string         | `spectrum/hero-section`                                                 |
| `title`     | string         | `Hero Section with Background Image`                                    |
| `content`   | text (HTML)    | `<div class="wp-block-cover">...</div>`                                 |
| `description` | string      | `Large hero block with background image and call to action.`            |
| `categories` | array or string | `["spectrum"]`


## Testing

Spectrum inlcudes both manual and automated testing.

### Automated Testing

Automated tests use PHPUnit and WP_Mock to simulate WordPress behaviour.
To run the test suite:
```
composer install
vendor/bin/phpunit
```

| Test File          | Description                                                                 |
|--------------------|-----------------------------------------------------------------------------|
| `PatternsTest.php` | Ensures block patterns are registered correctly in the editor.              |
| `ApiFetchTest.php` | Confirms API data is fetched and cached when no transient exists.           |
| `CacheTest.php` *(optional)* | Confirms cached data is reused instead of making new API requests.    |

### Manual Testing Plan


| Test              | Steps                                                                 | Expected Result                                                                   |
|-------------------|----------------------------------------------------------------------|-----------------------------------------------------------------------------------|
| **Initial Activation** | Activate the plugin and check the debug log.                         | API fetches patterns and registers them successfully.                             |
| **Cached State**       | Reload the admin/editor page after activation.                       | No new API request is made; patterns are loaded from the transient cache.         |
| **Failure State**      | Remove `SPECTRUM_API_KEY` temporarily and reload the site.          | Plugin logs a missing key warning and loads fallback `mock-patterns.json` data.   |
| **Editor Test**        | Open Block Editor → *Patterns* → *Spectrum Patterns*.               | Spectrum patterns appear in the UI and can be inserted as functional block layouts. |


## Folder Structure

```text
spectrum/
├── includes/
│   ├── classes/
│   │   └── Patterns.php
│   └── patterns/
│       └── mock-patterns.json
├── tests/
│   ├── bootstrap.php
│   ├── PatternsTest.php
│   ├── ApiFetchTest.php
│   └── CacheTest.php
├── spectrum.php
├── composer.json
└── README.md
```

## Developer Notes

The caching duration can be adjusted by changing:

`const CACHE_DURATION = 12 * HOUR_IN_SECONDS;`

- The plugin uses Supabase REST API with `Bearer` authentication headers.

You can manually clear the cache using WP-CLI:

`wp transient delete spectrum_block_patterns`

## Credits
Spectrum was developed to support our agency’s scalable design system workflow.
It provides a centralised and maintainable way to distribute reusable WordPress block patterns across multiple projects.

Key development focuses included:

- Secure API communication
- Dynamic block pattern registration within the WordPress Block Editor
- Performance optimisation through caching and graceful fallback
- Automated testing for reliability and maintainability

## Contributing

We follow a standard Git-based workflow for ongoing development and maintenance.

### Branching

- `main` contains the stable, deployable plugin.
- Create feature branches using the format: `feature/short-description`.
- Fix branches use: `fix/short-description`

### Code Standards
- Follow WordPress PHP coding standards.
- Use meaningful commit messages.
- Add documentation when introducing new logic.

### Testing
- Run the automated test suite before submitting a pull request:
**PHPUnit**

```bash
composer install
vendor/bin/phpunit
  ```
**Clear Transient Cache**

```
wp transient delete spectrum_block_patterns

```

  - New features should include test coverage where practical.

## Adding New Patterns (Internal Use)

Patterns are managed centrally in our Supabase table `patterns`.

Required fields:
- `title` (string)
- `slug` (string, must be unique)
- `content` (HTML markup of the block pattern)
- `categories` (string or array)

Once a new pattern is added, it will appear automatically in all connected WordPress sites after the next cache refresh (up to 12 hours), or immediately if the cache is cleared using:


