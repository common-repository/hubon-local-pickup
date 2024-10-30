<?php

class Hubon_Rest_Adapter
{
    private $timeout;
    public $hubon_secret_key;
    public $base_url;
    public function __construct($hubon_secret_key)
    {
        $this->hubon_secret_key = $hubon_secret_key;
        $this->base_url = HUBON_API_URL;
        $this->timeout = 50;
    }

    function generateSignature($params)
    {
        $method = $params['method'];
        $url = $params['url'];
        $body = !empty($params['body']) ? wp_json_encode($params['body']) : "";
        $timestamp = $params['timestamp'];
        $secretKey = $params['secretKey'];

        $body = preg_replace('/\s+/', '', $body);
        $data = strtoupper("{$method}:{$url}:{$body}");
        $payload = "{$data}:{$timestamp}";
        $signature = base64_encode(hash_hmac('sha512', $payload, $secretKey, true));

        $signatureUrlSafe = str_replace(['+', '/'], ['-', '_'], $signature);
        return $signatureUrlSafe;
    }


    function getStringBeforeDash($inputString)
    {
        $parts = explode('-', $inputString, 2);
        return $parts[0];
    }

    public function http_get($url, $params = [], $extraHeaders = [])
    {
        $headers = array_merge([
            "Content-Type" => "application/json",
            "HubOn-Client-ID" => HUBON_CLIENT_ID,
        ], $extraHeaders);

        if (!empty($params)) {
            $url = add_query_arg($params, $url);
        }

        $args = array(
            'headers' => $headers,
            'timeout' => $this->timeout,
        );

        $response = wp_remote_get($url, $args);
        if (is_wp_error($response)) {
            return __('Something went wrong, please try again', 'hubon-local-pickup');
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    public function http_post($url, $body, $headers = [])
    {
        $headers = array_merge([
            "Content-Type" => "application/json",
            "HubOn-Client-ID" => HUBON_CLIENT_ID,
        ], $headers);

        $args = array(
            'body' => wp_json_encode($body),
            'headers' => $headers,
            'timeout' => $this->timeout,
        );

        $response = wp_remote_post($url, $args);
        if (is_wp_error($response)) {
            return __('Something went wrong, please try again', 'hubon-local-pickup');
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    public function http_put($url, $body, $headers = [])
    {
        $headers = array_merge([
            "Content-Type" => "application/json",
            "HubOn-Client-ID" => HUBON_CLIENT_ID,
        ], $headers);

        $args = array(
            'method'    => 'PUT',
            'body'      => wp_json_encode($body),
            'headers'   => $headers,
            'timeout'   => $this->timeout,
        );

        $response = wp_remote_request($url, $args);
        if (is_wp_error($response)) {
            return __('Something went wrong, please try again', 'hubon-local-pickup');
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    public function get_nearest_hub($query)
    {
        $url = $this->base_url . '/external/v1/hubs/shopify_webhook';
        $data = $this->http_post($url, $query);

        if (!is_array($data)) return $data;

        if (!empty($data) && isset($data["error"]) && !isset($data['rates'])) {
            return $data;
        }

        $rates = $data["rates"];

        return $rates;
    }

    public function customer_info($secretKey)
    {
        $encode_user = $this->getStringBeforeDash($secretKey);

        $params = [
            'method' => "GET",
            'url' => $this->base_url . '/external/v1/customers/info',
            'body' => '',
            'timestamp' => gmdate('D, d M Y H:i:s \G\M\T'),
            'secretKey' => $secretKey
        ];

        $signature = (string) $this->generateSignature($params);

        $customHeaders = [
            "Request-Date" => $params['timestamp'],
            "HubOn-Signature" => $signature,
            "Encoded-User-ID" => $encode_user,
        ];

        $data = $this->http_get($params['url'], [], $customHeaders);

        if (!is_array($data)) return $data;

        if (!empty($data) && isset($data["error"]) && !isset($data['registered_customer'])) {
            return $data;
        }

        return $data;
    }

    public function create_transport($data)
    {
        $encode_user = $this->getStringBeforeDash($this->hubon_secret_key);

        $params = [
            'method' => "POST",
            'url' => $this->base_url . '/external/v1/transports',
            'body' => $data,
            'timestamp' => gmdate('D, d M Y H:i:s \G\M\T'),
            'secretKey' => $this->hubon_secret_key
        ];

        $signature = (string) $this->generateSignature($params);

        $customHeaders = [
            "Request-Date" => $params['timestamp'],
            "HubOn-Signature" => $signature,
            "Encoded-User-ID" => $encode_user,
        ];

        $response = $this->http_post($params['url'], $params['body'], $customHeaders);

        if (!is_array($response)) return $response;

        if (!empty($response) && isset($response["error"]) && !isset($response['registered_customer'])) {
            return $response;
        }

        return $response;
    }
}
