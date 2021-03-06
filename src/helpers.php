<?php
class Api_Odr
{
    /**
     * @var null|array
     *
     * @protected
     */
    protected $_result;

    /**
     * @var null|string
     *
     * @protected
     */
    protected $_error;

    protected $_logsEnabled = null;

    /**
     * @var array
     *
     * @protected
     */
    protected $_headers = array();

    /**
     * @var array
     *
     * @protected
     */
    protected $_config = array();

    /**
     * In case URL will be changed in the future
     */
    const URL = 'https://api.opendomainregistry.net/';

    const METHOD_GET     = 'GET';
    const METHOD_POST    = 'POST';
    const METHOD_PUT     = 'PUT';
    const METHOD_DELETE  = 'DELETE';
    const METHOD_OPTIONS = 'OPTIONS';
    const DEFAULT_METHOD = self::METHOD_GET;

    const MESSAGE_CURL_ERROR_FOUND = 'cURL error catched';

    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR   = 'error';

    /**
     * Class constructor
     *
     * @param array $config Configuration data
     *
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     */
    public function __construct(array $config = array())
    {
        if (extension_loaded('curl') === false) {
            throw new Api_Odr_Exception('cURL extension required by this class. Check you php.ini');
        }

        if (count($config) > 0) {
            $this->setConfig($config);
        }
    }

    /**
     * Change configuration data
     *
     * @param array $config Configuration array
     *
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     */
    public function setConfig(array $config = array())
    {
        if (count($config) === 0) {
            throw new Api_Odr_Exception('Config is empty');
        }

        foreach ($config as &$value) {
            $value = trim($value, ' /.,');
        }

        unset($value);

        $this->_config = $config;

        return $this;
    }

    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * Login procedure
     * At first, script tries to find out how signature is generated and after that actually tries to login
     * Is first step necessary? No. There is pretty slim chances that signature generation method will be changed in the future, but still, it wouldn't hurt
     *
     * @param string|null $apiKey    User's API Key
     * @param string|null $apiSecret User's API Secret
     *
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     */
    public function login($apiKey = null, $apiSecret = null)
    {
        $this->_execute('/info/user/login', self::METHOD_POST);

        if (!empty($this->_error)) {
            throw new Api_Odr_Exception(self::MESSAGE_CURL_ERROR_FOUND);
        }

        $result = $this->_result;

        if (!is_string($apiKey) || $apiKey === '') {
            $apiKey = $this->_config['api_key'];
        }

        if (!is_string($apiSecret) || $apiSecret === '') {
            $apiSecret = $this->_config['api_secret'];
        }

        $apiKey    = trim($apiKey);
        $apiSecret = trim($apiSecret);

        if ($apiKey === '' || $apiSecret === '') {
            throw new Api_Odr_Exception('You should defined `api_key` and `api_secret`');
        }

        $signatureRuleWrapper = $result['response']['fields']['signature']['signature_rule'];
        $signatureRule        = $result['response']['fields']['signature']['signature_rule_clear'];

        $wrapper = 'sha1';

        if (strpos($signatureRuleWrapper, '#SHA1(') === 0) {
            $wrapper = 'sha1';
        } elseif(strpos($signatureRuleWrapper, '#MD5(') === 0) {
            $wrapper = 'md5';
        }

        $timestamp = time();

        $r = array(
            '#API_KEY#'          => $apiKey,
            '#MD5(API_KEY)#'     => md5($apiKey),
            '#SHA1(API_KEY)#'    => sha1($apiKey),
            '#TIMESTAMP#'        => $timestamp,
            '#API_SECRET#'       => $apiSecret,
            '#MD5(API_SECRET)#'  => md5($apiSecret),
            '#SHA1(API_SECRET)#' => sha1($apiSecret),
        );

        $signature = str_replace(array_keys($r), array_values($r), $signatureRule);

        switch($wrapper) {
            case 'sha1':
                    $signature = sha1($signature);
                break;
            case 'md5':
                    $signature = md5($signature);
                break;
            default:
                break;
        }

        $data = array(
            'timestamp' => $timestamp,
            'api_key'   => $apiKey,
            'signature' => 'token$' . $signature,
        );

        $this->_execute('/user/login/', self::METHOD_POST, $data);

        $result = $this->_result;

        $this->setHeader($result['response']['as_header'], $result['response']['token']);

        return $this;
    }

    /**
     * Return list of user's domains
     *
     * @param array $filters Additional filters
     *
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     */
    public function getDomains(array $filters = array())
    {
        $data = array();

        if (!empty($filters)) {
            $data['filter'] = $filters;
        }

        return $this->_execute('/domain/', self::METHOD_GET, $data);
    }

    /**
     * Return single user domain
     *
     * @param int|string $id Domain ID or full domain name
     *
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     */
    public function getDomain($id)
    {
        return $this->_execute('/domain/' . $id . '/', self::METHOD_GET);
    }

    /**
     * Return current user data
     *
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     */
    public function getMe()
    {
        return $this->_execute('/user/', self::METHOD_GET);
    }

