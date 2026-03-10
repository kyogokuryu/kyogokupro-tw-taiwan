<?php


namespace Plugin\ECCUBE4LineIntegration\Consts;


class ApiUrl
{
    private $accessUrl = 'https://access.line.me';
    private $apiUrl =  'https://api.line.me';

    /**
     * @return string
     */
    public function getAccessUrl(): string
    {
        return $this->accessUrl;
    }

    /**
     * @param string $accessUrl
     */
    public function setAccessUrl(string $accessUrl): void
    {
        $this->accessUrl = $accessUrl;
    }

    /**
     * @return string
     */
    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }

    /**
     * @param string $apiUrl
     */
    public function setApiUrl(string $apiUrl): void
    {
        $this->apiUrl = $apiUrl;
    }
}