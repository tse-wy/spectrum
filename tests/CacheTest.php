<?php
use WP_Mock\Tools\TestCase;
use TseWy\Spectrum\Patterns;

class CacheTest extends TestCase {

    public function setUp(): void {
        parent::setUp();
        \WP_Mock::setUp();
    }

    public function tearDown(): void {
        \WP_Mock::tearDown();
        parent::tearDown();
    }

    public function test_it_uses_cache_instead_of_fetching_api() {
        $fake_patterns = [
            [
                'name'    => 'spectrum/cached-pattern',
                'title'   => 'Cached Pattern',
                'content' => '<p>Cached pattern</p>',
            ],
        ];

        // Mock get_transient() to return cached data
        \WP_Mock::userFunction('get_transient', [
            'times'  => 1,
            'return' => $fake_patterns,
        ]);

        // Mock wp_remote_get() to fail the test if called
        \WP_Mock::userFunction('wp_remote_get', [
            'times' => 0,
        ]);

        // Mock register_block_pattern() to confirm it’s called
        \WP_Mock::userFunction('register_block_pattern', [
            'times' => 1,
            'args'  => [
                'spectrum/cached-pattern',
                \WP_Mock\Functions::type('array'),
            ],
        ]);

        $patterns_class = new Patterns();
        $patterns_class->register_patterns();

        $this->assertTrue(true, 'Cached data was used instead of API call');
    }
}
