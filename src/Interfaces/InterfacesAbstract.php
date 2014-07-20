<?php

namespace Academe\OpenErpApi\Interfaces;

use Academe\OpenErpApi;

/**
 * 
 * @package 
 */
abstract class InterfacesAbstract
{
    /**
     * The OpenERP API connection.
     */
    protected $connection;

    /**
     * Need a connection to talk to the OpenERP API.
     */
    public function __construct(OpenErpApi\ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

}
