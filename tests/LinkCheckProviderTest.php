<?php
namespace Nkey\LinkCheck\Tests;

use PHPUnit\Framework\TestCase;
use Nkey\LinkCheck\LinkCheckProvider;
use Generics\Util\UrlParser;

class LinkCheckProviderTest extends TestCase
{
    /**
     * @test
     */
    public function testSimple()
    {
        $provider = new LinkCheckProvider(UrlParser::parseUrl("https://letsencrypt.org/getting-started/"));
        
        $provider->check();
    }
}