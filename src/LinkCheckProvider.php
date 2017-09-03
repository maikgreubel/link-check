<?php
namespace Nkey\LinkCheck;

use Generics\Client\HttpClientFactory;
use Generics\Socket\Url;
use Generics\Streams\StandardOutputStream;
use Generics\Streams\HttpStream;
use Generics\Util\UrlParser;

class LinkCheckProvider
{
    /**
     * 
     * @var HttpStream
     */
    private $httpClient;

    /**
     * 
     * @var string
     */
    private $output;
    
    /**
     * 
     * @var Url
     */
    private $url;

    public function __construct(Url $url)
    {
        $this->httpClient = HttpClientFactory::get($url);
        $this->output = new StandardOutputStream();
        $this->url = $url;
    }

    public function check(array $options = array())
    {
        $this->httpClient->request('GET');
        
        if ($this->httpClient->getResponseCode() != 200) {
            $this->output->write("Invalid response code " . $this->httpClient->getResponseCode());
            return;
        }
        
        $response = "";
        $size = $this->httpClient->getPayload()->count();
        if(isset($this->httpClient->getHeaders()['Content-Length'])) {
            $size = $this->httpClient->getHeaders()['Content-Length'];
        }
        
        while ($this->httpClient->getPayload()->ready()) {
            $response .= $this->httpClient->getPayload()->read($size);
        }
        
        $this->extractAndCheckUrls($response, $options);
    }
    
    private function extractAndCheckUrls(string $response, array $options)
    {
        $matches = [];
        
        if (preg_match_all('#<a href="([^\"]*)">#', $response, $matches)) {
            array_shift($matches);
            $matches = $matches[0];
        }
        
        if(count($matches) == 0) {
            return;
        }
        
        foreach ($matches as $match) {
            if(substr($match, 0, 4) != 'http') {
                $match = sprintf("%s://%s:%d%s", $this->url->getScheme(), $this->url->getAddress(), $this->url->getPort(), $match);
            }
            $url = UrlParser::parseUrl($match);
            
            $this->output->write($url);
            if($url->getAddress() == $this->url->getAddress()) {
                $this->checkSameSiteurl($url, $options);
            }
            $this->output->write("\n");
        }
    }
    
    private function checkSameSiteUrl(Url $url, array $options)
    {
        if(isset($options['recursive']) && $options['recursive'] == true) {
            $subProvider = new LinkCheckProvider($url);
            $subProvider->check($options);
        }
        else {
            $http = HttpClientFactory::get($url);
            $http->setTimeout(10);
            $http->request('HEAD');
            if($http->getResponseCode() < 400) {
                $this->output->write(" OK");
            }
        }
    }
}