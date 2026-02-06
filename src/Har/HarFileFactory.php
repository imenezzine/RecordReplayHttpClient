<?php

namespace Symfony\HttpClientRecorderBundle\Har;

use Symfony\Component\Clock\Clock;

final class HarFileFactory
{
    public function load(string $path): HarFile
    {
        return HarFile::createFromFile($path, Clock::get());
    }
}
