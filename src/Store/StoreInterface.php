<?php

namespace Symfony\HttpClientRecorderBundle\Store;

use Symfony\HttpClientRecorderBundle\Har\HttpRecord;

interface StoreInterface
{
    /**
     * @return HttpRecord[]
     */
    public function load(string $name): array;

    /**
     * @param HttpRecord[] $entries
     */
    public function save(string $name, array $entries): void;

    public function exists(string $name): bool;

    public function delete(string $name): void;

    /**
     * @return string[]
     */
    public function list(): array;

    public function purge(): void;
}
