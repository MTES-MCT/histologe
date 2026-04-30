#!/usr/bin/env php
<?php

/**
 * This script analyzes all test files with @dataProvider annotations
 * and checks for potential issues with PHPUnit 11 compatibility.
 */
$testsDir = __DIR__.'/tests';
$issues = [];

// Find all test files
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($testsDir)
);

foreach ($iterator as $file) {
    if ('php' !== $file->getExtension()) {
        continue;
    }

    $filePath = $file->getPathname();
    $content = file_get_contents($filePath);

    // Find all @dataProvider annotations
    if (preg_match_all('/@dataProvider\s+(\w+)/', $content, $matches)) {
        $providerNames = $matches[1];

        foreach ($providerNames as $providerName) {
            $fileIssues = [];

            // Check if provider method exists
            if (!preg_match('/function\s+'.preg_quote($providerName).'\s*\(/m', $content)) {
                $fileIssues[] = "Provider method '$providerName' not found";
                continue;
            }

            // Check if provider is static
            if (!preg_match('/(public\s+)?static\s+function\s+'.preg_quote($providerName).'\s*\(/m', $content)) {
                $fileIssues[] = "Provider '$providerName' is NOT static (required for PHPUnit 11)";
            }

            // Extract the provider method body
            if (preg_match('/function\s+'.preg_quote($providerName).'\s*\([^)]*\)\s*:\s*[^{]+\{(.*?)(?=\n\s{4}(public|private|protected|\/\*|\}))/s', $content, $bodyMatch)) {
                $providerBody = $bodyMatch[1];

                // Check if it yields or returns data
                $hasYield = str_contains($providerBody, 'yield');
                $hasReturn = preg_match('/return\s+\[/', $providerBody);

                if (!$hasYield && !$hasReturn) {
                    $fileIssues[] = "Provider '$providerName' might not yield or return data";
                }
            }

            // Find the test method that uses this provider
            if (preg_match('/@dataProvider\s+'.preg_quote($providerName).'[^\n]*\n[^\n]*(?:\/\*\*[^*]*\*[^\/]*\/\s*)?\n\s*(?:\/\*\*[^*]*\*[^\/]*\/\s*)?\n?\s*public\s+function\s+(\w+)\s*\(([^)]*)\)/s', $content, $testMatch)) {
                $testMethod = $testMatch[1];
                $testParams = $testMatch[2];

                // Count parameters
                $paramCount = 0;
                if (trim($testParams)) {
                    // Count commas outside of angle brackets
                    $level = 0;
                    $paramCount = 1;
                    for ($i = 0; $i < strlen($testParams); ++$i) {
                        if ('<' === $testParams[$i]) {
                            ++$level;
                        }
                        if ('>' === $testParams[$i]) {
                            --$level;
                        }
                        if (',' === $testParams[$i] && 0 === $level) {
                            ++$paramCount;
                        }
                    }
                }

                if (0 === $paramCount) {
                    $fileIssues[] = "Test method '$testMethod' has NO parameters but uses dataProvider '$providerName'";
                }
            }

            if (!empty($fileIssues)) {
                $issues[$filePath][$providerName] = $fileIssues;
            }
        }
    }
}

// Output results
if (empty($issues)) {
    echo "✓ No issues found with data providers!\n";
    exit(0);
}

echo 'Found '.count($issues)." test file(s) with potential issues:\n\n";

foreach ($issues as $file => $providers) {
    $relPath = str_replace($testsDir.'/', '', $file);
    echo "FILE: $relPath\n";

    foreach ($providers as $provider => $providerIssues) {
        echo "  Provider: $provider\n";
        foreach ($providerIssues as $issue) {
            echo "    - $issue\n";
        }
    }
    echo "\n";
}

exit(1);
