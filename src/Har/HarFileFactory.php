<?php

namespace Symfony\HttpClientRecorderBundle\Har;

final class HarFileFactory
{
    public function load(string $path): HarFile
    {
        return HarFile::createFromFile($path);
    }
}
