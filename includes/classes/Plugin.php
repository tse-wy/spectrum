<?php
/**
 * Main plugin class.
 *
 * @package TseWy\Spectrum
 */

namespace TseWy\Spectrum;

defined( 'ABSPATH' ) || exit;

/**
 * Main Plugin class.
 */
class Plugin {

	use Singleton;

	/**
	 * Setup the plugin.
	 *
	 * @return void
	 */
	public function setup(): void {
		add_action( 'init', [ $this, 'load_textdomain' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
	}

	/**
	 * Load plugin textdomain.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'spectrum',
			false,
			SPECTRUM_PATH . '/languages'
		);
	}

	/**
	 * Plugin activation.
	 *
	 * @return void
	 */
	public static function activation(): void {}

	/**
	 * Plugin deactivation.
	 *
	 * @return void
	 */
	public static function deactivation(): void {}

	/**
	 * Enqueue frontend scripts and styles.
	 *
	 * @return void
	 */
	public function enqueue_scripts(): void {}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts(): void {}
}
