#ifndef BASIC_MATH_H
#define BASIC_MATH_H

/**
 * Basic Math Library
 * 
 * A simple C library demonstrating basic mathematical operations
 * for use with the C-to-PHP FFI Converter.
 */

// Mathematical constants
#define PI 3.14159265359
#define E  2.71828182846
#define GOLDEN_RATIO 1.61803398875

/**
 * Add two integers
 * @param a First integer
 * @param b Second integer
 * @return Sum of a and b
 */
int add(int a, int b);

/**
 * Subtract two integers
 * @param a First integer
 * @param b Second integer
 * @return Difference of a and b
 */
int subtract(int a, int b);

/**
 * Multiply two integers
 * @param a First integer
 * @param b Second integer
 * @return Product of a and b
 */
int multiply(int a, int b);

/**
 * Divide two double precision numbers
 * @param a Dividend
 * @param b Divisor
 * @return Quotient of a and b, or 0.0 if b is zero
 */
double divide(double a, double b);

/**
 * Calculate power of a number
 * @param base Base number
 * @param exponent Exponent
 * @return base raised to the power of exponent
 */
double power(double base, int exponent);

/**
 * Calculate square root (simple implementation)
 * @param x Number to find square root of
 * @return Square root of x, or -1.0 if x is negative
 */
double square_root(double x);

/**
 * Calculate factorial of a number
 * @param n Number to calculate factorial of
 * @return Factorial of n, or -1 if n is negative
 */
long factorial(int n);

/**
 * Check if a number is prime
 * @param n Number to check
 * @return 1 if prime, 0 if not prime
 */
int is_prime(int n);

#endif // BASIC_MATH_H