<?php

namespace Academe\OpenErpApi;

use fXmlRpc;
use fXmlRpc\Exception as RpcException;

/**
 * Class XmlRpcClient
 * @package Academe\OpenErpApi
 * @todo Charset is probably irrelevant here now.
 */
class XmlRpcClient implements RpcClientInterface
{
    /**
     * @var
     */
    protected $host;

    /**
     * @var
     */
    protected $port;

    /**
     * @var string
     */
    protected $path = '';

    /**
     * @var string
     */
    protected $charset = 'utf-8';

    /**
     * @var
     */
    protected $lastRawResponse;

    /**
     * @var
     */
    protected $lastRequest;

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
     * Set all parameters in one go. Except the path, which is set per call as it changes
     * for each interface anyway.
     */
    public function setParams($host = null, $port = null, $charset = null)
    {
        if (isset($host)) {
            $urlInfo = parse_url($host);

            $scheme = $urlInfo['scheme'];
            $host = $urlInfo['host'];
            $port = isset($urlInfo['port']) ? $urlInfo['port'] : (isset($port) ? $port : '8069');

            $path = isset($urlInfo['path']) ? $urlInfo['path'] : null;

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
        return $this->host;
    }

    /**
     * @param $host
     */
    public function setHost($host)
    {
        $this->host = rtrim($host, '/');
    }

    /**
     * @param $charset
     */
    public function setCharset($charset)
    {
        $this->charset = $charset;
    }

    /**
     * @return string
     */
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * @return mixed
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param $port
     */
    public function setPort($port)
    {
        $this->port = (int)$port;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param $path
     */
    public function setPath($path)
    {
        $this->path = $path;
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
     * @return mixed
     */
    public function call($method, $params = array())
    {
        $this->lastRequest = array(
            'method' => $method,
            'params' => $params,
        );

        $uri = $this->getHost() . ':' . $this->getPort() . $this->getPath();

        // Make the call through the fast-XmlRpc client.
        $client = new fXmlRpc\Client($uri);

        try {
            $response = $client->call($method, $params);
        } catch (RpcException\ResponseException $e) {
            // Get the details of the error, and pass it on.
            // We don't have a response, so can't return with anything sensible.
            // Here we just concatenate the faultCode and the faultString, since in practice both of
            // them seeto be string and either of them can contain the error message.

            // CHECKME: if we get $e->getFaultCode() == 'AccessDenied' then we have possibly
            // been logged out. It may be worth checking the authentication status and setting
            // the session as logged out. However, AccessDenied may be a more general error that
            // is applied to individual models and not just the whole login session. There will be
            // a way to check though.

            // TODO: store these error parts separately, so they are available later on.
            // Or maybe just don't catch it? Think it through.

            throw new RpcException\ResponseException(trim($e->getFaultCode() . ' ' . $e->getFaultString()), $e->getCode(), $e);
        }

        $this->lastRawResponse = $response;

        return $response;
    }
}
