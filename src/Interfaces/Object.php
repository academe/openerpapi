<?php

namespace Academe\OpenErpApi\Interfaces;

use Academe\OpenErpApi;

/**
 * Access to the "common" interface.
 * @package Simbigo\OpenERP
 */
class Object extends InterfacesAbstract
{
    /**
     * The service name
     */
    protected $service = 'object';

    /**
     * @param $model
     * @param $data
     * @return int
     */
    public function create($model, $data)
    {
        $client = $this->connection->getClient();
        $client->setPath($this->connection->getEntryPoint($this->service));

        $params = array(
            $this->connection->getDb(),
            $this->connection->getUid(),
            $this->connection->getPassword(),
            $model,
            'create',
            $data
        );

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
        $client = $this->connection->getClient();
        $client->setPath($this->connection->getEntryPoint($this->service));

        $params = array(
            $this->connection->getDb(),
            $this->connection->getUid(),
            $this->connection->getPassword(),
            $model,
            'search',
            $data,
            $offset,
            $limit
        );

        $response = $client->call('execute', $params);
        $this->connection->throwExceptionIfFault($response);

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
        $client = $this->connection->getClient();
        $client->setPath($this->connection->getEntryPoint($this->service));

        $params = array(
            $this->connection->getDb(),
            $this->connection->getUid(),
            $this->connection->getPassword(),
            $model,
            'read',
            $ids,
            $fields
        );

        $response = $client->call('execute', $params);
        $this->connection->throwExceptionIfFault($response);

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
        $client = $this->connection->getClient();
        $client->setPath($this->connection->getEntryPoint($this->service));

        $params = array(
            $this->connection->getDb(),
            $this->connection->getUid(),
            $this->connection->getPassword(),
            $model,
            'write',
            $ids,
            $fields
        );

        $response = $client->call('execute', $params);
        $this->connection->throwExceptionIfFault($response);

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

        $params = array(
            $this->connection->getDb(),
            $this->connection->getUid(),
            $this->connection->getPassword(),
            $model,
            'write',
            $ids
        );

        $response = $client->call('execute', $params);
        $this->connection->throwExceptionIfFault($response);

        $response = (bool)$response['params']['param']['value']['boolean'];

        return $response;
    }
}

