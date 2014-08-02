<?php

namespace Academe\OpenErpApi\Interfaces;

use Pimple\Container;

/**
 * 
 * @package 
 */
interface InterfacesInterface
{
    /**
     * Register the DIC container
     */
    public function setContainer(Container $container);

    /**
     * Log in if not already logged in.
     */
    public function checkLogin();
}

