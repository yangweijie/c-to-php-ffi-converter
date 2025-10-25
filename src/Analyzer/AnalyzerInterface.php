<?php

declare(strict_types=1);

namespace Yangweijie\CWrapper\Analyzer;

/**
 * Interface for analyzing C projects
 */
interface AnalyzerInterface
{
    /**
     * Analyze a C project path
     *
     * @param string $path Path to analyze
     * @return AnalysisResult Analysis results
     */
    public function analyze(string $path): AnalysisResult;
}