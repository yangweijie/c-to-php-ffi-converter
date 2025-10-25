<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Tests\Unit\Analyzer;

use PHPUnit\Framework\TestCase;
use Yangweijie\CWrapper\Analyzer\DependencyResolver;
use Yangweijie\CWrapper\Exception\AnalysisException;

class DependencyResolverTest extends TestCase
{
    private DependencyResolver $resolver;
    private string $fixturesPath;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->fixturesPath = __DIR__ . '/../../Fixtures';
        $this->tempDir = sys_get_temp_dir() . '/dependency_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        
        $this->resolver = new DependencyResolver([]);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testResolveDependenciesSimple(): void
    {
        $headerPath = $this->fixturesPath . '/dependency2.h';
        $dependencies = $this->resolver->resolveDependencies($headerPath);

        // dependency2.h has no includes, so should return empty array
        $this->assertEmpty($dependencies);
    }

    public function testResolveDependenciesWithLocalIncludes(): void
    {
        $headerPath = $this->fixturesPath . '/dependency1.h';
        $searchPaths = [$this->fixturesPath];
        
        $dependencies = $this->resolver->resolveDependencies($headerPath, $searchPaths);

        // dependency1.h includes dependency2.h
        $this->assertCount(1, $dependencies);
        $this->assertStringEndsWith('dependency2.h', $dependencies[0]);
    }

    public function testResolveDependenciesNonExistentFile(): void
    {
        $this->expectException(AnalysisException::class);
        $this->expectExceptionMessage('Header file not found');

        $this->resolver->resolveDependencies('/non/existent/file.h');
    }

    public function testResolveDependenciesWithSystemIncludes(): void
    {
        $headerPath = $this->fixturesPath . '/complex.h';
        $searchPaths = [$this->fixturesPath];
        
        $dependencies = $this->resolver->resolveDependencies($headerPath, $searchPaths);

        // complex.h includes sample.h (which should be found)
        // stdio.h and stdlib.h are system headers and may not be found
        $this->assertGreaterThanOrEqual(1, count($dependencies));
        
        $foundSampleH = false;
        foreach ($dependencies as $dependency) {
            if (str_contains($dependency, 'sample.h')) {
                $foundSampleH = true;
                break;
            }
        }
        $this->assertTrue($foundSampleH, 'sample.h should be found in dependencies');
    }

    public function testCreateCompilationOrderSimple(): void
    {
        $headerPaths = [
            $this->fixturesPath . '/dependency2.h'
        ];
        
        $order = $this->resolver->createCompilationOrder($headerPaths);
        
        $this->assertCount(1, $order);
        $this->assertStringEndsWith('dependency2.h', $order[0]);
    }

    public function testCreateCompilationOrderWithDependencies(): void
    {
        $headerPaths = [
            $this->fixturesPath . '/dependency1.h',
            $this->fixturesPath . '/dependency2.h'
        ];
        $searchPaths = [$this->fixturesPath];
        
        $order = $this->resolver->createCompilationOrder($headerPaths, $searchPaths);
        
        // dependency2.h should come before dependency1.h since dependency1.h includes dependency2.h
        $this->assertGreaterThanOrEqual(2, count($order));
        
        $dep1Index = -1;
        $dep2Index = -1;
        
        foreach ($order as $index => $file) {
            if (str_contains($file, 'dependency1.h')) {
                $dep1Index = $index;
            }
            if (str_contains($file, 'dependency2.h')) {
                $dep2Index = $index;
            }
        }
        
        $this->assertNotEquals(-1, $dep1Index, 'dependency1.h should be in compilation order');
        $this->assertNotEquals(-1, $dep2Index, 'dependency2.h should be in compilation order');
        $this->assertLessThan($dep1Index, $dep2Index, 'dependency2.h should come before dependency1.h');
    }

    public function testCreateCompilationOrderCircularDependency(): void
    {
        // Create circular dependency files
        $circularA = $this->tempDir . '/circular_a.h';
        $circularB = $this->tempDir . '/circular_b.h';
        
        file_put_contents($circularA, '#include "circular_b.h"');
        file_put_contents($circularB, '#include "circular_a.h"');
        
        $this->expectException(AnalysisException::class);
        $this->expectExceptionMessage('Circular dependency detected');
        
        $this->resolver->createCompilationOrder([$circularA], [$this->tempDir]);
    }

