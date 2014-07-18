<?php

namespace Academe\OpenErpApi;

/**
 * Class OpenERP
 * @package Simbigo\OpenERP
 */
class OpenERP
{
    /**
     * @var 
     */
    private $_defaultPath = '';

    /**
     * @var 
     */
    private $_client;

    /**
     * @var 
     */
    private $_uid;

    /**
     * @var 
     */
    private $_version;

    /**
     * @var
     */
    private $_db;

    /**
     * @var
     */
    private $_login;

    /**
     * @var
     */
    private $_password;

    /**
     * The entry points for the static web services.
     * @var
     */
    protected $entry_points = array(
        'common' => '/xmlrpc/common',
        'object' => '/xmlrpc/object',
    );

    /**
     * @param $host
     * @param string $charset
     */
    public function __construct($host, $charset = 'utf-8')
    {
        $urlInfo = parse_url($host);
        $scheme = $urlInfo['scheme'];
        $host = $urlInfo['host'];
        $port = isset($urlInfo['port']) ? $urlInfo['port'] : 80;

        $path = isset($urlInfo['path']) ? $urlInfo['path'] : null;

        // If the path is "xmlrpc" then strip it off - we will be adding it
        // in each entry point path. If the path is not "xmlrpc" then assume
        // OpenERP is install on a a non-root path, so keep it.
        // CHECKME: if the path is "/myinstance/xmlrpc" then we probably need to
        // strip "xmlrpc" from the end.
        // However, _defaultPath is not actually used anywhere, so it's a moot.

        if ($path !== null && trim($path, '/') != 'xmlrpc') {
            $this->_defaultPath = rtrim($path, '/');
        } else {
            $this->_defaultPath = '';
        }

        // TODO: other clients may also work, such as a JSON-RPC client, which may be
        // a little quicker.
        $this->_client = $this->clientInstance($scheme . '://' . $host, $port, $charset);
    }

    /**
     * @return XmlRpcClient
     */
    public function clientInstance($uri, $port, $charset)
    {
        return new XmlRpcClient($uri, $port, $charset);
    }

    /**
     * @return mixed
     */
    public function getLastResponse()
    {
        return $this->getClient()->getLastResponse();
    }

    /**
     * @return mixed
     */
    public function getLastRequest()
    {
        return $this->getClient()->getLastRequest();
    }

    /**
     * @return this
     */
    public function setClient(XmlRpcClient $client)
    {
        $this->_client = $client;

        return $this;
    }

    /**
     * @return XmlRpcClient
     */
    public function getClient()
    {
        return $this->_client;
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
        return $this->_uid;
    }

    /**
     * @return client
     */
    public function setEntryPoint($name)
    {
        if ( ! isset($this->entry_points[$name])) {
            // Entry point not known.
            throw new \Exception(sprintf('Entry point "$name" not known.', $name));
        }

        $client = $this->getClient();
        $client->setPath($this->entry_points[$name]);

        return $client;
    }

    /**
     * @param $db
     * @param $login
     * @param $password
     * @return int
     */
    public function login($db, $login, $password)
    {
        $this->_db = $db;
        $this->_login = $login;
        $this->_password = $password;

        $client = $this->setEntryPoint('common');

        $response = $client->call('login', array($db, $login, $password));
        $this->throwExceptionIfFault($response);

        $uid = (int)$response['params']['param']['value']['int'];
        $this->_uid = $uid;

        return $uid;
    }

    /**
     * @return mixed
     */
    public function version()
    {
        $client = $this->setEntryPoint('common');

        $response = $client->call('version');
        $this->throwExceptionIfFault($response);

        $version = $response['params']['param']['value']['struct']['member'][0]['value']['string'];
        $this->_version = $version;

        return $version;
    }

    /**
     * @param bool $extended
     * @return mixed
     */
    public function about($extended = false)
    {
        $client = $this->setEntryPoint('common');

        $response = $client->call('about', array($extended));
        $this->throwExceptionIfFault($response);

        return $response['params']['param']['value']['string'];
    }

