<?php
/**
 * Pattern manager. Handles fetching, caching, and registering remote block patterns.
 *
 * @package Spectrum
 */

namespace TseWy\Spectrum;

defined( 'ABSPATH' ) || exit;

/**
 * Pattern manager. Handles fetching, caching, and registering remote block patterns.
 */
class Patterns {

	const TRANSIENT_KEY  = 'spectrum_block_patterns';
	const CACHE_DURATION = 12 * HOUR_IN_SECONDS;
	const API_URL        = 'https://yiwxpfphcyrdpwutnggf.supabase.co/rest/v1/patterns?select=*';

	/**
	 * Hook pattern registration into init.
	 */
	public function setup(): void {
		add_action( 'init', [ $this, 'register_pattern_category' ], 5 );
		add_action( 'init', [ $this, 'register_patterns' ], 10 );
	}

	/**
	 * Fetch data from cache or API.
	 *
	 * @return array Pattern data.
	 */
	public function get_patterns(): array {
		$cached = get_transient( self::TRANSIENT_KEY );
		if ( false !== $cached ) {
			return $cached;
		}

		$api_key = defined( 'SPECTRUM_API_KEY' ) ? \SPECTRUM_API_KEY : '';

		if ( '' === $api_key ) {
			error_log( 'Spectrum: missing SPECTRUM_API_KEY.' );
			return [];
		}

		error_log( 'Spectrum: fetching patterns from ' . self::API_URL );
		error_log( 'Spectrum: using Bearer Auth with API key (first 10 chars): ' . substr( $api_key, 0, 10 ) . '...' );

		$response = wp_remote_get(
			self::API_URL,
			[
				'headers' => [
					'Authorization' => 'Bearer ' . $api_key,
					'apikey'        => $api_key,
					'Accept'        => 'application/json',
				],
				'timeout' => 15,
			]
		);

		if ( is_wp_error( $response ) ) {
			error_log( 'Spectrum API error: ' . $response->get_error_message() );
			return [];
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = [];

		if ( 200 === $code && ! empty( $body ) ) {
			$data = json_decode( $body, true );

			if ( is_array( $data ) ) {
				set_transient( self::TRANSIENT_KEY, $data, self::CACHE_DURATION );
				error_log( 'Spectrum: fetched and cached ' . count( $data ) . ' patterns successfully.' );
				return $data;
			}

			error_log( 'Spectrum API invalid JSON.' );
		} else {
			error_log( 'Spectrum API bad response: ' . $code );
		}

		// Fallback to local mock file.
		$mock_file = SPECTRUM_PATH . 'includes/patterns/mock-patterns.json';

		if ( file_exists( $mock_file ) ) {
			error_log( 'Spectrum: using local mock-patterns.json fallback.' );

			$mock_data = file_get_contents( $mock_file );
			$data      = json_decode( $mock_data, true );

			if ( is_array( $data ) ) {
				set_transient( self::TRANSIENT_KEY, $data, self::CACHE_DURATION );
				return $data;
			}

			error_log( 'Spectrum: mock-patterns.json invalid JSON.' );
		}

		return [];
	}

	/**
	 * Register block patterns in the WordPress editor.
	 */
	public function register_patterns(): void {
		error_log( 'Spectrum: register_patterns() is running.' );

		$patterns = $this->get_patterns();

		if ( empty( $patterns ) ) {
			error_log( 'Spectrum: No patterns returned.' );
			return;
		}

		foreach ( $patterns as $pattern ) {
			$slug = $pattern['slug'] ?? $pattern['name'] ?? '';

			if ( empty( $slug ) || empty( $pattern['content'] ) ) {
				continue;
			}

			$content = stripslashes( $pattern['content'] );
			$content = str_replace( '\\"', '"', $content );

			// Merge categories with "spectrum" as default.
			$categories = (array) ( $pattern['categories'] ?? [] );
			if ( empty( $categories ) ) {
				$categories = [ 'spectrum' ];
			} else {
				$categories[] = 'spectrum';
			}

			error_log( 'Spectrum: registering pattern ' . $slug );

			register_block_pattern(
				$slug,
				[
					'title'       => $pattern['title'] ?? 'Untitled Pattern',
					'content'     => $content,
					'description' => $pattern['description'] ?? '',
					'categories'  => array_unique( $categories ),
				]
			);
		}
	}

	/**
	 * Register the Spectrum category and any dynamic ones from Supabase.
	 */
	public function register_pattern_category(): void {
		if ( function_exists( 'register_block_pattern_category' ) ) {
			$registry = \WP_Block_Pattern_Categories_Registry::get_instance();

			// Ensure main Spectrum category.
			if ( ! $registry->is_registered( 'spectrum' ) ) {
				register_block_pattern_category(
					'spectrum',
					[ 'label' => __( 'Spectrum Patterns', 'spectrum' ) ]
				);
				error_log( 'Spectrum: "Spectrum Patterns" category registered.' );
			}

			// Register dynamic categories from cached patterns.
			$cached = get_transient( self::TRANSIENT_KEY );
			if ( is_array( $cached ) ) {
				foreach ( $cached as $pattern ) {
					if ( empty( $pattern['categories'] ) ) {
						continue;
					}
					foreach ( (array) $pattern['categories'] as $cat ) {
						$slug = sanitize_title( $cat );
						if ( ! $registry->is_registered( $slug ) ) {
							register_block_pattern_category(
								$slug,
								[ 'label' => 'Spectrum: ' . esc_html( $cat ) ]
							);
							error_log( 'Spectrum: registered dynamic category "' . $cat . '"' );
						}
					}
				}
			}
		}
	}
}
