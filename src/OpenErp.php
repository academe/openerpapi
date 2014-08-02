<?php

namespace Academe\OpenErpApi;

use Pimple\Container;

/**
 * Class OpenERP
 * @package 
 * @todo The lazy-loading parameters - are they just a silly idea here? They only work when
 * the parameters are set before loading any objects.
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
        $this->container['client_base_uri'] = '';
        $this->container['client_class'] = __NAMESPACE__ . '\\XmlRpcClient';
        $this->container['client_charset'] = 'utf-8';
        $this->container['client_port'] = '8069';

        // The shared client service.
        $this->container['client'] = function ($c) {
            return new $c['client_class']($c['client_base_uri'], $c['client_port'], $c['client_charset']);
        };

        // The shared connection service name and parameters.
        $this->container['connection_class'] = __NAMESPACE__ . '\\Connection';
        $this->container['connection_database'] = null;
        $this->container['connection_username'] = null;
        $this->container['connection_password'] = null;

        // The shared connection service.
        $this->container['connection'] = function ($c) {
            $connection = new $c['connection_class']($c['client']);
            $connection->setCredentials($c['connection_database'], $c['connection_username'], $c['connection_password']);

            return $connection;
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
     * Set the client parameters.
     */
    public function setClientParams($service_base_uri = null, $port = null, $charset = null)
    {
        if (isset($service_base_uri)) $this->container['client_base_uri'] = $service_base_uri;
        if (isset($port)) $this->container['client_port'] = $port;
        if (isset($charset)) $this->container['client_charset'] = $charset;
    }

    /**
     * Return the client service.
     * Parameters for the client can be set here.
     */
    public function getClient($service_base_uri = null, $port = null, $charset = null)
    {
        // Set lazy-loading parameters.
        $this->setClientParams($service_base_uri, $port, $charset);

        // Get the client from the DIC.
        $client = $this->container['client'];

        // Set the container parameters directly in case it was already instantiated
        // and the parameters have changed.
        // TODO: some more thought needed here, to prevent the same thing being done
        // multiple times.
        $client->setParams($service_base_uri, $port, $charset);

        return $client;
    }

    /**
     * Return the connection service.
     * Allow login credentials to be set of changed here.
     * All null credentials will be skipped at all levels, so null will never
     * overwite a credential already set.
     */
    public function getConnection($database = null, $username = null, $password = null)
    {
        // Set any lazy-loading credentials.
        // Even if already instantiated, thet are handy for reference.
        $this->setCredentials($database, $username, $password);

        // Get the connection object from the DIC.
        $connection = $this->container['connection'];

        // Overide any credentials in the DIC connection object.
        $connection->setCredentials($database, $username, $password);

        return $connection;
    }

    /**
     * Return an interface service.
     * Services are: "common", "object", "db", "report".
     */
    public function getInterface($name)
    {
        // Pass the DIC into the interface, so it has access to common/login if it needs it.
        $interface_name = 'interface_' . strtolower($name);

        return $this
            ->container[$interface_name]
            ->setContainer($this->container);
    }

    /**
     * Set the connection credentials (lazy-loaded parameters).
     * @fixme This does not affect the connection if already instantiated.
     * Maybe we just make this method protected?
     */
    public function setCredentials($database, $username, $password)
    {
        // The credentials are set in the DIC for lazy-loading.

        if ( isset($database)) $this->container['connection_database'] = $database;
        if ( isset($username)) $this->container['connection_username'] = $username;
        if ( isset($password)) $this->container['connection_password'] = $password;

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

