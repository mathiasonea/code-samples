<?php

namespace App\Services\Pushwoosh;

use App\Services\Pushwoosh\Exception\PushwooshException;
use App\Services\Pushwoosh\Exception\UnknownDeviceException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;

class Pushwoosh implements PushwooshApiServiceInterface
{
    protected $client;
    protected $apiToken;
    protected $applicationCode;
    protected $baseUrl = 'https://CUSTOMER.pushwoosh.com/json/1.3/';
    
    /**
     * Create a new Pushwoosh API client.
     *
     * @param  ClientInterface  $client
     * @param  string  $apiToken
     */
    public function __construct(ClientInterface $client, string $apiToken)
    {
        $this->client = $client;
        $this->apiToken = $apiToken;
    }
    
    public function createMessage(Message $message)
    {
        if (!$this->applicationCode) {
            throw new PushwooshException('No Pushwoosh application specified.');
        }
        
        $request = new Request(
            'POST',
            $this->baseUrl.'createMessage',
            [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ],
            \GuzzleHttp\json_encode([
                'request' => [
                    'auth' => $this->apiToken,
                    'application' => $this->applicationCode,
                    'notifications' => [
                        $message->toArray()
                    ],
                ]
            ])
        );
        
        try {
            $response = $this->client->send($request);
        } catch (GuzzleException $exception) {
            throw new PushwooshException('Failed to create message(s)', 0, $exception);
        }
        
        $response = \GuzzleHttp\json_decode($response->getBody()->getContents());
        
        if (isset($response->status_code) && $response->status_code !== 200) {
            throw new PushwooshException($response->status_message);
        }
        
        if (isset($response->response->UnknownDevices)) {
            throw new UnknownDeviceException($response->response->UnknownDevices);
        }
        
        if (isset($response->response->Messages)) {
            # Pushwoosh will not assign IDs to messages sent to less than 10 unique devices
            return array_map([$this, 'mapResponse'], $response->response->Messages);
        }
        
        return [];
    }
    
    public function setApplication(string $application)
    {
        $this->applicationCode = $application;
    }

    public function mapResponse(string $identifier)
    {
        return $identifier !== 'CODE_NOT_AVAILABLE' ? $identifier : null;
    }
}
