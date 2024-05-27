<?php

// namespace WhatsappSdk;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class SDK
{
    const url = "https://wapp.esistemasinteligentes.com.br";

    protected $_http;
    protected $instance_key;
    protected $globalToken;
    function __construct($instance_key = null, $globalToken = null)
    {
        $this->instance_key = $instance_key ?? "deployment";
        $this->globalToken = $globalToken;
        $this->_http = new Client([
            'base_uri' => self::url,
            "headers" => [
                'Apikey' => $globalToken,
            ],
        ]);
    }

    public function initSession()
    {
        try {
            $response = $this->_http->request(
                'POST',
                "/instance/create",
                [
                    "json" => [
                        "instanceName" => $this->instance_key,
                        "description" => "API"
                    ]
                ]
            );

            $parseResponse = json_decode($response->getBody()->getContents(), true);
            return $parseResponse;
        } catch (ClientException $cli) {
            return $this->errorsParse($cli->getMessage(), $cli->getResponse()->getBody()->getContents(), $cli->getCode());
        } catch (Exception $e) {
            return $this->errorsParse($e->getMessage(), null, $e->getCode());
        }
    }

    public function setToken($bearerToken)
    {
        // Atualize o cliente Guzzle com o novo token
        $this->_http = new Client([
            'base_uri' => self::url,
            'headers' => [
                'Apikey' => $this->globalToken,
                'Authorization' => 'Bearer ' . $bearerToken,
            ],
        ]);
    }

    public function getSession()
    {
        try {
            $response = $this->_http->request(
                'GET',
                "/instance/fetchInstances",
                [
                    "query" => [
                        "instanceName" => $this->instance_key
                    ]
                ]
            );

            $parseResponse = json_decode($response->getBody()->getContents(), true);

            return $parseResponse;
        } catch (ClientException $cli) {
            return $this->errorsParse($cli->getMessage(), $cli->getResponse()->getBody()->getContents(), $cli->getCode());
        } catch (Exception $e) {
            return $this->errorsParse($e->getMessage(), null, $e->getCode());
        }
    }

    public function getSessionStatus()
    {
        try {
            $response = $this->_http->request(
                'GET',
                "/instance/connectionState/" . $this->instance_key,
            );

            $parseResponse = json_decode($response->getBody()->getContents(), true);

            return $parseResponse;
        } catch (ClientException $cli) {
            return $this->errorsParse($cli->getMessage(), $cli->getResponse()->getBody()->getContents(), $cli->getCode());
        } catch (Exception $e) {
            return $this->errorsParse($e->getMessage(), null, $e->getCode());
        }
    }

    // public function restartSession()
    // {
    //     $checkSession = $this->getSession();
    //     if (isset($checkSession['error']) && $checkSession['error'] == true) {
    //         $this->parseSessionResult($checkSession['content']);
    //     }
    // }

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
                "/instance/delete/{$this->instance_key}"
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
                "/instance/logout/{$this->instance_key}"
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
                "/instance/connect/{$this->instance_key}"
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
        $dados["number"] = $this->parseNumberPrefix($info['telefone']);
        $dados['options'] = [
            "delay" => 1200,
            "presence" => "composing"
        ];
        $dados["textMessage"] = [
            'text' => $info['message']
        ];

        $endPoint = "sendText";

        try {
            $response = $this->_http->request(
                'POST',
                "/message/{$endPoint}/{$this->instance_key}",
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

    public function sendMessageFilePDF(array $info)
    {
        try {
            $response = $this->_http->request(
                'POST',
                "/message/sendMediaFile/{$this->instance_key}",
                [
                    "multipart" => [
                        [
                            'name' => 'number',
                            'contents' => $this->parseNumberPrefix($info['telefone']),
                        ],
                        [
                            "name" => 'attachment',
                            "contents" => file_get_contents($info['link']),
                            "filename" => $info['filename'],
                            'Content-type' => 'multipart/form-data, boundary=sendfile',
                            'headers'  => ['Content-Type' => 'application/pdf'],
                        ],
                        [
                            'name' => 'mediatype',
                            "contents" => 'document',
                        ],
                        [
                            'name' => 'caption',
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
        return "55{$telefone}";
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
