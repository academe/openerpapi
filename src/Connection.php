<?php

namespace Academe\OpenErpApi;

/**
 * Handles the connection to OpenERP at the application level.
 * @package Academe\OpenErpApi
 * @todo Add a root path that can prefix the entry point.
 */
class Connection implements ConnectionInterface
{
    /**
     * @var 
     */
    protected $client;

    /**
     * @var 
     */
    protected $uid;

    /**
     * @var 
     */
    protected $version;

    /**
     * @var
     */
    protected $db;

    /**
     * @var
     */
    protected $username;

    /**
     * @var
     */
    protected $password;

    /**
     * Used if OpenERP is not installed at/running from the root folder of the virtual server.
     * @var
     */
    protected $root_path = '';

    /**
     * @var
     */
    protected $logged_in = false;

    /**
     * The entry points for the static web services.
     * @var
     * @todo Add methods to manage this list.
     */
    protected $entry_points = array(
        'common' => '/xmlrpc/common',
        'object' => '/xmlrpc/object',
        'db' => '/xmlrpc/db',
        'report' => '/xmlrpc/report_spool',
    );

    /**
     * @param $host
     * @param string $charset
     * @todo initialise with the client object interface
     */
    public function __construct(RpcClientInterface $client)
    {
        $this->setClient($client);
    }

    /**
     * @return string The entry point path.
     */
    public function getEntryPoint($service)
    {
        if ( ! isset($this->entry_points[$service])) {
            // Entry point not known.
            throw new \Exception(sprintf('Entry point for service "$service" not known.', $service));
        }

        return $this->root_path . $this->entry_points[$service];
    }

    /**
     * @return $this
     */
    public function setClient(RpcClientInterface $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return XmlRpcClient
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return string
     */
    public function getCharset()
    {
        return $this->getClient()->getCharset();
    }

    /**
     * @return mixed
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * @return mixed
     */
    public function setUid($uid)
    {
        $this->uid = $uid;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return mixed
     */
    public function isLoggedIn()
    {
        return !empty($this->logged_in);
    }

    /**
     * @return mixed
     */
    public function setLoggedIn($logged_in)
    {
        $this->logged_in = !empty($logged_in);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getRootPath()
    {
        return $this->root_path;
    }

    /**
     * @return this
     */
    public function setRootPath($root_path)
    {
        // To work with the entry point list, needs a leading '/', but no trailing '/'.

        $this->root_path = (trim($root_path, '/') != '' ? '/' . trim($root_path, '/') : '');

        return $this;
    }

    /**
     * Set the path for a given service.
     * @return this
     */
    public function setService($service)
    {
        $this->setPath($this->getEntryPoint($service));
    }

    /**
     * @param $db
     * @param $username
     * @param $password
     * @return int
     */
    public function setCredentials($db, $username, $password)
    {
        // Save the username details, because we will need them in subsequent calls.

        if (isset($db)) $this->db = $db;
        if (isset($username)) $this->username = $username;
        if (isset($password)) $this->password = $password;
    }

    /**
     * Pass any further calls on to the client to handle.
     */
    public function __call($method, $params)
    {
        return call_user_func_array(array($this->client, $method), $params);
    }
}

