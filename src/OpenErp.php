<?php

namespace Academe\OpenErpApi;

use Pimple\Container;

/**
 * Class OpenERP
 * @package Academe\OpenErpApi
 * @todo The lazy-loading parameters - are they just a silly idea here? They only work when
 * the parameters are set before loading any objects.
 */
class OpenErp
{
    /**
     * DI container - Pimple 3.
     * This holds the routes and parameters to everthing within this package.
     * Use of Pimple internally is a kind of an experiment for me. The idea is
     * to try to make the class structure a little more flexible.
     */

    protected $container;

    /**
     * Some definitions.
     */

    const DEFAULT_CHARSET = 'utf-8';
    const DEFAULT_PORT = '8069';

    const CLIENT_CLASS = 'Academe\\OpenErpApi\\XmlRpcClient';
    const CONNECTION_CLASS = 'Academe\\OpenErpApi\\Connection';

    // Note Interfaces is plural because Interface is a reserved word.
    const INTERFACE_COMMON_CLASS = 'Academe\\OpenErpApi\\Interfaces\\Common';
    const INTERFACE_OBJECT_CLASS = 'Academe\\OpenErpApi\\Interfaces\\Object';
    const INTERFACE_DB_CLASS = 'Academe\\OpenErpApi\\Interfaces\\Db';
    const INTERFACE_REPORT_CLASS = 'Academe\\OpenErpApi\\Interfaces\\Report';

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Do we want to be able to pass this in?
        $this->container = new Container();

        // The client service name and some default parameters.
        $this->container['client_base_uri'] = '';
        $this->container['client_class'] = static::CLIENT_CLASS;
        $this->container['client_charset'] = static::DEFAULT_CHARSET;
        $this->container['client_port'] = static::DEFAULT_PORT;

        // The shared client service.
        $this->container['client'] = function ($c) {
            return new $c['client_class']($c['client_base_uri'], $c['client_port'], $c['client_charset']);
        };

        // The shared connection service name and parameters.
        $this->container['connection_class'] = static::CONNECTION_CLASS;
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

        $this->container['interface_common_class'] = static::INTERFACE_COMMON_CLASS;
        $this->container['interface_object_class'] = static::INTERFACE_OBJECT_CLASS;
        $this->container['interface_db_class'] = static::INTERFACE_DB_CLASS;
        $this->container['interface_report_class'] = static::INTERFACE_REPORT_CLASS;

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

        // Generic App/Model instantiation.

        $this->container['app_model'] = function ($c) {
            return new \Academe\OpenErpApi\App\Partner($c['connection']);
        };
    }

    /**
     * Set the client parameters.
     * Must be set before the client is instantiated.
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

        if (isset($database)) $this->container['connection_database'] = $database;
        if (isset($username)) $this->container['connection_username'] = $username;
        if (isset($password)) $this->container['connection_password'] = $password;

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
     * Return a new instance of a model (actually, more a business model).
     * This method name may be changed to make more sense.
     */
    public function getModelInstance($name)
    {
        //$model_name = 'app_model_' . strtolower($name);
        //return $this->container['app_model'];

        // So far as I am see, Pimple does not support instantiation parameters,
        // so we will create the new class right here.

        $fqcn_name = '\\Academe\\OpenErpApi\\App\\' . $name;

        $class = new $fqcn_name($this->container['connection']);

        // Think this through a bit. Maybe just pass in the container
        // and not the connnection explicitly.
        $class->setContainer($this->container);

        return $class;
    }
}

