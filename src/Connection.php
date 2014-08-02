<?php

namespace Academe\OpenErpApi;

/**
 * .
 * @package Simbigo\OpenERP
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
     * login aka username
     * @var
     */
    protected $login;

    /**
     * @var
     */
    protected $password;

    /**
     * @var
     */
    protected $logged_in = false;

    /**
     * The entry points for the static web services.
     * @var
     */
    protected $entry_points = array(
        'common' => '/xmlrpc/common',
        'object' => '/xmlrpc/object',
        'db' => '/xmlrpc/db',
        'report' => 'xmlrpc/report_spool',
    );

    /**
     * @param $host
     * @param string $charset
     * @todo initialise with the client object interface
     */
    public function __construct(RpcClientInterface $client, $database = null, $username = null, $password = null)
    {
        $this->setClient($client);
    }

    /**
     * @return client
     */
    public function getEntryPoint($service)
    {
        if ( ! isset($this->entry_points[$service])) {
            // Entry point not known.
            throw new \Exception(sprintf('Entry point for service "$service" not known.', $service));
        }

        $client = $this->getClient();

        return $this->entry_points[$service];
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
    public function getLogin()
    {
        return $this->login;
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
     * @param $db
     * @param $login
     * @param $password
     * @return int
     */
    public function setCredentials($db, $login, $password)
    {
        // Save the login details, because we will need them in subsequent calls.

        if (isset($db)) $this->db = $db;
        if (isset($login)) $this->login = $login;
        if (isset($password)) $this->password = $password;
    }

    /**
     * @param $response
     * @throws \Exception
     */
    public function throwExceptionIfFault($response)
    {
        if (isset($response['fault'])) {
            $faultArray = $response['fault']['value']['struct']['member'];
            $faultCode = 0;
            $faultString = 'Undefined fault string';

            foreach ($faultArray as $fault) {
                if ($fault['name'] == 'faultCode') {
                    $f = $fault['value'];
                    if (isset($f['string'])) {
                        $faultCode = 0;
                        $faultString = $f['string'];
                        break;
                    }

                    if (isset($f['int'])) {
                        $faultCode = $f['int'];
                    }
                }

                if ($fault['name'] == 'faultString') {
                    $faultString = $fault['value']['string'];
                }
            }

            // There is also a full stack track available, and since OpenERP API has very little
            // validation against what you are trying to do, stack dumps are a very common result
            // of data errors, unfortunately.
            throw new \Exception($faultString, $faultCode);
        }
    }
}

