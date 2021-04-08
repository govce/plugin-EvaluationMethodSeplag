<?php

namespace EvaluationMethodSeplag;

use GuzzleHttp\Client;

class SeplagAPI
{
    public $config;
    public $token;
    public $error;

    public function __construct($config, $token = null)
    {
        $this->config = $config;
        $this->token = $token;
    }

    /**
     * Authenticate SEPLAG service function
     *
     * @return boolean
     */
    public function authenticate()
    {
        $client = new Client();

        $api = $this->config['api_seplag']['auth'];

        $bodyJson = json_encode([
            'cpf' => $api["keys"]["cpf"],
            'password' => $api["keys"]["password"],
            'idSistema' => $api["keys"]["idSistema"]
        ]);

        try {
            $response = $client->post($api['URL'], [
                'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'body'    => $bodyJson
            ]);
        } catch (\Exception $e) {
            $this->token = null;
            $this->error = $e;
            return false;
        }

        $response = json_decode($response->getBody(), true);

        if (isset($response) && $response['sucesso']) {
            $this->token = $response['token'];
        }

        return true;
    }

    /**
     * Search employee by cpf function
     *
     * @param [type] $cpf
     * @return false | null | json
     */
    public function searchEmployeeByCPF($cpf)
    {
        if (!$this->authenticate()) return false;

        $client = new Client([
            'verify' => false
        ]);

        $api = $this->config['api_seplag']['search'];

        try {
            $response = $client->request($api['method'], "{$api['URL']}?numeroDocumento=$cpf", [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => "Bearer {$this->token}"
                ]
            ]);
        } catch (\Exception $e) {
            $this->error = $e;
            return false;
        }

        return json_decode($response->getBody(), true);
    }
}
