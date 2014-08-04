<?php

namespace Academe\OpenErpApi;

use Zend;

/**
 * Class XmlRpcClient
 * @package Simbigo\OpenERP
 * @todo Create an interface for use by multiple clients.
 */
class XmlRpcClient implements RpcClientInterface
{
    /**
     * @var 
     */
    protected $defaultPath = '';

    /**
     * @var string
     */
    public $userAgent = 'Simbigo XML-RPC Client';

    /**
     * @var
     */
    private $_host;

    /**
     * @var
     */
    private $_port;

    /**
     * @var string
     */
    private $_path = '';

    /**
     * @var string
     */
    private $_charset = 'utf-8';

    /**
     * @var
     */
    private $lastRawResponse;

    /**
     * @var
     */
    private $lastRequest;

    /**
     * @param $host
     * @param int $port
     * @param string $charset
     */
    public function __construct($host, $port = 8069, $charset = 'utf-8')
    {
        $this->setParams($host, $port, $charset);
    }

    /**
     * Set all paramters in one go.
     */
    public function setParams($host = null, $port = null, $charset = null)
    {
        if (isset($host)) {
            $urlInfo = parse_url($host);

            $scheme = $urlInfo['scheme'];
            $host = $urlInfo['host'];
            $port = isset($urlInfo['port']) ? $urlInfo['port'] : (isset($port) ? $port : '8069');

            $path = isset($urlInfo['path']) ? $urlInfo['path'] : null;

            // If the path is "xmlrpc" then strip it off - we will be adding it
            // in each entry point path. If the path is not "xmlrpc" then assume
            // OpenERP is install on a a non-root path, so keep it.
            // CHECKME: if the path is "/myinstance/xmlrpc" then we probably need to
            // strip "xmlrpc" from the end.
            // However, defaultPath is not actually used anywhere, so it's a moot.

            if ($path !== null && trim($path, '/') != 'xmlrpc') {
                $this->defaultPath = rtrim($path, '/');
            } else {
                $this->defaultPath = '';
            }

            $this->setHost($scheme . '://' . $host);
        }

        if (isset($port)) {
            $this->setPort($port);
        }

        if (isset($charset)) {
            $this->setCharset($charset);
        }
    }

    /**
     * @return mixed
     */
    public function getHost()
    {
        return $this->_host;
    }

    /**
     * @param $host
     */
    public function setHost($host)
    {
        $this->_host = rtrim($host, '/');
    }

    /**
     * @param $charset
     */
    public function setCharset($charset)
    {
        $this->_charset = $charset;
    }

    /**
     * @return string
     */
    public function getCharset()
    {
        return $this->_charset;
    }

    /**
     * @return mixed
     */
    public function getPort()
    {
        return $this->_port;
    }

    /**
     * @param $port
     */
    public function setPort($port)
    {
        $this->_port = (int)$port;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->_path;
    }

    /**
     * @param $path
     */
    public function setPath($path)
    {
        $this->_path = $path;
    }

    /**
     * @return mixed
     */
    public function getLastResponse()
    {
        return $this->lastRawResponse;
    }

    /**
     * @return mixed
     */
    public function getLastRequest()
    {
        return $this->lastRequest;
    }

    /**
     * @param $method
     * @param array $params
     * @return mixed|\SimpleXMLElement|string
     */
    public function call($method, $params = array())
    {
        if (function_exists('xmlrpc_encode_request')) {
            $options = array(
                'encoding' => $this->getCharset(), 
                'version' => 'xmlrpc',
                'escaping' => 'markup',
            );
            $payload = xmlrpc_encode_request($method, $params, $options);
        } else {
            $payload = $this->encodeRequest($method, $params);
        }

        $this->lastRequest = $payload;

        $context = stream_context_create(array(
            'http' => array(
                'method' => "POST",
                'header' => $this->getDefaultHeader(),
                'content' => $payload
            )
        ));

        $uri = $this->getHost() . ':' . $this->getPort() . $this->getPath();

        // Make the call through the Zend XML-RPC client.
        $client = new Zend\XmlRpc\Client($uri);
        $response = $client->call($method, $params);

        return $response;

        $xml = file_get_contents($uri, false, $context);

        $this->lastRawResponse = $xml;

        $response = new \SimpleXMLElement($xml);

        $response = json_encode($response);
        $response = json_decode($response, true);

        return $response;
    }

    /**
     * @return string
     */
    public function getDefaultHeader()
    {
        $headers  = "";
        $headers .= "Content-Type: text/xml\r\n";
        $headers .= "User-Agent: " . $this->userAgent . "\r\n";
        return $headers;
    }

    /**
     * @param $method
     * @param array $params
     * @return string
     */
    public function encodeRequest($method, array $params)
    {
        $payload = '<?xml version="1.0" encoding="' . $this->getCharset() . '"?>' . "\r\n";
        $payload .= "\t" . '<methodCall>' . "\r\n";
        $payload .= "\t\t" . '<methodName>' . $method . '</methodName>' . "\r\n";
        $payload .= "\t\t" . '<params>' . "\r\n";

        foreach ($params as $param) {
            $payload .= "<param>\r\n" . $this->encodeParam($param) . "</param>\r\n";
        }

        $payload .= "\t\t" . '</params>' . "\r\n";
        $payload .= "\t" . '</methodCall>' . "\r\n";

        // Now use PHP's build-in XML-RPC functionality. Or a library?

        return $payload;
    }

    /**
     * @param $param
     * @return bool|string
     */
    public function encodeParam($param)
    {
        switch (gettype($param)) {
            case 'boolean':
                $encoded = '<value><boolean>' . $param . '</boolean></value>' . "\r\n";
                break;

            case 'double':
                $encoded = '<value><double>' . $param . '</double></value>' . "\r\n";
                break;

            case 'integer':
                $encoded = '<value><int>' . $param . '</int></value>' . "\r\n";
                break;

            case 'string':
                $encoded = '<value><string>' . htmlspecialchars($param) . '</string></value>' . "\r\n";
                break;

            case 'array':
                $encoded = $this->encodeArray($param);
                break;

            default:
                $encoded = false;
                break;
        }
        return $encoded;
    }

    /**
     * @param $array
     * @return string
     */
    private function encodeArray($array)
    {
        if ($this->isAssoc($array)) {
            $encoded = '<struct>' . "\r\n";

            foreach ($array as $key => $value) {
                $encoded .= '<member>' . "\r\n";
                $encoded .= '<name>' . $key . '</name>' . "\r\n";
                $encoded .= $this->encodeParam($value);
                $encoded .= '</member>' . "\r\n";
            }

            $encoded .= '</struct>' . "\r\n";
        } else {
            $encoded = '<array>' . "\r\n";
            $encoded .= '<data>' . "\r\n";

            foreach ($array as $value) {
                $encoded .= $this->encodeParam($value);
            }

            $encoded .= '</data>' . "\r\n";
            $encoded .= '</array>' . "\r\n";
        }

        return $encoded;
    }

    /**
     * @param $array
     * @return bool
     */
    private function isAssoc($array)
    {
        if (is_array($array) && !is_numeric(array_shift(array_keys($array)))) {
            return true;
        }

        return false;
    }
}