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
     * It might be better to pass the DIC in instead.
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
     * Get the DIC container
     */
    public function getContainer(Container $container)
    {
        return $this->container;
    }

    /**
     * Check if logged in.
     */
    public function isLoggedIn()
    {
        return $this->connection->isLoggedIn();
    }

    /**
     * Log in if not already logged in.
     * This is called before any API method is called, as a kind of
     * lazy login approach.
     */
    public function checkLogin()
    {
        if ($this->isLoggedIn()) {
            // Already logged in.
            return $this->connection->getUid();
        }

        // Not logged in - do so now.
        $common = $this->container['interface_common'];
        return $common->login();
    }
}
