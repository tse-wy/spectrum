<?php
use WP_Mock\Tools\TestCase;
use TseWy\Spectrum\Patterns;

class ApiFetchTest extends TestCase {

    public function setUp(): void {
        parent::setUp();
        \WP_Mock::setUp();
    }

    public function tearDown(): void {
        \WP_Mock::tearDown();
        parent::tearDown();
    }

    public function test_it_fetches_from_api_when_no_cache() {
        // Make sure the constant exists, so the plugin thinks an API key is available
        if ( ! defined( 'SPECTRUM_API_KEY' ) ) {
            define( 'SPECTRUM_API_KEY', 'test-key' );
        }

        // Prepare a fake API response (what Supabase would return)
        $fake_api_response = [
            [
                'name'    => 'spectrum/api-pattern',
                'title'   => 'Fetched from API',
                'content' => '<p>API content</p>',
            ],
        ];

        // Mock get_transient() to simulate "no cache found"
        \WP_Mock::userFunction('get_transient', [
            'times'  => 1,
            'return' => false,
        ]);

		// Mock is_wp_error (fixes the undefined function error)
		\WP_Mock::userFunction('is_wp_error', [
			'return' => false,
		]);

        // Mock wp_remote_get() — this is the actual API request
        \WP_Mock::userFunction('wp_remote_get', [
            'times'  => 1, // must be called exactly once
            'return' => [
                'response' => [ 'code' => 200 ],
                'body'     => json_encode($fake_api_response),
            ],
        ]);

        // Mock helpers WordPress normally calls
        \WP_Mock::userFunction('wp_remote_retrieve_response_code', [
            'return' => 200,
        ]);

        \WP_Mock::userFunction('wp_remote_retrieve_body', [
            'return' => json_encode($fake_api_response),
        ]);

        // Expect the plugin to cache the results
        \WP_Mock::userFunction('set_transient', [
            'times' => 1,
        ]);

        // Expect a pattern registration call
        \WP_Mock::userFunction('register_block_pattern', [
            'times' => 1,
            'args'  => [
                'spectrum/api-pattern',
                \WP_Mock\Functions::type('array'),
            ],
        ]);

        // Run the plugin logic
        $patterns_class = new Patterns();
        $patterns_class->register_patterns();

        // Final assertion — just a sanity check message
        $this->assertTrue(true, 'Plugin fetched patterns from API and registered them');
    }
}
