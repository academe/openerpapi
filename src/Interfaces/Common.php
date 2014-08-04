<?php

namespace Academe\OpenErpApi\Interfaces;

use Academe\OpenErpApi;

/**
 * Access to the "common" interface.
 * @package Simbigo\OpenERP
 */
class Common extends InterfacesAbstract
{
    /**
     * The service name
     */
    protected $service = 'common';

    /**
     * The login method is actually a part of the common interface. We may move it there
     * depending on whether we need to keep track of the login state here in the Connection.
     *
     * @param $db
     * @param $login
     * @param $password
     * @return int
     */
    public function login($db = null, $login = null, $password = null)
    {
        // Keep the credentials on the connection for use by other interfaces.
        $this->connection->setCredentials($db, $login, $password);

        $client = $this->connection->getClient();
        $client->setPath($this->connection->getEntryPoint($this->service));

        $uid = $client->call(
            'login',
            array(
                $this->connection->getDb(),
                $this->connection->getLogin(),
                $this->connection->getPassword(),
            )
        );

        $this->connection->setUid($uid);

        // Set a flag against the connection to indicate whether a connection
        // has been made or not (i.e. that the credentials are valid). This may
        // actually be of no use, so prepare to remove it if the connection turns
        // out to be totally sessionless.

        // The assumption is that there is no zero uid.
        $this->connection->setLoggedIn($uid);

        return $uid;
    }

    /**
     * @param null $db
     * @param null $login
     * @param null $password
     * @return mixed
     */
    public function getTimezone()
    {
        $client = $this->connection->getClient();
        $client->setPath($this->connection->getEntryPoint($this->service));

        $params = array(
            $this->connection->getDb(),
            $this->connection->getLogin(),
            $this->connection->getPassword(),
        );

        $response = $client->call('timezone_get', $params);
        $this->connection->throwExceptionIfFault($response);

        return $response['params']['param']['value']['string'];
    }

    /**
     * @param bool $extended
     * @return mixed
     * @todo FIXME - this is not working
     */
    public function about($extended = false)
    {
        $client = $this->connection->getClient();
        $client->setPath($this->connection->getEntryPoint($this->service));

        $response = $client->call('about', array($extended));
        $this->connection->throwExceptionIfFault($response);

        return $response['params']['param']['value']['string'];
    }

    /**
     * @return mixed
     */
    public function version()
    {
        $client = $this->connection->getClient();
        $client->setPath($this->connection->getEntryPoint($this->service));

        $response = $client->call('version');
        $this->connection->throwExceptionIfFault($response);

        $version = $response['params']['param']['value']['struct']['member'][0]['value']['string'];
        $this->version = $version;

        return $version;
    }
}

