<?php

/*
 *      VK Api Class
 *      By Nikita Bykov
 *
 */

class VK
{
    private $access_token;
    private $curl;

    public function __construct($access_token)
    {
        $this->access_token = $access_token;
        $this->curl = curl_init();
    }

    public function __destruct()
    {
        curl_close($this->curl);
    }

    private function request($url)
    {
        curl_setopt_array($this->curl, array(
            CURLOPT_USERAGENT => 'VK',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POST => false,
            CURLOPT_URL => $url
        ));
        return curl_exec($this->curl);
    }

    public function api($method, $parameters = array())
    {
        $parameters['access_token'] = $this->access_token;
        $rs = $this->request('https://api.vk.com/method/' . $method . '?' . http_build_query($parameters));
        return json_decode($rs, true);
    }
}