<?php

namespace Symfony\HttpClientRecorderBundle\Har;

use Symfony\Component\Clock\ClockInterface;

final class HarFileFactory
{
    public function __construct(
        private readonly ClockInterface $clock,
    ) {
    }

    public function load(string $path): HarFile
    {
        return HarFile::createFromFile($path, $this->clock);
    }
}
