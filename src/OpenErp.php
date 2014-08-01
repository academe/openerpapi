<?php

namespace Academe\OpenErpApi;

use Pimple\Container;

/**
 * Class OpenERP
 * @package 
 * @todo This will probably just end up the object factory/locator.
 */
class OpenErp
{
    /**
     * DI container - Pimple 3.
     * This holds the routes and parameters to everthing within this package.
     */

    protected $container;

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Do we want to be able to pass this in?
        $this->container = new Container();

        // The client service name and some default parameters.
        $this->container['client_class'] = __NAMESPACE__ . '\\XmlRpcClient';
        $this->container['client_charset'] = 'utf-8';
        $this->container['client_port'] = '8069';

        // The shared client service.
        $this->container['client'] = function ($c) {
            return new $c['client_class']($c['client_base_uri'], $c['client_port'], $c['client_charset']);
        };

        // The shared connection service name.
        $this->container['connection_class'] = __NAMESPACE__ . '\\Connection';

        // The shared connection service.
        $this->container['connection'] = function ($c) {
            return new $c['connection_class']($c['client']);
        };

        // The various interface services.

        $this->container['interface_common_class'] = __NAMESPACE__ . '\\Interfaces\\Common';
        $this->container['interface_object_class'] = __NAMESPACE__ . '\\Interfaces\\Object';
        $this->container['interface_db_class'] = __NAMESPACE__ . '\\Interfaces\\Db';
        $this->container['interface_report_class'] = __NAMESPACE__ . '\\Interfaces\\Report';

        // TODO: can these be gerated in a loop?

        $this->container['interface_common'] = function ($c) {
            return new $c['interface_common_class']($c['connection']);
        };

        $this->container['interface_object'] = function ($c) {
            return new $c['interface_object_class']($c['connection']);
        };

        $this->container['interface_db'] = function ($c) {
            return new $c['interface_db_class']($c['connection']);
        };

        $this->container['interface_report'] = function ($c) {
            return new $c['interface_report_class']($c['connection']);
        };
    }

    /**
     * Set the client base URI.
     */
    public function setClientUri($service_base_uri)
    {
        $this->container['client_base_uri'] = $service_base_uri;

        return $this;
    }

    /**
     * Set the client port.
     */
    public function setClientPort($port)
    {
        $this->container['client_port'] = $port;

        return $this;
    }

    /**
     * Set the client characterset.
     */
    public function setClientCharset($charset)
    {
        $this->container['client_charset'] = $charset;

        return $this;
    }

    /**
     * Return the client service.
     * Parameters for the client can be set here.
     */
    public function getClient($service_base_uri = null, $port = null, $charset = null)
    {
        if (isset($service_base_uri)) $this->setClientUri($service_base_uri);
        if (isset($port)) $this->setClientPort($port);
        if (isset($charset)) $this->setClientCharset($charset);

        return $this->container['client'];
    }

    /**
     * Return the connection service.
     */
    public function getConnection()
    {
        return $this->container['connection'];
    }

    /**
     * Return an interface service.
     */
    public function getInterface($name)
    {
        // TODO: pass the DIC into the interface, so it has access to common/login if it needs it.

        return $this
            ->container['interface_' . strtolower($name)]
            ->setContainer($this->container);
    }

    /**
     * Set the connection credentials.
     */
    public function setCredentials($database, $username, $password)
    {
        // The credentials are set directly on the connection, which is
        // instantiated if necessary.

        $this->getConnection()->setCredentials($database, $username, $password);

        return $this;
    }

    /**
     * Return the DI container for extending.
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Return a new instance of a model.
     */
    public function modelInstance($model_name)
    {
    }
}

