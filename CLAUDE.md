# Symfony HTTP Recorder

## Overview

A PHP-VCR-like HTTP recording/replay system for Symfony's HttpClient component. Provides a
modern, Symfony-native way to record HTTP interactions and replay them for testing, development mocking, and
request auditing.

# Architecture

A `RecorderHttpClient` decorator following the established patterns from CachingHttpClient and
TraceableHttpClient, with a Symfony bundle for DI integration.

Key components:
- `RecorderHttpClient` - Main decorator (pattern from CachingHttpClient)
- `HarFile` - HAR-based storage container for recordings
- `MatcherInterface` / `DefaultMatcher` - Configurable request matching
- `HttpClientRecorderBundle` - Bundle with DI configuration
- PHPUnit integration (`RecorderExtension`, `RecorderSubscriber`, `UseRecord` attribute)

# Decisions

- Storage formats: HAR (W3C standard, browser dev tools compatible)

# Directory Structure

- `src/RecorderMode.php` - Constant registry for native modes (record, replay, record_if_missing_and_replay, passthrough)
- `src/Exception` - Custom exceptions for recording/replay errors
- `src/Har` - HAR file handling (should be used later in HarFileResponseFactory)
- `src/HttpClient` - `RecorderHttpClient` decorator that handles recording/replay
- `src/Matcher` - Configurable request matching (`MatcherInterface`, `DefaultMatcher`)
- `src/PHPUnit` - PHPUnit extension and subscriber for recording/replay control
- `src/PHPUnit/Attribute` - `UseRecord` attribute for recording/replay control in PHPUnit tests
- `src/Store` - HAR storage logic (`StoreInterface`, `FilesystemStore`)

# Implementation Details

## RecorderHttpClient (Main Decorator)

Follows CachingHttpClient pattern:
- Uses AsyncDecoratorTrait for streaming support
- Intercepts requests based on current mode
- Uses MockHttpClient for replay responses

```php
final class RecorderHttpClient implements HttpClientInterface
{
    use AsyncDecoratorTrait;

    public function __construct(
        private readonly HttpClientInterface $inner,
        private readonly StoreInterface $store,
        private readonly MatcherInterface $matcher = new DefaultMatcher(),
    ) {}

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        // 1. PASSTHROUGH: just pass through to the inner client
        // 2. REPLAY: find matching entry in HAR file
        // 3. RECORD: pass through the inner client and store the response
        // 4. RECORD_IF_MISSING_AND_REPLAY: try replay first, fall back to recording
    }
}
```

## HarFile (Value Object)

A class that handles a HAR file to:
- add new entry
- find entry using a MatcherInterface

## RequestMatcher

Matcher strategies that implement the following interface:
```php
interface MatcherInterface
{
    /**
     * @param array<string, mixed> $harEntry
     * @param array<string, mixed> $options
     */
    public function matches(
        array $harEntry,
        string $method,
        string $url,
        array $options,
    ): bool;
}
```

A `DefaultMatcher` is provided that matches based on:
- method
- url
- body (if present)
- headers (if present)

## Store

Interface for storing HAR files.
```php
interface StoreInterface
{
    public function load(string $name): HarFile;

    public function save(string $name, HarFile $har): void;
}
```

A `FilesystemStore` is provided that stores the HAR files on disk.

## HttpClientRecorderBundle

Symfony bundle providing DI integration. Configuration:

```yaml
http_client_recorder:
    enabled: false # Enable/disable the recorder
    records_path: '%kernel.project_dir%/tests/fixtures/records' # Where HAR files are stored
```

When enabled, the bundle:
- Registers a `FilesystemStore` service
- Decorates all tagged `http_client.client` services with `RecorderHttpClient`

## PHPUnit Integration

- `RecorderExtension` - PHPUnit extension that registers the subscriber
- `RecorderSubscriber` - Listens for test preparation, reads `UseRecord` attributes from both method and class level
- `UseRecord` attribute - Controls recording mode and record name per test method or class

```php
#[UseRecord(record: 'my-fixture.har', mode: RecorderMode::PLAYBACK)]
public function testSomething(): void { ... }
```

# Critical Files to Reference from the HttpClient component
| File | Pattern/Logic to Follow |
|---|---|
| CachingHttpClient.php | AsyncResponse chunk interception, MockResponse creation |
| TraceableHttpClient.php | Request metadata capture, ArrayObject storage |
| Test/HarFileResponseFactory.php | HAR parsing, base64 handling |
| Response/MockResponse.php | fromRequest() factory for replay |
| HttpClientTrait.php | prepareRequest() for URL/option normalization |

# Verification Plan

1. Quality
- Run `php-cs-fixer fix --dry-run --diff` to check code style
- Run `php-cs-fixer fix` to fix code style
2. Unit Tests
- Run `./phpunit`
3. Manual Testing
- Create a test script that records a real HTTP call
- Verify HAR file is created correctly
- Switch to replay mode, verify response matches
- Test all modes (record, replay, new_episodes, passthrough)
4. Streaming Test
- Record a large response
- Verify the chunked body is captured completely
- Playback and verify content matches
5. HAR Compatibility
- Import HAR file exported from browser dev tools
- Verify replay works correctly
