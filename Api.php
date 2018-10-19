<?php

namespace Dnna\Payum\AlphaBank;

use Http\Message\MessageFactory;
use Payum\Core\Exception\Http\HttpException;
use Payum\Core\HttpClientInterface;

class Api
{
    /**
     * @var HttpClientInterface
     */
    protected $client;

    /**
     * @var MessageFactory
     */
    protected $messageFactory;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @param array $options
     * @param HttpClientInterface $client
     * @param MessageFactory $messageFactory
     *
     * @throws \Payum\Core\Exception\InvalidArgumentException if an option is invalid
     */
    public function __construct(array $options, HttpClientInterface $client, MessageFactory $messageFactory)
    {
        $this->options = $options;
        $this->client = $client;
        $this->messageFactory = $messageFactory;
    }

    /**
     * @param string $data
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function sendXMLRequest(string $data): \Psr\Http\Message\ResponseInterface
    {
        $endpoint = $this->getApiEndpoint() . '/xmlpayvpos';
        return $this->doRequest('POST', $endpoint, $data);
    }

    /**
     * @param $method
     * @param string $endpoint
     * @param string $body
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function doRequest($method, string $endpoint, string $body): \Psr\Http\Message\ResponseInterface
    {
        $headers = [];

        $request = $this->messageFactory->createRequest(
            $method,
            $endpoint,
            $headers,
            $body
        );

        $response = $this->client->send($request);

        if (false == ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
            throw HttpException::factory($request, $response);
        }

        return $response;
    }

    /**
     * @return string
     */
    protected function getApiEndpoint(): string
    {
        return $this->options['sandbox'] ? 'https://alpha.test.modirum.com/vpos' : 'https://www.alphaecommerce.gr/vpos';
    }
}