    /**
     * @param null $db
     * @param null $login
     * @param null $password
     * @return mixed
     */
    public function getTimezone($db = null, $login = null, $password = null)
    {
        $client = $this->setEntryPoint('common');

        $params = array($this->_db, $this->_login, $this->_password);

        $response = $client->call('timezone_get', $params);
        $this->throwExceptionIfFault($response);

        return $response['params']['param']['value']['string'];
    }

    /**
     * @param $model
     * @param $data
     * @return int
     */
    public function create($model, $data)
    {
        $client = $this->setEntryPoint('object');

        $params = array($this->_db, $this->getUid(), $this->_password, $model, 'create', $data);

        $response = $client->call('execute', $params);
        $this->throwExceptionIfFault($response);

        return (int)$response['params']['param']['value']['int'];
    }

    /**
     * @param $model
     * @param $data
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function search($model, $data, $offset = 0, $limit = 1000)
    {
        $client = $this->setEntryPoint('object');

        $params = array($this->_db, $this->getUid(), $this->_password, $model, 'search', $data, $offset, $limit);

        $response = $client->call('execute', $params);
        $this->throwExceptionIfFault($response);

        $response = $response['params']['param']['value']['array']['data'];

        if (!isset($response['value'])) {
            return array();
        }

        $ids = array();
        $response = $response['value'];

        foreach ($response as $value) {
            $ids[] = (int)$value['int'];
        }

        return $ids;
    }

    /**
     * @param $model
     * @param $ids
     * @param array $fields
     * @return array
     */
    public function read($model, $ids, $fields = array())
    {
        $client = $this->setEntryPoint('object');

        $params = array($this->_db, $this->getUid(), $this->_password, $model, 'read', $ids, $fields);

        $response = $client->call('execute', $params);
        $this->throwExceptionIfFault($response);

        $response = $response['params']['param']['value']['array']['data'];

        if (!isset($response['value'])) {
            return array();
        }
        $records = array();

        // When only one item is fetched the value of result is a associative array.
        // As a result records will be an array with length 1 with an empty array inside.
        // The following check fixes the issue.

        if (count($ids) === 1) {
            $response = array($response['value']);
        } else {
            $response = $response['value'];
        }

        foreach ($response as $item) {
            $record = array();
            $recordItems = $item['struct']['member'];

            // Convert from ['name'=>'foo','value'=>'bar'] pairs to an associate array of 'foo'=>'bar' elements.
            // Similar treatment may be needed recursively into the record.

            foreach ($recordItems as $recordItem) {
                $key = $recordItem['name'];
                $value = current($recordItem['value']);
                $record[$key] = $value;
            }

            // TODO: it can be helpful to optionally index the array by the row IDs or codes.
            $records[] = $record;
        }
        return $records;
    }

    /**
     * @param $model
     * @param $ids
     * @param $fields
     * @return bool|mixed|\SimpleXMLElement|string
     */
    public function write($model, $ids, $fields)
    {
        $client = $this->setEntryPoint('object');

        $params = array($this->_db, $this->getUid(), $this->_password, $model, 'write', $ids, $fields);

        $response = $client->call('execute', $params);
        $this->throwExceptionIfFault($response);

        $response = (bool)$response['params']['param']['value']['boolean'];

        return $response;
    }

    /**
     * @param $model
     * @param $ids
     * @return bool|mixed|\SimpleXMLElement|string
     */
    public function unlink($model, $ids)
    {
        $client = $this->setEntryPoint('object');

        $params = array($this->_db, $this->getUid(), $this->_password, $model, 'write', $ids);

        $response = $client->call('execute', $params);
        $this->throwExceptionIfFault($response);

        $response = (bool)$response['params']['param']['value']['boolean'];

        return $response;
    }

    /**
     * @param $response
     * @throws \Exception
     */
    protected function throwExceptionIfFault($response)
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

            throw new \Exception($faultString, $faultCode);
        }
    }
}
