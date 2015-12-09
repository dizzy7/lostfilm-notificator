<?php

namespace AppBundle\Service;

use AppBundle\Interfaces\HttpGrabberInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class HttpGrabber implements HttpGrabberInterface
{
    private $guzzle;

    public function __construct(Client $guzzle)
    {
        $this->guzzle = $guzzle;
    }

    public function getPage($url)
    {
        try {
            $result = $this->guzzle->get($url);

            return (string) $result->getBody();
        } catch (ClientException $e) {
            throw new \Exception('Не удалось получить данные: ' . $e->getMessage());
        }
    }
}