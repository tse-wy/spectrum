<?php
/**
 * Plugin Name:       Spectrum
 * Description:       Fetches custom block patterns from a shared central library for use in the editor.
 * Version:           0.1.0
 * Requires at least: 6.8
 * Requires PHP:      8.0
 * Requires Plugins:
 * Author:            Wing Yan Tse
 * Author URI:        https://eighteen73.co.uk
 * Text Domain:       spectrum
 * Domain Path:
 *
 * @package           TseWy\Spectrum
 */

namespace TseWy\Spectrum;

defined( 'ABSPATH' ) || exit;

// Useful global constants.
define( 'SPECTRUM_URL', plugin_dir_url( __FILE__ ) );
define( 'SPECTRUM_PATH', plugin_dir_path( __FILE__ ) );
define( 'SPECTRUM_INC', SPECTRUM_PATH . 'includes/' );


// Require the autoloader.
$autoloader = SPECTRUM_PATH . 'vendor/autoload.php';
if ( file_exists( $autoloader ) ) {
	require_once $autoloader;
} else {
	// Display admin notice if dependencies are missing.
	add_action(
		'admin_notices',
		function () {
			printf(
				'<div class="notice notice-error"><p><strong>%s</strong>: %s</p></div>',
				esc_html__( 'Spectrum', 'spectrum' ),
				sprintf(
					esc_html__( 'Composer dependencies not found. Please run %s to install required dependencies.', 'spectrum' ),
					'composer install'
				)
			);
		}
	);
	return;
}

/**
 * Register the Spectrum pattern category early.
 */
add_action(
	'init',
	function () {
		if ( function_exists( 'register_block_pattern_category' ) ) {
			register_block_pattern_category(
				'spectrum',
				[ 'label' => __( 'Spectrum Patterns', 'spectrum' ) ]
			);
		}
	},
	1
);

// Initialise the main plugin instance.
Plugin::instance()->setup();

/**
 * Load Spectrum Patterns.
 * Calls the setup() method inside your Patterns class.
 */
add_action(
	'init',
	function () {
		( new Patterns() )->setup();
	},
	5
);

/**
 * Restrict plugin deactivation to administrators.
 *
 * Hides the "Deactivate" link for non-admins.
 * Blocks manual deactivation attempts by URL.
 */
add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	function ( $actions ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			unset( $actions['deactivate'] );
		}
		return $actions;
	}
);

add_action(
	'admin_init',
	function () {
		if (
		isset( $_GET['action'], $_GET['plugin'] ) &&
		$_GET['action'] === 'deactivate' &&
		$_GET['plugin'] === plugin_basename( __FILE__ ) &&
		! current_user_can( 'manage_options' )
		) {
			wp_die(
				esc_html__( 'Sorry, you do not have permission to deactivate this plugin.', 'spectrum' ),
				esc_html__( 'Access Denied', 'spectrum' ),
				[ 'response' => 403 ]
			);
		}
	}
);

/**
 * Schedule a daily refresh of Spectrum patterns and only replaces cache if fetch suceeds.
 */
add_action(
	'spectrum_refresh_patterns_daily',
	function () {
		$patterns = new \TseWy\Spectrum\Patterns();

		$new_data = $patterns->fetch_fresh_patterns();

		if ( ! empty( $new_data ) ) {
			set_transient( \TseWy\Spectrum\Patterns::TRANSIENT_KEY, $new_data, \TseWy\Spectrum\Patterns::CACHE_DURATION );

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Spectrum: Daily pattern refresh succeeded (cache updated).' );
			}
		} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Spectrum: Daily pattern refresh failed, keeping existing cached patterns.' );
		}
	}
);

/**
 * On plugin activation, schedule the daily event.
 */
register_activation_hook(
	__FILE__,
	function () {
		if ( ! wp_next_scheduled( 'spectrum_refresh_patterns_daily' ) ) {
			wp_schedule_event( time(), 'daily', 'spectrum_refresh_patterns_daily' );
		}
	}
);


/**
 * Clear daily event on deactivation
 */
register_deactivation_hook(
	__FILE__,
	function () {
		wp_clear_scheduled_hook( 'spectrum_refresh_patterns_daily' );
	}
);
