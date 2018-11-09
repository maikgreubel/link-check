<?php
namespace Nkey\LinkCheck\Tests;

use PHPUnit\Framework\TestCase;
use Nkey\LinkCheck\LinkCheckProvider;
use Generics\Util\UrlParser;
use Generics\Streams\StandardOutputStream;
use Generics\Streams\Interceptor\CachedStreamInterceptor;

class LinkCheckProviderTest extends TestCase
{
    public function testCheckOnExistedLink()
    {
        $provider = new LinkCheckProvider(UrlParser::parseUrl("https://letsencrypt.org/getting-started/"));

        $stream = new StandardOutputStream();
        $interceptor = new CachedStreamInterceptor();
        $stream->setInterceptor($interceptor);
        $provider->setOutput($stream);

        $provider->check();

        $this->assertContains("https://letsencrypt.org/privacy/ OK", $interceptor->getCache());
    }

    public function testCheckOnNonExistedLink()
    {
        $provider = new LinkCheckProvider(UrlParser::parseUrl("https://letsencrypt.org/123456"));

        $stream = new StandardOutputStream();
        $interceptor = new CachedStreamInterceptor();
        $stream->setInterceptor($interceptor);
        $provider->setOutput($stream);

        $provider->check();

        $this->assertContains("Invalid response code 404", $interceptor->getCache());
    }
}