    public function testGetDependencyGraph(): void
    {
        $headerPaths = [
            $this->fixturesPath . '/dependency1.h',
            $this->fixturesPath . '/dependency2.h'
        ];
        $searchPaths = [$this->fixturesPath];
        
        $graph = $this->resolver->getDependencyGraph($headerPaths, $searchPaths);
        
        $this->assertArrayHasKey($this->fixturesPath . '/dependency1.h', $graph);
        $this->assertArrayHasKey($this->fixturesPath . '/dependency2.h', $graph);
        
        // dependency1.h should have dependency2.h as a dependency
        $dep1Dependencies = $graph[$this->fixturesPath . '/dependency1.h'];
        $this->assertGreaterThan(0, count($dep1Dependencies));
        
        $foundDep2 = false;
        foreach ($dep1Dependencies as $dep) {
            if (str_contains($dep, 'dependency2.h')) {
                $foundDep2 = true;
                break;
            }
        }
        $this->assertTrue($foundDep2, 'dependency1.h should depend on dependency2.h');
        
        // dependency2.h should have no dependencies
        $dep2Dependencies = $graph[$this->fixturesPath . '/dependency2.h'];
        $this->assertEmpty($dep2Dependencies);
    }

    public function testResolveIncludePathRelative(): void
    {
        // Create test files
        $subDir = $this->tempDir . '/subdir';
        mkdir($subDir, 0755, true);
        
        $mainHeader = $this->tempDir . '/main.h';
        $subHeader = $subDir . '/sub.h';
        
        file_put_contents($mainHeader, '#include "subdir/sub.h"');
        file_put_contents($subHeader, '// sub header');
        
        $dependencies = $this->resolver->resolveDependencies($mainHeader);
        
        $this->assertCount(1, $dependencies);
        $this->assertStringEndsWith('sub.h', $dependencies[0]);
    }

    public function testResolveIncludePathWithSearchPaths(): void
    {
        // Create test files in search path
        $searchDir = $this->tempDir . '/search';
        mkdir($searchDir, 0755, true);
        
        $mainHeader = $this->tempDir . '/main.h';
        $searchHeader = $searchDir . '/search_header.h';
        
        file_put_contents($mainHeader, '#include "search_header.h"');
        file_put_contents($searchHeader, '// search header');
        
        $dependencies = $this->resolver->resolveDependencies($mainHeader, [$searchDir]);
        
        $this->assertCount(1, $dependencies);
        $this->assertStringEndsWith('search_header.h', $dependencies[0]);
    }

    public function testResolveIncludePathNotFound(): void
    {
        $mainHeader = $this->tempDir . '/main.h';
        file_put_contents($mainHeader, '#include "nonexistent.h"');
        
        // Should not throw exception, just return empty dependencies
        $dependencies = $this->resolver->resolveDependencies($mainHeader);
        $this->assertEmpty($dependencies);
    }

    public function testCachingBehavior(): void
    {
        $headerPath = $this->fixturesPath . '/dependency2.h';
        
        // First call
        $dependencies1 = $this->resolver->resolveDependencies($headerPath);
        
        // Second call should use cache
        $dependencies2 = $this->resolver->resolveDependencies($headerPath);
        
        $this->assertEquals($dependencies1, $dependencies2);
    }

    public function testSystemIncludePaths(): void
    {
        $systemPaths = ['/custom/include', '/another/include'];
        $resolver = new DependencyResolver($systemPaths);
        
        // Create a header that includes a system header
        $mainHeader = $this->tempDir . '/main.h';
        $systemHeader = $this->tempDir . '/system.h'; // Simulate system header
        
        file_put_contents($mainHeader, '#include <system.h>');
        file_put_contents($systemHeader, '// system header');
        
        // Add temp dir as system path for this test
        $resolver = new DependencyResolver([$this->tempDir]);
        $dependencies = $resolver->resolveDependencies($mainHeader);
        
        $this->assertCount(1, $dependencies);
        $this->assertStringEndsWith('system.h', $dependencies[0]);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}