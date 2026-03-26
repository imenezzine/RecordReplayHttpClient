RecordHttpClientBundle
------------

Record and replay HTTP interactions in your tests using HAR files.

🚀 Motivation
------------

When testing code that relies on external APIs, developers usually face a trade-off:

* ✅ Real HTTP calls → accurate but slow and flaky
* ✅ Mocks → fast but not always realistic

This bundle provides a third approach:

🎯 Record real HTTP interactions once, then replay them in tests

This allows:

* deterministic tests
* faster execution
* no dependency on external services

📦 Installation
------------
```bash
composer require --dev symfony/http-client-recorder-bundle
```
⚙️ Configuration
------------

Enable the PHPUnit extension:
```YAML
<extensions>
    <bootstrap class="Symfony\HttpClientRecorderBundle\PHPUnit\RecorderExtension">
        <parameter name="defaultDirectory" value="./tests/" />
    </bootstrap>
</extensions>
```          
🧪 Usage
------------

1. Example Controller
------------
 ```php         
// src/Controller/GetRandomController.php

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GetRandomController
{
    #[Route('/random')]
    public function __invoke(HttpClientInterface $client): Response
    {
        $response = $client->request('GET', 'https://meowfacts.herokuapp.com/');

        // ...
    }
}
```
2. Enable recording in your test
------------

```php
// tests/Controller/TestGetRandomController.php

use Symfony\Bridge\PhpUnit\HttpClientRecorder\Attribute\UseRecord;

class TestGetRandomController extends WebTestCase
{
    #[UseRecord]
    public function testRandom(): void
    {
        $client = static::createClient();
        $client->request('GET', 'https://catfact.ninja/fact');

        $this->assertResponseIsSuccessful();
    }
}
```
📁 Generated HAR file
------------
After running the test, a HAR file is automatically created:
```bash
tests/Controller/TestGetRandomController/testRandom.har
```
Example
------------
```JSON
{
  "log": {
    "version": "1.2",
    "creator": {
      "name": "HttpRecorder"
    },
    "entries": [
      {
        "startedDateTime": "2026-03-26T16:56:06.969Z",
        "request": {
          "method": "GET",
          "url": "https://catfact.ninja/fact"
        },
        "response": {
          "status": 200,
          "headers": {
            "content-type": ["application/json"]
          },
          "content": {
            "text": "{\"fact\":\"Cats sleep 70% of their lives\"}"
          }
        }
      }
    ]
  }
}
```
🔁 Replay mode
------------

On subsequent test runs:

* ❌ No real HTTP request is executed
* ✅ Responses are replayed from the HAR file

This ensures:

* stable tests
* instant execution
* no API rate limits issues

🧠 How it works
------------

The bundle decorates the Symfony HttpClient

During the first run
------------
requests/responses are recorded into a .har file

During next runs:
------------

responses are replayed from the recorded file
