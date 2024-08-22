<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service to get API wanted version in accept header from http request
 */
class VersioningService
{
    private RequestStack $requestStack;
    private string $defaultVersion;

    /**
     * Constructor to store API wanted version from request and default API version from parameter file
     * 
     * @param RequestStack $requestStack
     * @param ParameterBagInterface $params
     */
    public function __construct(RequestStack $requestStack, ParameterBagInterface $params)
    {
        $this->requestStack = $requestStack;
        $this->defaultVersion = $params->get('default_api_version');
    }

    /**
     * Method to get API wanted version in accept header from http request or default version if empty
     * 
     * @return string API version number
     */
    public function getAPIVersion(): string
    {
        $version = $this->defaultVersion;

        $request = $this->requestStack->getCurrentRequest();
        $accept = $request->headers->get('Accept');

        $header = explode(';', $accept);

        foreach ($header as $value) {
            if (strpos($value, 'version') !== false) {
                $version = explode('=', $value)[1];
                break;
            }
        }

        return $version;
    }
}
