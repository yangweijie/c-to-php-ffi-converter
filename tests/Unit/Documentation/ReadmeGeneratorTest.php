<?php

declare(strict_types=1);

namespace Tests\Unit\Documentation;

use PHPUnit\Framework\TestCase;
use Yangweijie\CWrapper\Config\ProjectConfig;
use Yangweijie\CWrapper\Documentation\Documentation;
use Yangweijie\CWrapper\Documentation\ReadmeGenerator;
use Yangweijie\CWrapper\Generator\GeneratedCode;
use Yangweijie\CWrapper\Generator\WrapperClass;

class ReadmeGeneratorTest extends TestCase
{
    private ReadmeGenerator $generator;
    private ProjectConfig $config;
    private GeneratedCode $code;

    protected function setUp(): void
    {
        $this->generator = new ReadmeGenerator();
        
        $this->config = new ProjectConfig(
            headerFiles: ['/path/to/header1.h', '/path/to/header2.h'],
            libraryFile: '/path/to/library.so',
            outputPath: './generated',
            namespace: 'MyProject\\FFI'
        );

        $wrapperClass = new WrapperClass(
            'TestWrapper',
            'MyProject\\FFI',
            [
                'public function method1(): int { return 0; }',
                'public function method2(string $param): void { }'
            ],
            [],
            []
        );

        $this->code = new GeneratedCode(
            [$wrapperClass],
            [],
            [],
            new Documentation([], '', [])
        );
    }

    public function testGenerateReadmeWithCompleteConfiguration(): void
    {
        $readme = $this->generator->generateReadme($this->code, $this->config, 'TestLibrary');

        // Check title section
        $this->assertStringContainsString('# TestLibrary PHP FFI Wrapper', $readme);
        $this->assertStringContainsString('PHP FFI wrapper for the TestLibrary C library', $readme);

        // Check description section
        $this->assertStringContainsString('## Description', $readme);
        $this->assertStringContainsString('Object-oriented interface to C functions', $readme);
        $this->assertStringContainsString('Automatic parameter validation', $readme);

        // Check requirements section
        $this->assertStringContainsString('## Requirements', $readme);
        $this->assertStringContainsString('PHP 8.1 or higher', $readme);
        $this->assertStringContainsString('FFI extension enabled', $readme);

        // Check installation section
        $this->assertStringContainsString('## Installation', $readme);
        $this->assertStringContainsString('library.so', $readme);
        $this->assertStringContainsString('composer require', $readme);
        $this->assertStringContainsString('ffi.enable=1', $readme);

        // Check usage section
        $this->assertStringContainsString('## Usage', $readme);
        $this->assertStringContainsString('use MyProject\\FFI\\TestWrapper', $readme);
        $this->assertStringContainsString('new TestWrapper()', $readme);

        // Check dependencies section
        $this->assertStringContainsString('## Dependencies', $readme);
        $this->assertStringContainsString('/path/to/library.so', $readme);
        $this->assertStringContainsString('/path/to/header1.h', $readme);
        $this->assertStringContainsString('/path/to/header2.h', $readme);

        // Check troubleshooting section
        $this->assertStringContainsString('## Troubleshooting', $readme);
        $this->assertStringContainsString('FFI Extension Not Enabled', $readme);
        $this->assertStringContainsString('Library Not Found', $readme);

        // Check license section
        $this->assertStringContainsString('## License', $readme);
    }

    public function testGenerateReadmeWithEmptyClasses(): void
    {
        $emptyCode = new GeneratedCode([], [], [], new Documentation([], '', []));
        
        $readme = $this->generator->generateReadme($emptyCode, $this->config, 'TestLibrary');

        $this->assertStringContainsString('# TestLibrary PHP FFI Wrapper', $readme);
        $this->assertStringContainsString('No wrapper classes were generated', $readme);
    }

    public function testGenerateReadmeWithMinimalConfiguration(): void
    {
        $minimalConfig = new ProjectConfig();
        
        $readme = $this->generator->generateReadme($this->code, $minimalConfig, 'MinimalLib');

        $this->assertStringContainsString('# MinimalLib PHP FFI Wrapper', $readme);
        $this->assertStringContainsString('## Requirements', $readme);
        $this->assertStringContainsString('## Installation', $readme);
        $this->assertStringContainsString('## Usage', $readme);
    }

    public function testUsageSectionWithErrorHandling(): void
    {
        $readme = $this->generator->generateReadme($this->code, $this->config, 'TestLibrary');

        $this->assertStringContainsString('### Error Handling', $readme);
        $this->assertStringContainsString('ValidationException', $readme);
        $this->assertStringContainsString('try {', $readme);
        $this->assertStringContainsString('} catch', $readme);
    }

    public function testExamplesSectionGeneration(): void
    {
        $readme = $this->generator->generateReadme($this->code, $this->config, 'TestLibrary');

        $this->assertStringContainsString('## Examples', $readme);
        $this->assertStringContainsString('### TestWrapper Example', $readme);
        $this->assertStringContainsString('```php', $readme);
        $this->assertStringContainsString('new TestWrapper()', $readme);
    }

    public function testDependenciesSectionWithLibraryInfo(): void
    {
        $readme = $this->generator->generateReadme($this->code, $this->config, 'TestLibrary');

        $this->assertStringContainsString('### C Library Dependencies', $readme);
        $this->assertStringContainsString('**Library File**: `/path/to/library.so`', $readme);
        $this->assertStringContainsString('**Header Files**:', $readme);
        $this->assertStringContainsString('- `/path/to/header1.h`', $readme);
        $this->assertStringContainsString('- `/path/to/header2.h`', $readme);
        $this->assertStringContainsString('LD_LIBRARY_PATH', $readme);
    }

    public function testTroubleshootingSectionContent(): void
    {
        $readme = $this->generator->generateReadme($this->code, $this->config, 'TestLibrary');

        $this->assertStringContainsString('### Common Issues', $readme);
        $this->assertStringContainsString('#### FFI Extension Not Enabled', $readme);
        $this->assertStringContainsString('#### Library Not Found', $readme);
        $this->assertStringContainsString('#### Parameter Validation Errors', $readme);
        $this->assertStringContainsString('### Getting Help', $readme);
    }

    public function testReadmeStructureAndFormatting(): void
    {
        $readme = $this->generator->generateReadme($this->code, $this->config, 'TestLibrary');

        // Check that sections are properly separated
        $sections = explode("\n\n", $readme);
        $this->assertGreaterThan(5, count($sections));

        // Check markdown formatting
        $this->assertStringContainsString('# ', $readme); // H1 headers
        $this->assertStringContainsString('## ', $readme); // H2 headers
        $this->assertStringContainsString('### ', $readme); // H3 headers
        $this->assertStringContainsString('```', $readme); // Code blocks
        $this->assertStringContainsString('- ', $readme); // List items
    }

    public function testMultipleClassesInExamples(): void
    {
        $secondClass = new WrapperClass(
            'SecondWrapper',
            'MyProject\\FFI',
            ['public function anotherMethod(): string { return ""; }'],
            [],
            []
        );

        $multiClassCode = new GeneratedCode(
            [$this->code->classes[0], $secondClass],
            [],
            [],
            new Documentation([], '', [])
        );

        $readme = $this->generator->generateReadme($multiClassCode, $this->config, 'TestLibrary');

        $this->assertStringContainsString('### TestWrapper Example', $readme);
        $this->assertStringContainsString('### SecondWrapper Example', $readme);
    }
}