    /**
     * Check if domain is available or not
     *
     * @param string|int $domain Either ID or domain name
     *
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     */
    public function checkDomain($domain)
    {
        if (!is_numeric($domain) && (!is_string($domain) || $domain === '')) {
            throw new Api_Odr_Exception('Domain must be a string, but you give us a '. gettype($domain));
        }

        $domain = trim($domain, ' /.');

        if ($domain === '') {
            throw new Api_Odr_Exception('Domain name is required for this operation');
        }

        return $this->_execute('/domain/'. $domain .'/available/', self::METHOD_GET);
    }

    public function deleteDomain($domain, $deleteAt)
    {
        $data = $deleteAt;

        if (!is_array($deleteAt)) {
            $data = array(
                'deleted_at' => date('c', strtotime($deleteAt)),
            );
        }

        return $this->_execute('/domain/' . $domain .'/', Api_Odr::METHOD_DELETE, $data);
    }

    public function getDomainInfo($domain)
    {
        return $this->_execute('/domain/' . $domain . '/info/', Api_Odr::METHOD_GET);
    }

    /**
     * Update existing domain with new data
     *
     * @param string|int $id   Either ID or domain name
     * @param array      $data Data for update
     *
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     */
    public function updateDomain($id, array $data = array())
    {
        return $this->_execute('/domain/'. trim($id) .'/', self::METHOD_PUT, $data);
    }

    /**
     * Transfers domain from one user to another
     *
     * @param string|int $id   Domain ID or domain name
     * @param array      $data Data to update
     *
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     */
    public function transferDomain($id, array $data = array())
    {
        return $this->_execute('/domain/'. trim($id) .'/transfer/', self::METHOD_PUT, $data);
    }

    /**
     * Return list of user's contacts
     *
     * @param array $filters Additional filters
     *
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     */
    public function getContacts(array $filters = array())
    {
        $data = array();

        if (!empty($filters)) {
            $data['filter'] = $filters;
        }

        return $this->_execute('/contact/', self::METHOD_GET, $data);
    }

    /**
     * Get information about single contact
     *
     * @param int $id Contact ID
     *
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     */
    public function getContact($id)
    {
        if (!is_numeric($id)) {
            throw new Api_Odr_Exception('Contact ID must be numeric');
        }

        $id = (int)$id;

        if ($id <= 0) {
            throw new Api_Odr_Exception('Contact ID must be a positive number');
        }

        $this->_execute('/contact/'. $id .'/', self::METHOD_GET);

        return $this;
    }

    /**
     * Creates contact from passed data
     *
     * @param array $data Data for contact
     *
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     */
    public function createContact(array $data)
    {
        // If you want to pass data directly as part of request, you can uncomment following lines:
        /*
        if (empty($data)) {
            $data = $_REQUEST;
        }
        */

        $this->_execute('/contact/', self::METHOD_POST, $data);

        return $this;
    }

    /**
     * Registers new domain
     *
     * @param string|array $domainName Either domain name as string or whole request data as array (must have 'domain_name' key)
     * @param array        $data       Data for new domain. Only usable if $domainName is a string
     *
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     */
    public function registerDomain($domainName, array $data = array())
    {
        if (is_array($domainName) && count($data) === 0) {
            $data       = $domainName;
            $domainName = null;
        }

        if ((!is_string($domainName) || $domainName === '') && array_key_exists('domain_name', $data) === false) {
            throw new Api_Odr_Exception('No domain name defined');
        }

        if (!is_string($domainName) || $domainName === '') {
            $domainName = $data['domain_name'];
        }

        $this->_execute('/domain/'. $domainName .'/', self::METHOD_POST, $data);

        return $this;
    }

    /**
     * Get information about operation, including price and required fields
     *
     * @param string $what   About what you want to know information about. Either URL or a string
     * @param mixed  $method If $what is an URL, then method should be a string. If not, then $method might be an array (instead of data) or null
     * @param array  $data   Additional data for request
     *
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     */
    public function info($what, $method = null, array $data = array())
    {
        if (!is_string($what) || $what === '') {
            throw new Api_Odr_Exception('I don\'t understand about what you want to get information about');
        }

        $what = strtolower(trim($what));

        return $this->custom('/info/'. trim($what, '/') .'/', $method, $data);
    }

    /**
     * Information about domain registration
     *
     * @param string $domainName Domain name to get info about
     *
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     */
    public function infoRegisterDomain($domainName)
    {
        return $this->info('/domain/'. $domainName .'/', self::METHOD_POST);
    }

    public function getDomainAuthCode($domain)
    {
        return $this->_execute('/domain/auth-code/' . $domain);
    }

    /**
     * Changes autorenew state of domain
     *
     * @param string $domainName Domain name to change autorenew state
     * @param bool   $state      Set autorenew on or off
     *
     * @return Api_Odr
     */
    public function setAutorenew($domainName, $state)
    {
        return $this->custom('/domain/' . $domainName . '/renew-' . ($state ? 'on' : 'off') .'/', Api_Odr::METHOD_PUT);
    }

