<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Analyzer;

use Yangweijie\CWrapper\Exception\AnalysisException;

/**
 * Resolves header file dependencies and creates compilation order
 */
class DependencyResolver
{
    /** @var array<string> */
    private array $systemIncludePaths;
    
    /** @var array<string, array<string>> */
    private array $dependencyCache = [];

    /**
     * @param array<string> $systemIncludePaths System header file locations
     */
    public function __construct(array $systemIncludePaths = [])
    {
        $this->systemIncludePaths = array_merge([
            '/usr/include',
            '/usr/local/include',
            '/opt/homebrew/include', // macOS Homebrew
        ], $systemIncludePaths);
    }

    /**
     * Resolve dependencies for a header file
     *
     * @param string $headerPath Path to the header file
     * @param array<string> $searchPaths Additional search paths for includes
     * @return array<string> List of dependency file paths
     * @throws AnalysisException If dependencies cannot be resolved
     */
    public function resolveDependencies(string $headerPath, array $searchPaths = []): array
    {
        if (isset($this->dependencyCache[$headerPath])) {
            return $this->dependencyCache[$headerPath];
        }

        $dependencies = [];
        $visited = [];
        
        $this->resolveDependenciesRecursive($headerPath, $searchPaths, $dependencies, $visited);
        
        $this->dependencyCache[$headerPath] = $dependencies;
        return $dependencies;
    }

    /**
     * Create compilation order for multiple header files
     *
     * @param array<string> $headerPaths List of header file paths
     * @param array<string> $searchPaths Additional search paths for includes
     * @return array<string> Header files in compilation order
     * @throws AnalysisException If circular dependencies are detected
     */
    public function createCompilationOrder(array $headerPaths, array $searchPaths = []): array
    {
        $dependencyGraph = [];
        $allHeaders = [];
        
        // Normalize all paths first
        $normalizedHeaderPaths = [];
        foreach ($headerPaths as $headerPath) {
            $realPath = realpath($headerPath);
            if ($realPath !== false) {
                $normalizedHeaderPaths[] = $realPath;
            }
        }
        
        // Build dependency graph for all headers
        foreach ($normalizedHeaderPaths as $headerPath) {
            $dependencies = $this->resolveDependencies($headerPath, $searchPaths);
            $dependencyGraph[$headerPath] = $dependencies;
            $allHeaders[] = $headerPath;
            $allHeaders = array_merge($allHeaders, $dependencies);
        }
        
        $allHeaders = array_unique($allHeaders);
        
        // Perform topological sort
        return $this->topologicalSort($dependencyGraph, $allHeaders);
    }

    /**
     * Recursively resolve dependencies for a header file
     *
     * @param string $headerPath Current header file path
     * @param array<string> $searchPaths Search paths for includes
     * @param array<string> $dependencies Accumulated dependencies
     * @param array<string> $visited Visited files to detect cycles
     */
    private function resolveDependenciesRecursive(
        string $headerPath,
        array $searchPaths,
        array &$dependencies,
        array &$visited
    ): void {
        $realPath = realpath($headerPath);
        if ($realPath === false) {
            throw new AnalysisException("Header file not found: {$headerPath}");
        }
        
        if (in_array($realPath, $visited)) {
            // Circular dependency detected - skip to avoid infinite loop
            return;
        }
        
        $visited[] = $realPath;
        
        $content = file_get_contents($realPath);
        if ($content === false) {
            throw new AnalysisException("Failed to read header file: {$headerPath}");
        }
        
        $includes = $this->extractIncludes($content);
        
        foreach ($includes as $include) {
            $includePath = $this->resolveIncludePath($include, dirname($realPath), $searchPaths);
            
            if ($includePath && !in_array($includePath, $dependencies)) {
                $dependencies[] = $includePath;
                
                // Recursively resolve dependencies of the included file
                $this->resolveDependenciesRecursive($includePath, $searchPaths, $dependencies, $visited);
            }
        }
        
        // Remove current file from visited to allow it in other dependency chains
        array_pop($visited);
    }

