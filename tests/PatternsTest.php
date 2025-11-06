<?php
use WP_Mock\Tools\TestCase;
use TseWy\Spectrum\Patterns;

class PatternsTest extends TestCase {

    public function setUp(): void {
        parent::setUp();
        \WP_Mock::setUp();
    }

    public function tearDown(): void {
        \WP_Mock::tearDown();
        parent::tearDown();
    }

    public function test_register_patterns_calls_register_block_pattern() {
        // Mock the WordPress function register_block_pattern.
        \WP_Mock::userFunction( 'register_block_pattern', [
            'times' => 1,
            'args'  => [
                'spectrum/test-pattern',
                \WP_Mock\Functions::type( 'array' )
            ],
        ]);

        // Create fake pattern data (like what Supabase would return)
        $patterns = [
            [
                'name'    => 'spectrum/test-pattern',
                'title'   => 'Test Pattern',
                'content' => '<p>Test content</p>',
            ],
        ];

        // Mock get_transient() to return our fake patterns.
        \WP_Mock::userFunction( 'get_transient', [
            'return' => $patterns,
        ]);

        $patterns_class = new Patterns();
        $patterns_class->register_patterns();

        // If register_block_pattern() was not called, the test fails.
        $this->assertTrue(true);
    }
}
