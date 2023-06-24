<?php

namespace ESI\WhatsappSDK;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class WhatsappSdk
{
    const url = "https://wapp.esistemasinteligentes.com.br";

    protected $_http;
    protected $instance_key;
    function __construct($instance_key = null, $token = null)
    {
        $this->instance_key = $instance_key ?? "deployment";
        $this->_http = new Client([
            'base_uri' => self::url,
            "headers" => [
                'token-esi' => $token
            ],
        ]);
    }

    public function initSession()
    {
        try {
            $response = $this->_http->request(
                'GET',
                "/instance/init?key={$this->instance_key}",
            );

            $parseResponse = json_decode($response->getBody()->getContents(), true);
            return $parseResponse;
        } catch (ClientException $cli) {
            return $this->errorsParse($cli->getMessage(), $cli->getResponse()->getBody()->getContents(), $cli->getCode());
        } catch (Exception $e) {
            return $this->errorsParse($e->getMessage(), null, $e->getCode());
        }
    }

    public function getSession()
    {
        try {
            $response = $this->_http->request(
                'GET',
                "/instance/info?key={$this->instance_key}"
            );

            $parseResponse = json_decode($response->getBody()->getContents(), true);

            return $parseResponse;
        } catch (ClientException $cli) {
            return $this->errorsParse($cli->getMessage(), $cli->getResponse()->getBody()->getContents(), $cli->getCode());
        } catch (Exception $e) {
            return $this->errorsParse($e->getMessage(), null, $e->getCode());
        }
    }

    public function restartSession()
    {
        $checkSession = $this->getSession();
        if (isset($checkSession['error']) && $checkSession['error'] == true) {
            $this->parseSessionResult($checkSession['content']);
        }
    }

    private function parseSessionResult($content)
    {
        if (isset($content['status']) && ($content['status'] == '404' || $content['status'] == '403')) {
            $this->initSession();
            sleep(1);
        } else {
            $this->logoutSession();
            $this->deleteSession();
            sleep(1);
            $this->initSession();
            sleep(1);
        }
    }

    public function deleteSession()
    {
        try {
            $response = $this->_http->request(
                'DELETE',
                "/instance/delete?key={$this->instance_key}"
            );

            $parseResponse = json_decode($response->getBody()->getContents(), true);
            return $parseResponse;
        } catch (ClientException $cli) {
            return $this->errorsParse($cli->getMessage(), $cli->getResponse()->getBody()->getContents(), $cli->getCode());
        } catch (Exception $e) {
            return $this->errorsParse($e->getMessage(), null, $e->getCode());
        }
    }

    public function logoutSession()
    {
        try {
            $response = $this->_http->request(
                'DELETE',
                "/instance/logout?key={$this->instance_key}"
            );

            $parseResponse = json_decode($response->getBody()->getContents(), true);
            return $parseResponse;
        } catch (ClientException $cli) {
            return $this->errorsParse($cli->getMessage(), $cli->getResponse()->getBody()->getContents(), $cli->getCode());
        } catch (Exception $e) {
            return $this->errorsParse($e->getMessage(), null, $e->getCode());
        }
    }

    public function getQRCodeBase64()
    {
        try {
            $response = $this->_http->request(
                'GET',
                "/instance/qrbase64?key={$this->instance_key}"
            );

            $parseResponse = json_decode($response->getBody()->getContents(), true);
            return $parseResponse;
        } catch (ClientException $cli) {
            return $this->errorsParse($cli->getMessage(), $cli->getResponse()->getBody()->getContents(), $cli->getCode());
        } catch (Exception $e) {
            return $this->errorsParse($e->getMessage(), null, $e->getCode());
        }
    }

    public function sendMessage(array $info)
    {
        $dados = [];
        $dados["id"] = $this->parseNumberPrefix($info['telefone']);
        $dados["message"] = $info['message'];
        $endPoint = "text";

        try {
            $response = $this->_http->request(
                'POST',
                "/message/{$endPoint}?key={$this->instance_key}",
                [
                    "json" => $dados
                ]
            );

            $parseResponse = (array) json_decode($response->getBody()->getContents());

            return $parseResponse;
        } catch (ClientException $cli) {
            return $this->errorsParse($cli->getMessage(), $cli->getResponse()->getBody()->getContents(), $cli->getCode());
        } catch (Exception $e) {
            return $this->errorsParse($e->getMessage(), null, $e->getCode());
        }
    }

    public function sendMessageTemplate(array $info, $options = [])
    {
        $dados = [
            "id" => $this->parseNumberPrefix($info['telefone']),
            "btndata" => [
                "text" => $info['titleText'],
                "buttons" => $options,
                // "buttons" => [
                //     [
                //         "type" => "replyButton",
                //         "title" => "Reply this text (REPLY)"
                //     ],
                //     [
                //         "type" => "urlButton",
                //         "title" => "Click me (URL)",
                //         "payload" => "https=>//google.com"
                //     ],
                //     [
                //         "type" => "callButton",
                //         "title" => "Click to call (CALL)",
                //         "payload" => "918788889688"
                //     ]
                // ],
                "footerText" => $info['footerText']
            ]
        ];

        try {
            $response = $this->_http->request(
                'POST',
                "/message/button?key={$this->instance_key}",
                [
                    "json" => $dados
                ]
            );

            $parseResponse = (array) json_decode($response->getBody()->getContents());

            return $parseResponse;
        } catch (ClientException $cli) {
            return $this->errorsParse($cli->getMessage(), $cli->getResponse()->getBody()->getContents(), $cli->getCode());
        } catch (Exception $e) {
            return $this->errorsParse($e->getMessage(), null, $e->getCode());
        }
    }

    public function sendMessageFile(array $info)
    {
        try {
            $response = $this->_http->request(
                'POST',
                "/message/doc?key={$this->instance_key}",
                [
                    "multipart" => [
                        [
                            "name" => 'file',
                            "contents" => file_get_contents($info['link']),
                            "filename" => $info['description'],
                            'Content-type' => 'multipart/form-data, boundary=sendfile',
                            'headers'  => ['Content-Type' => 'application/pdf'],
                        ],
                        [
                            'name' => 'id',
                            'contents' => $this->parseNumberPrefix($info['telefone']),
                        ],
                        [
                            'name' => 'filename',
                            "contents" => $info['description'],
                        ]
                    ]
                ]
            );

            $parseResponse = (array) json_decode($response->getBody()->getContents());

            return $parseResponse;
        } catch (ClientException $cli) {
            return $this->errorsParse($cli->getMessage(), $cli->getResponse()->getBody()->getContents(), $cli->getCode());
        } catch (Exception $e) {
            return $this->errorsParse($e->getMessage(), null, $e->getCode());
        }
    }

    private function parseNumberPrefix($telefone)
    {
        $telefoneDDD = substr($telefone, 0, 2);
        if ($telefoneDDD >= "30") {
            $telefone = substr($telefone, 3); //ignore o prefix
        }
        $telefone = "55{$telefoneDDD}{$telefone}";
        return $telefone;
    }

    private function errorsParse($message, $content = null, $status = null)
    {
        if ($content != null) {
            $content = json_decode($content, true);
        }
        return array('error' => true, "message" => $message, "content" => $content, 'status' => $status);
    }
}
