<?php
/**
 * Bootstraps WP_Mock for Spectrum tests.
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
    define( 'HOUR_IN_SECONDS', 3600 );
}

// Optional but recommended: allow mocking custom plugin functions
\WP_Mock::setUsePatchwork(true);

// Optional: enable strict mode for extra safety
// \WP_Mock::activateStrictMode();

\WP_Mock::bootstrap();