    /**
     * Changes autorenew state of domain
     *
     * @param string $domainName Domain name to change autorenew state
     *
     * @return Api_Odr
     */
    public function setDomainAutorenewOff($domainName)
    {
        return $this->setAutorenew($domainName, false);
    }

    /**
     * Changes autorenew state of domain
     *
     * @param string $domainName Domain name to change autorenew state
     *
     * @return Api_Odr
     */
    public function setDomainAutorenewOn($domainName)
    {
        return $this->setAutorenew($domainName, true);
    }

    /**
     * Reactivates the deleted domain
     *
     * @param string $domainName Domain name to reactivate
     *
     * @return Api_Odr
     */
    public function reactivateDomain($domainName)
    {
        return $this->_execute('/domain/' . $domainName . '/reactivate/', self::METHOD_PUT);
    }

    /**
     * Request to any custom API URL
     * Works as shorthand for $this->_execute() function
     *
     * @param string $url    Request URL
     * @param string $method cURL request method
     * @param array  $data   Data for request
     *
     * @return Api_Odr
     */
    public function custom($url, $method = self::DEFAULT_METHOD, array $data = array())
    {
        try {
            return $this->_execute($url, $method, $data);
        } catch (Api_Odr_Exception $e) {
            $this->_error = $e->getMessage();
        }

        return $this;
    }

    /**
     * Executes cURL request and return result and error
     *
     * @param string $url    Where send request
     * @param string $method What method should be called
     * @param array  $data   Additional data to send
     *
     * @return Api_Odr
     *
     * @throws Api_Odr_Exception
     *
     * @protected
     */
    protected function _execute($url = '', $method = self::DEFAULT_METHOD, array $data = array())
    {
        $this->_result = null;

        if (!is_string($method) || $method === '') {
            $method = self::DEFAULT_METHOD;
        }

        $url = rtrim($url, '/') . '/';

        $method = strtoupper($method);
        $host   = $this->getUrl();

        if (!is_string($url) || $url === '') {
            $url = $host;
        }

        if (strpos($url, '/') === 0) {
            $url = $host . ltrim($url, '/');
        }

        if (strpos($url, $host) !== 0) {
            throw new Api_Odr_Exception('Wrong host for URL ('. $url .')');
        }

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST,  $method);

        if (count($data) > 0) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        if (count($this->_headers) > 0) {
            $headers = array();

            foreach ($this->_headers as $k => $v) {
                if ($v) {
                    $headers[] = $k . ': ' . $v;
                }
            }

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $result = curl_exec($ch);

        $this->_error = curl_error($ch);

        if (empty($this->_error)) {
            $this->_result = json_decode($result, true);
        }

        $this->log('Executed [' . $method . '] ' . $url);

        curl_close($ch);

        if (!empty($this->_error)) {
            throw new Api_Odr_Exception($this->_error);
        }

        return $this;
    }

    /**
     * Return request result
     *
     * @return null|array
     */
    public function getResult()
    {
        return $this->_result;
    }

    /**
     * Return possible cURL error
     *
     * @return null|string
     */
    public function getError()
    {
        return $this->_error;
    }

    /**
     * Returns all headers, that will be set for request
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->_headers;
    }

    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getHeader($name, $default = null)
    {
        $name = strtolower($name);

        return empty($this->_headers[$name]) ? $default : $this->_headers[$name];
    }

    /**
     * Returns usable API URL
     *
     * @return string
     */
    public function getUrl()
    {
        return rtrim(empty($this->_config['url']) ? self::URL : $this->_config['url'], '/') . '/';
    }

    /**
     * Sets header value
     *
     * @param string|array $name  Either array with headers to set or header key name
     * @param mixed        $value Value for header (only if $name is string)
     *
     * @return Api_Odr
     */
    public function setHeader($name, $value = null)
    {
        if (!is_array($name)) {
            $name = array(
                strtolower($name) => $value,
            );
        }

        $this->_headers = array_merge($this->_headers, $name);

        return $this;
    }

    public function updateContact($handle, array $data)
    {
        return $this->custom('/contact/' . $handle, Api_Odr::METHOD_PUT, $data);
    }

    public function log($message, $prio = 'normal')
    {
        if ($this->_logsEnabled === false || empty($this->_config['enable_logs'])) {
            return true;
        }

        $filepath = empty($this->_config['logs_path']) ? __DIR__ . '/log/api' . date('Ymd_H') .'.log' : $this->_config['logs_path'];

        $r = array(
            '#DAY#'   => date('d'),
            '#MONTH#' => date('m'),
            '#YEAR#'  => date('Y'),
            '#HOUR#'  => date('H'),
        );

        $file = str_replace(array_keys($r), array_values($r), $filepath);

        if (!is_dir(dirname($file))) {
            $this->_logsEnabled = false;

            return true;
        }

        $data = json_encode($this->getResult());

        $to = <<<MESSAGE
[{$prio}] {$message} {$data}
MESSAGE;

        error_log($to . "\r\n", 3, $file);

        if (!is_writeable($file)) {
            $this->_logsEnabled = false;

            return true;
        }

        $this->_logsEnabled = true;

        return true;
    }
}

class Api_Odr_Exception extends Exception
{
}