    /**
     * Extract include statements from header content
     *
     * @return array<string>
     */
    private function extractIncludes(string $content): array
    {
        $includes = [];
        
        // Pattern to match #include statements
        $pattern = '/#include\s+[<"]([^>"]+)[>"]/';
        
        if (preg_match_all($pattern, $content, $matches)) {
            $includes = $matches[1];
        }
        
        return $includes;
    }

    /**
     * Resolve the full path for an include statement
     *
     * @param string $include Include filename (e.g., "stdio.h" or "myheader.h")
     * @param string $currentDir Directory of the current header file
     * @param array<string> $searchPaths Additional search paths
     * @return string|null Full path to the include file, or null if not found
     */
    private function resolveIncludePath(string $include, string $currentDir, array $searchPaths): ?string
    {
        // First, try relative to current directory
        $relativePath = $currentDir . DIRECTORY_SEPARATOR . $include;
        if (file_exists($relativePath)) {
            return realpath($relativePath);
        }
        
        // Try additional search paths
        foreach ($searchPaths as $searchPath) {
            $fullPath = $searchPath . DIRECTORY_SEPARATOR . $include;
            if (file_exists($fullPath)) {
                return realpath($fullPath);
            }
        }
        
        // Try system include paths
        foreach ($this->systemIncludePaths as $systemPath) {
            $fullPath = $systemPath . DIRECTORY_SEPARATOR . $include;
            if (file_exists($fullPath)) {
                return realpath($fullPath);
            }
        }
        
        // Include not found - this might be a system header we don't need to analyze
        return null;
    }

    /**
     * Perform topological sort on dependency graph
     *
     * @param array<string, array<string>> $dependencyGraph
     * @param array<string> $allHeaders
     * @return array<string>
     * @throws AnalysisException If circular dependencies are detected
     */
    private function topologicalSort(array $dependencyGraph, array $allHeaders): array
    {
        $sorted = [];
        $visited = [];
        $visiting = [];
        
        foreach ($allHeaders as $header) {
            if (!in_array($header, $visited)) {
                $this->topologicalSortVisit($header, $dependencyGraph, $sorted, $visited, $visiting);
            }
        }
        
        return $sorted;
    }

    /**
     * Visit node in topological sort
     *
     * @param string $header Current header
     * @param array<string, array<string>> $dependencyGraph
     * @param array<string> $sorted
     * @param array<string> $visited
     * @param array<string> $visiting
     * @throws AnalysisException If circular dependency is detected
     */
    private function topologicalSortVisit(
        string $header,
        array $dependencyGraph,
        array &$sorted,
        array &$visited,
        array &$visiting
    ): void {
        if (in_array($header, $visiting)) {
            throw new AnalysisException("Circular dependency detected involving: {$header}");
        }
        
        if (in_array($header, $visited)) {
            return;
        }
        
        $visiting[] = $header;
        
        $dependencies = $dependencyGraph[$header] ?? [];
        foreach ($dependencies as $dependency) {
            $this->topologicalSortVisit($dependency, $dependencyGraph, $sorted, $visited, $visiting);
        }
        
        // Remove from visiting and add to visited
        $visitingIndex = array_search($header, $visiting);
        if ($visitingIndex !== false) {
            array_splice($visiting, $visitingIndex, 1);
        }
        
        $visited[] = $header;
        $sorted[] = $header;
    }

    /**
     * Get dependency graph for a set of headers
     *
     * @param array<string> $headerPaths
     * @param array<string> $searchPaths
     * @return array<string, array<string>>
     */
    public function getDependencyGraph(array $headerPaths, array $searchPaths = []): array
    {
        $graph = [];
        
        foreach ($headerPaths as $headerPath) {
            $dependencies = $this->resolveDependencies($headerPath, $searchPaths);
            $graph[$headerPath] = $dependencies;
        }
        
        return $graph;
    }
}