<?php

namespace SeetoLight\Tracking\Tracking;

trait Request
{
    # @var string The client id to be used for requests.
    public $clientId;

    # @var string The base URL to be used for requests.
    public $apiBaseUrl = "api.51tracking.com";

    # @var string The port to be used for requests.
    public $apiPort = 443;

    # @var string The version to be used for requests.
    public $apiVersion = "v3";

    # @var string The path to be used for requests.
    public $apiPath;

    # @var string The header key.
    public $headerKey = "Tracking-Api-Key";

    # @var string The tracking number.
    public $trackingNumber;

    # @var string The express.
    public $trackingExpress;

    # @var string The lang.
    public $trackingLang;

    # @var string The pattern express.
    public $patternExpress = "/^[0-9a-z-_]+$/i";

    # @var string The pattern number.
    public $patternNumber = "/^[0-9a-z-_]{5,}$/i";

    # @var string The pattern lang.
    public $patternLang = "/^[a-z]{2}$/i";
    /**
     * @var string
     */
    private $apiKey;

    # @var string The url to be used for requests.
    public $url;

    # @var array The params to be used for requests.
    public $params = [];

    # @var array The header to be used for requests.
    public $header = [];

    # @var int The header to be used for requests.
    public $timeout = 60;

    # @var boolean The header to be used for requests.
    public $isHeader = false;

    /**
     * @param $data
     * @param $keys
     * @return mixed
     */
    protected function filter_data($data, $keys)
    {
        if (count($data) < count($data, COUNT_RECURSIVE)) {
            foreach ($data as $k => $val) {
                $data[$k] = array_filter($data, function ($val, $key) use ($keys) {
                    if (in_array($key, $keys)) {
                        return true;
                    }
                    return false;
                }, ARRAY_FILTER_USE_BOTH);
            }
            return $data;
        }
        return $data;

    }

    /**
     * Check if the express meets the requirements.
     * @return bool|false|int
     */
    protected function checkExpressRequirements()
    {
        if (empty($this->trackingExpress) || !is_string($this->trackingExpress)) return false;
        return preg_match($this->patternExpress, $this->trackingExpress);
    }

    /**
     * Check if the tracking number meets the requirements.
     * @return bool|false|int
     */
    protected function checkNumberRequirements()
    {
        if (empty($this->trackingNumber) || !is_string($this->trackingNumber)) return false;
        return preg_match($this->patternNumber, $this->trackingNumber);
    }

    /**
     * Check if the tracking number meets the requirements.
     * @return bool|false|int
     */
    protected function checkLangRequirements()
    {
        if (empty($this->trackingLang) || !is_string($this->trackingLang)) return false;
        return preg_match($this->patternLang, $this->trackingLang);
    }

    /**
     * Check if the tracking number and express meets the requirements.
     * @param $params
     * @return bool|int
     */
    protected function checkParamsRequirements($params)
    {
        $this->trackingNumber = empty($params["tracking_number"]) ? "" : trim($params["tracking_number"]);
        $this->trackingExpress = empty($params["courier_code"]) ? "" : trim($params["courier_code"]);
        $this->trackingLang = empty($params["lang"]) ? "" : trim($params["lang"]);
        if (!$this->checkLangRequirements()) $this->trackingLang = "";
        if (!$this->checkNumberRequirements()) return 4014;
        if (!$this->checkExpressRequirements()) return 4015;
        return true;
    }

    /**
     * Check if the data meets the requirements.
     * @param $data
     * @return false|mixed|string
     */
    protected function checkSendApi($data)
    {
        if (empty($data) || !is_array($data)) return $this->errorResponse(4501);
        $params = [];
        foreach ($data as $value) {
            $check = $this->checkParamsRequirements($value);
            if ($check !== true) continue;
            $params[] = $value;
        }
        if (empty($params)) return $this->errorResponse(4501);
        return $this->sendApiRequest($params);
    }

    /**
     * gets the header to be used for requests.
     * @return string[]
     */
    protected function getRequestHeader()
    {
        $header = [
            "Content-Type: application/json",
            $this->headerKey . ": " . $this->apiKey,
        ];
        if (!empty($this->clientId)) $header[] = "Client-Id: " . $this->clientId;
        return $header;
    }

    # @var string The pattern number.
    public $airPatternNumber = "/^([0-9]{3})([ \-]{1})?([0-9]{7,8})$/";

    /**
     * Check if the air number meets the requirements.
     * @return bool|false|int
     */
    public function checkAirNumberRequirements()
    {
        if (empty($this->airNumber) || !is_string($this->airNumber)) return false;
        return preg_match($this->airPatternNumber, $this->airNumber);
    }

    /**
     * send api request.
     * @param array $params
     * @param string $method
     * @return mixed|string
     */
    protected function sendApiRequest($params = [], $method = "GET")
    {
        $this->url = $this->getBaseUrl($this->apiPath);
        $this->params = $params;
        $this->header = $this->getRequestHeader();
        return $this->send($method);
    }

    /**
     * error params request.
     * @param $code
     * @param string $message
     * @param array $data
     * @return false|string
     */
    protected function errorResponse($code, $message = "", $data = [])
    {
        $errorMessage = [
            4014 => "The value of `tracking_number` is invalid.",
            4015 => "The value of `courier_code` is invalid.",
            4501 => "The submitted parameters do not meet the requirements",
            4502 => "Signature verification failed",
            4503 => "No data received or data format error",
        ];
        if (
            empty($message)
            && isset($errorMessage[$code])
        ) $message = $errorMessage[$code];
        $json = [
            "meta" => [
                "code" => $code,
                "type" => "Error",
                "message" => $message,
            ],
            "data" => $data,
        ];
        return json_encode($json);
    }

    /**
     * send api request.
     * @param string $method
     * @return bool|string
     */
    protected function send($method = "GET")
    {
        $method = strtoupper($method);
        if (
            empty($this->url)
            || empty($this->header)
        ) return "";
        $curl = curl_init();
        if (
            !empty($this->params)
            && $method === "GET"
        ) {
            $this->url .= "?" . http_build_query($this->params);
            $this->params = null;
        }
        curl_setopt($curl, CURLOPT_URL, $this->url);
        if (
            !empty($this->params)
            && !is_string($this->params)
        ) $this->params = json_encode($this->params);
        switch ($method) {
            case "GET" :
                curl_setopt($curl, CURLOPT_HTTPGET, true);
                break;
            case "POST" :
                curl_setopt($curl, CURLOPT_POST, true);
                break;
            default :
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
                break;
        }
        if (0 === strpos($this->url, "https")) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, $this->isHeader);
        if (!empty($this->params)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $this->params);
            $this->header[] = "Content-Length: " . strlen($this->params);
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->header);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        $response = curl_exec($curl);
        curl_close($curl);
        unset($curl);
        return $response;
    }

}