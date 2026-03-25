<?php

namespace Symfony\HttpClientRecorderBundle\Store;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\HttpClientRecorderBundle\Har\HarFile;

/**
 * @psalm-import-type HarData from HarFile
 */
final class FilesystemStore implements StoreInterface
{
    private Filesystem $filesystem;

    public function __construct(?Filesystem $filesystem)
    {
        $this->filesystem = $filesystem ?? new Filesystem();
    }

    public function load(string $name): HarFile
    {
        if (!is_file($name)) {
            return HarFile::create();
        }

        /** @psalm-var HarData $har */
        $har = json_decode(file_get_contents($name), true, 512, \JSON_THROW_ON_ERROR);

        return new HarFile($har);
    }

    public function save(string $name, HarFile $har): void
    {
        if (!$this->filesystem->isAbsolutePath($name)) {
            throw new \InvalidArgumentException('The path must be absolute.');
        }

        $this->filesystem->dumpFile(
            $name,
            json_encode($har->toArray(), \JSON_PRETTY_PRINT)
        );
    }
}
