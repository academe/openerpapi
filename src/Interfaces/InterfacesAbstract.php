<?php

namespace Academe\OpenErpApi\Interfaces;

use Academe\OpenErpApi;
use Pimple\Container;

/**
 * 
 * @package 
 */
abstract class InterfacesAbstract implements InterfacesInterface
{
    /**
     * The OpenERP API connection.
     */
    protected $connection;

    /**
     * The Pimple DIC.
     */
    protected $container;

    /**
     * Need a connection to talk to the OpenERP API.
     */
    public function __construct(OpenErpApi\ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Register the DIC container
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Log in if not already logged in.
     */
    public function checkLogin()
    {
        if ($this->connection->isLoggedIn()) {
            // Already logged in.
            return $this->connection->getUid();
        }

        $common = $this->container['interface_common'];
        return $common->login();
    }
}
