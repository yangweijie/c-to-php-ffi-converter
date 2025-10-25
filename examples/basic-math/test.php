<?php
/**
 * Test script for Basic Math Library Example
 * 
 * This script demonstrates how to use the generated PHP wrappers
 * for the basic math C library.
 */

declare(strict_types=1);

// Check if generated files exist
if (!file_exists('generated/bootstrap.php')) {
    echo "Error: Generated files not found.\n";
    echo "Please run 'make generate' first to create the PHP wrappers.\n";
    exit(1);
}

require_once 'generated/bootstrap.php';

use BasicMath\MathLibrary;
use BasicMath\MathConstants;
use Yangweijie\CWrapper\Exception\ValidationException;

echo "=== Basic Math Library Test ===\n\n";

try {
    // Create library instance
    $math = new MathLibrary();
    echo "✓ MathLibrary instance created successfully\n\n";
    
    // Test basic arithmetic operations
    echo "--- Basic Arithmetic ---\n";
    
    $a = 15;
    $b = 7;
    
    echo "Testing with a = $a, b = $b\n";
    echo "add($a, $b) = " . $math->add($a, $b) . "\n";
    echo "subtract($a, $b) = " . $math->subtract($a, $b) . "\n";
    echo "multiply($a, $b) = " . $math->multiply($a, $b) . "\n";
    
    $x = 15.0;
    $y = 3.0;
    echo "divide($x, $y) = " . $math->divide($x, $y) . "\n";
    
    echo "\n--- Advanced Functions ---\n";
    
    // Test power function
    $base = 2.0;
    $exp = 8;
    echo "power($base, $exp) = " . $math->power($base, $exp) . "\n";
    
    // Test square root
    $num = 16.0;
    echo "square_root($num) = " . $math->squareRoot($num) . "\n";
    
    // Test factorial
    $n = 5;
    echo "factorial($n) = " . $math->factorial($n) . "\n";
    
    // Test prime checking
    $primes = [2, 3, 4, 5, 17, 18, 19];
    echo "Prime checking:\n";
    foreach ($primes as $num) {
        $isPrime = $math->isPrime($num) ? 'prime' : 'not prime';
        echo "  $num is $isPrime\n";
    }
    
    echo "\n--- Constants ---\n";
    
    // Test constants
    echo "PI = " . MathConstants::PI . "\n";
    echo "E = " . MathConstants::E . "\n";
    echo "Golden Ratio = " . MathConstants::GOLDEN_RATIO . "\n";
    
    echo "\n--- Error Handling ---\n";
    
    // Test division by zero
    echo "Testing division by zero:\n";
    try {
        $result = $math->divide(10.0, 0.0);
        echo "divide(10.0, 0.0) = $result (handled gracefully)\n";
    } catch (ValidationException $e) {
        echo "ValidationException: " . $e->getMessage() . "\n";
    }
    
    // Test negative square root
    echo "Testing negative square root:\n";
    $result = $math->squareRoot(-4.0);
    echo "square_root(-4.0) = $result (error value)\n";
    
    // Test parameter validation
    echo "Testing parameter validation:\n";
    try {
        $result = $math->add("not a number", 5);
        echo "This should not be reached\n";
    } catch (ValidationException $e) {
        echo "✓ Parameter validation caught invalid type: " . $e->getMessage() . "\n";
    }
    
    echo "\n--- Performance Test ---\n";
    
    // Simple performance test
    $iterations = 100000;
    $start = microtime(true);
    
    for ($i = 0; $i < $iterations; $i++) {
        $math->add($i, $i + 1);
    }
    
    $end = microtime(true);
    $duration = $end - $start;
    $ops_per_sec = $iterations / $duration;
    
    echo "Performed $iterations additions in " . number_format($duration, 4) . " seconds\n";
    echo "Operations per second: " . number_format($ops_per_sec, 0) . "\n";
    
    echo "\n=== All Tests Completed Successfully! ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

// Additional usage examples
echo "\n--- Usage Examples ---\n";

// Example 1: Calculator-like usage
function calculate($operation, $a, $b) {
    global $math;
    
    switch ($operation) {
        case '+':
            return $math->add((int)$a, (int)$b);
        case '-':
            return $math->subtract((int)$a, (int)$b);
        case '*':
            return $math->multiply((int)$a, (int)$b);
        case '/':
            return $math->divide((float)$a, (float)$b);
        default:
            throw new InvalidArgumentException("Unknown operation: $operation");
    }
}

echo "Calculator examples:\n";
echo "5 + 3 = " . calculate('+', 5, 3) . "\n";
echo "10 - 4 = " . calculate('-', 10, 4) . "\n";
echo "6 * 7 = " . calculate('*', 6, 7) . "\n";
echo "15 / 3 = " . calculate('/', 15, 3) . "\n";

// Example 2: Mathematical series
echo "\nFibonacci-like series using add function:\n";
$a = 0;
$b = 1;
echo "$a, $b";
for ($i = 0; $i < 8; $i++) {
    $next = $math->add($a, $b);
    echo ", $next";
    $a = $b;
    $b = $next;
}
echo "\n";

// Example 3: Prime number finder
echo "\nFirst 10 prime numbers:\n";
$count = 0;
$num = 2;
$primes = [];
while ($count < 10) {
    if ($math->isPrime($num)) {
        $primes[] = $num;
        $count++;
    }
    $num++;
}
echo implode(', ', $primes) . "\n";

echo "\n--- Test Complete ---\n";