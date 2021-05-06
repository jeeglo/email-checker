<?php

namespace Jeeglo\EmailChecker;

use GuzzleHttp\Client;

class EmailChecker {

    // Initialize the protected properties
    protected $api_key;
    protected $api_url;
    protected $responseData = [];

    /**
     * EmailVerifier constructor.
     * @param $api_key
     * @param $api_url
     */
    public function __construct($api_key)
    {
        // set the protected properties with construct variables
        $this->api_key = $api_key;
        $this->api_url = 'https://api.emailable.com/v1/';
    }

    /**
     * Validate the email address with the API call to third party (Emailable) service
     * In response, 'state' key contains the following values: deliverable, undeliverable, risky, unknown
     * @param $email
     * @return bool
     */
    public function isDeliverable($email)
    {
        // set default '$is_valid' as true
        $is_valid = true;

        // prepare the data to send with API call
        $params = ['api_key' => $this->api_key, 'email' => $email];

        // call the API with guzzle (curl)
        $response = $this->curlRequest($this->api_url. 'verify', 'GET', $params);

        // Emailable API will return JSON format data, if we found the response we'll decode
        $this->responseData = $this->decodeResponse($response);

        // get the state value
        $state = $this->getParamValue('state');

        // if the state value is equal to 'undeliverable' then we'll set the '$is_valid' variable value to false
        if($state && $state === 'undeliverable') {
            $is_valid = false;
        }

        // return response
        return $is_valid;
    }

    /**
     * Validate the email address with the API call to third party (Emailable) service
     * In response, 'disposable' key contains the value '' or 1 if disposable email found
     * @param $email
     * @return bool
     */
    public function isDisposable($email)
    {
        // prepare the data to send with API call
        $params = ['api_key' => $this->api_key, 'email' => $email];

        // call the API with guzzle (curl)
        $response = $this->curlRequest($this->api_url. 'verify', 'GET', $params);

        // Emailable API will return JSON format data, if we found the response we'll decode
        $this->responseData = $this->decodeResponse($response);

        // get the disposable param key value - if the disposable value is 1 we'll return true else false
        if($this->getParamValue('disposable')) {
            return true;
        }

        // default return false
        return false;
    }

    /**
     * Decode the JSON response from Emailable API
     * @param $response
     * @return array|mixed
     */
    protected function decodeResponse($response)
    {
        return !empty($response) ? json_decode($response, true) : [];
    }

    /**
     * check if particular parameter key is exist in the responseData
     * @param $key
     * @return mixed|null
     */
    protected function getParamValue($key)
    {
        return !empty($this->responseData[$key]) ? $this->responseData[$key] : null;
    }

    /**
     * Generic method to make cURL requests
     * @param $url
     * @param string $type
     * @param array $params
     * @param array $headers
     * @return bool|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function curlRequest($url, $type = 'POST', $params = [], $headers = [])
    {
        $client = new Client();

        try {
            // Initialize cURL request
            $res = $client->request( $type, trim($url), [
                'form_params' => $params,
                'timeout' => 30,
                'headers' => $headers
            ]);

            $response_body = $res->getBody()->getContents();

            // Set Status
            $status = 1;
        }
        catch( \GuzzleHttp\Exception\ServerException $e ) {
            $status = 2;
        }
        catch( \GuzzleHttp\Exception\ClientException $e ) {
            $status = 2;
        }
        catch( \GuzzleHttp\Exception\BadResponseException $e ) {
            $status = 2;
        }
        catch( \GuzzleHttp\Exception\ConnectException $e ) {
            $status = 2;
        }
        catch( \Exception $e ) {
            $status = 2;
        }

        if($status == 2) {
            $response_body = false;
        }

        return $response_body;
    }
}