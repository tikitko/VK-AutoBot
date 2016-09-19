<?php

/*
 *      AntiCaptcha Class (http://rucaptcha.com/)
 *      By Nikita Bykov
 *
 */

class AntiCaptcha
{
    private $api_key;
    private $curl;

    public function __construct($api_key)
    {
        $this->api_key = $api_key;
        $this->curl = curl_init();
    }

    public function __destruct()
    {
        curl_close($this->curl);
    }

    private function request($url, $post = true, $post_data = array())
    {
        curl_setopt_array($this->curl, array(
            CURLOPT_USERAGENT => 'AntiCaptcha',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POST => $post,
            CURLOPT_POSTFIELDS => $post_data,
            CURLOPT_URL => $url
        ));
        return curl_exec($this->curl);
    }

    private function Base64_Image($image_file)
    {
        return base64_encode(file_get_contents($image_file));
    }

    private function Captcha_Sender($image_file)
    {
        $status = '';
        $body = '';
        $parameters = array(
            'method' => 'base64',
            'key' => $this->api_key,
            'body' => $this->Base64_Image($image_file)
        );
        $rs = $this->request('http://rucaptcha.com/in.php' . '?' . http_build_query($parameters));
        $temp = explode('|', $rs);
        if ($temp[0] == 'OK') {
            $status = $temp[0];
            $body = $temp[1];
        } else {
            $status = 'ERROR';
            $body = $temp[0];
        }
        return array('status' => $status, 'body' => $body);
    }

    private function Captcha_Result($captcha_id)
    {
        $status = '';
        $body = '';
        $parameters = array(
            'action' => 'get',
            'key' => $this->api_key,
            'id' => $captcha_id
        );
        for ($i = 0; $i < 5; $i++) {
            sleep(5);
            $rs = $this->request('http://rucaptcha.com/res.php' . '?' . http_build_query($parameters), false);
            $temp = explode('|', $rs);
            if ($temp[0] == 'OK') {
                $status = $temp[0];
                $body = $temp[1];
                break;
            } else {
                $status = 'ERROR';
                $body = $temp[0];
            }
        }
        if (empty($status)) {
            $status = 'ERROR';
        }
        return array('status' => $status, 'body' => $body);
    }

    public function run($captcha_img)
    {
        $captcha = $this->Captcha_Sender($captcha_img);
        if ($captcha['status'] == 'OK') {
            return $this->Captcha_Result($captcha['body']);
        }
        return $captcha;
    }
}