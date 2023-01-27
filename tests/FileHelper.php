<?php

namespace App\Tests;

trait FileHelper
{
    public function getTempFilepath(string $filename = 'random_', string $extension = '.txt'): string
    {
        $projectDir = static::getContainer()->getParameter('kernel.project_dir');
        $tempFilepath = tempnam($projectDir.'/tmp/', $filename).$extension;
        file_put_contents($tempFilepath, 'Hello world!');

        return $tempFilepath;
    }
}
