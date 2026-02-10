<?php

namespace Symfony\HttpClientRecorderBundle\Store;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\HttpClientRecorderBundle\Har\HttpRecord;

final readonly class FilesystemStore implements StoreInterface
{
    public function __construct(private string $dir)
    {
    }

    public function load(string $name): array
    {
        $path = $this->dir.'/'.$name;

        if (!is_file($path)) {
            return [];
        }

        $json = file_get_contents($path);
        $entries = json_decode($json, true, flags: \JSON_THROW_ON_ERROR);

        return array_map(fn ($entry) => HttpRecord::fromArray($entry), $entries);
    }

    public function save(string $name, array $entries): void
    {
        $path = $this->dir.'/'.$name;

        $data = array_map(fn (HttpRecord $r) => $r->toArray(), $entries);

        (new Filesystem())->dumpFile($path, json_encode($data, \JSON_PRETTY_PRINT));
    }

    public function exists(string $name): bool
    {
        return is_file($this->dir.'/'.$name);
    }

    public function delete(string $name): void
    {
        $fs = new Filesystem();
        $path = $this->dir.'/'.$name;
        if (is_file($path)) {
            $fs->remove($path);
        }
    }

    public function list(): array
    {
        return glob($this->dir.'/*.har') ?: [];
    }

    public function purge(): void
    {
        $fs = new Filesystem();
        $fs->remove(glob($this->dir.'/*.har') ?: []);
    }
}
