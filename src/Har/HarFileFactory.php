<?php

namespace Symfony\HttpClientRecorderBundle\Har;

use Symfony\HttpClientRecorderBundle\Matcher\DefaultMatcher;
use Symfony\HttpClientRecorderBundle\Store\StoreInterface;

final readonly class HarFileFactory
{
    public function __construct(private StoreInterface $store)
    {
    }

    public function load(string $name): HarFile
    {
        $entries = $this->store->exists($name)
            ? $this->store->load($name)
            : [];

        return $this->createFromEntries($entries);
    }

    public function createFromEntries(array $entries): HarFile
    {
        $har = HarFile::create();

        foreach ($entries as $entry) {
            $har->addEntry(new DefaultMatcher(), $entry);
        }

        return $har;
    }
}
