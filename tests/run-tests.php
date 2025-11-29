<?php
/**
 * Test Runner for Comfort Comm Chatbot
 *
 * Run all tests: php tests/run-tests.php
 * Run specific test: php tests/run-tests.php --test=vector-search
 *
 * @package comfort-comm-chatbot
 */

// Check if running from command line
if (php_sapi_name() !== 'cli') {
    die('Tests must be run from command line');
}

// Parse command line arguments
$options = getopt('', ['test::', 'verbose']);
$specific_test = $options['test'] ?? null;
$verbose = isset($options['verbose']);

// Load WordPress
$wp_load_path = dirname(__FILE__) . '/../../../../wp-load.php';
if (!file_exists($wp_load_path)) {
    die("Error: Could not find wp-load.php at $wp_load_path\n");
}

// Suppress deprecation warnings from other plugins
error_reporting(E_ERROR | E_WARNING | E_PARSE);

require_once $wp_load_path;

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║         Comfort Comm Chatbot - Test Suite                    ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// Test results tracking
$total_tests = 0;
$passed_tests = 0;
$failed_tests = 0;
$failed_test_names = [];

/**
 * Assert function for tests
 */
function test_assert($condition, $test_name, $message = '') {
    global $total_tests, $passed_tests, $failed_tests, $failed_test_names, $verbose;

    $total_tests++;

    if ($condition) {
        $passed_tests++;
        echo "  ✓ $test_name\n";
        if ($verbose && $message) {
            echo "    └─ $message\n";
        }
        return true;
    } else {
        $failed_tests++;
        $failed_test_names[] = $test_name;
        echo "  ✗ $test_name\n";
        if ($message) {
            echo "    └─ FAILED: $message\n";
        }
        return false;
    }
}

/**
 * Run a test file
 */
function run_test_file($file_path, $test_name) {
    global $verbose;

    if (!file_exists($file_path)) {
        echo "  ⚠ Test file not found: $file_path\n";
        return false;
    }

    echo "\n┌─ $test_name\n";
    echo "│\n";

    include $file_path;

    echo "│\n";
    echo "└─────────────────────────────────────────────────────────────\n";

    return true;
}

// Get all test files
$test_dir = dirname(__FILE__);
$test_files = glob($test_dir . '/test-*.php');

// Filter by specific test if requested
if ($specific_test) {
    $test_files = array_filter($test_files, function($file) use ($specific_test) {
        return strpos($file, "test-$specific_test") !== false;
    });

    if (empty($test_files)) {
        echo "No test file found matching: $specific_test\n";
        echo "Available tests:\n";
        foreach (glob($test_dir . '/test-*.php') as $file) {
            $name = str_replace(['test-', '.php'], '', basename($file));
            echo "  - $name\n";
        }
        exit(1);
    }
}

// Run tests
foreach ($test_files as $test_file) {
    $test_name = str_replace(['.php', 'test-'], '', basename($test_file));
    $test_name = ucwords(str_replace('-', ' ', $test_name));
    run_test_file($test_file, $test_name);
}

// Summary
echo "\n";
echo "══════════════════════════════════════════════════════════════\n";
echo "  TEST SUMMARY\n";
echo "══════════════════════════════════════════════════════════════\n";
echo "  Total:  $total_tests\n";
echo "  Passed: $passed_tests ✓\n";
echo "  Failed: $failed_tests ✗\n";

if ($failed_tests > 0) {
    echo "\n  Failed tests:\n";
    foreach ($failed_test_names as $name) {
        echo "    - $name\n";
    }
    echo "\n";
    exit(1);
} else {
    echo "\n  All tests passed! 🎉\n\n";
    exit(0);
}
