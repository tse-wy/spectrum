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
	 * Fetch patterns from cache or API.
	 *
	 * @return array
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

		if ( 200 === $code && ! empty( $body ) ) {
			$data = json_decode( $body, true );

			if ( is_array( $data ) ) {
				set_transient( self::TRANSIENT_KEY, $data, self::CACHE_DURATION );
				return $data;
			}

			error_log( 'Spectrum: API returned invalid JSON.' );
		} else {
			error_log( 'Spectrum: API returned status ' . $code );
		}

		// Fallback to local mock file.
		$mock_file = SPECTRUM_PATH . 'includes/patterns/mock-patterns.json';

		if ( file_exists( $mock_file ) ) {
			$mock_json = file_get_contents( $mock_file );
			$data      = json_decode( $mock_json, true );

			if ( is_array( $data ) ) {
				set_transient( self::TRANSIENT_KEY, $data, self::CACHE_DURATION );
				return $data;
			}

			error_log( 'Spectrum: mock-patterns.json invalid JSON.' );
		}

		return [];
	}

	/**
	 * Used for scheduled refresh and fetch fresh without deleting cache first.
	 *
	 * @return array
	 */
	public function fetch_fresh_patterns(): array {
		delete_transient( self::TRANSIENT_KEY );
		return $this->get_patterns();
	}

	/**
	 * Register block patterns
	 */
	public function register_patterns(): void {
		$patterns = $this->get_patterns();

		if ( empty( $patterns ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Spectrum: No patterns available to register.' );
			}
			return;
		}

		foreach ( $patterns as $pattern ) {
			$slug = $pattern['slug'] ?? $pattern['name'] ?? '';

			if ( empty( $slug ) || empty( $pattern['content'] ) ) {
				continue;
			}

			$content = stripslashes( $pattern['content'] );
			$content = str_replace( '\\"', '"', $content );

			$categories   = (array) ( $pattern['categories'] ?? [] );
			$categories[] = 'spectrum';
			$categories   = array_unique( $categories );

			register_block_pattern(
				$slug,
				[
					'title'       => $pattern['title'] ?? 'Untitled Pattern',
					'content'     => $content,
					'description' => $pattern['description'] ?? '',
					'categories'  => $categories,
				]
			);
		}
	}

	/**
	 * Register main and dynamic categories.
	 */
	public function register_pattern_category(): void {
		if ( function_exists( 'register_block_pattern_category' ) ) {
			$registry = \WP_Block_Pattern_Categories_Registry::get_instance();

			if ( ! $registry->is_registered( 'spectrum' ) ) {
				register_block_pattern_category(
					'spectrum',
					[ 'label' => __( 'Spectrum Patterns', 'spectrum' ) ]
				);
			}

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
						}
					}
				}
			}
		}
	}
}
