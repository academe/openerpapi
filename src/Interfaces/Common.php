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

        $this->connection->setService($this->service);

        $uid = $this->connection->call(
            'login',
            array(
                $this->connection->getDb(),
                $this->connection->getUsername(),
                $this->connection->getPassword(),
            )
        );

        $this->connection->setUid($uid);

        // The assumption is that there is no zero uid.
        $this->connection->setLoggedIn( ! empty($uid));

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
        $this->connection->setService($this->service);

        $params = array(
            $this->connection->getDb(),
            $this->connection->getUsername(),
            $this->connection->getPassword(),
        );

        $response = $this->connection->call('timezone_get', $params);

        return $response;
    }

    /**
     * @param bool $extended
     * @return mixed
     */
    public function getAbout($extended = false)
    {
        $this->connection->setService($this->service);

        $response = $this->connection->call('about', array($extended));

        return $response;
    }

    /**
     * @return mixed
     */
    public function getVersion()
    {
        $this->connection->setService($this->service);

        $response = $this->connection->call('version');

        return $response;
    }
}

