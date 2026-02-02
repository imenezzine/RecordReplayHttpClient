<?php

namespace Symfony\HttpClientRecorderBundle\Recorder;

use Symfony\HttpClientRecorderBundle\Har\HarFile;

final readonly class HarStore
{
    public function __construct(
        private string $cassetteDir,
    ) {
    }

    public function load(string $key): ?HarFile
    {
        $file = $this->cassetteDir.'/'.$key.'.har';

        return is_file($file) ? HarFile::createFromFile($file) : null;
    }

    public function save(string $key, HarFile $har): void
    {
        if (!is_dir($this->cassetteDir)) {
            mkdir($this->cassetteDir, 0777, true);
        }

        file_put_contents(
            $this->cassetteDir.'/'.$key.'.har',
            json_encode($har->toArray(), \JSON_PRETTY_PRINT)
        );
    }
}
