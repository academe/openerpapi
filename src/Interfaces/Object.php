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
     * The model where the IR (external IDs) is stored.
     */
    protected $ir_model_data = 'ir.model.data';

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

        if ( ! isset($response['value'])) {
            return array();
        }

        $ids = array();
        $response = $response['value'];

        // Another example of the returned structure being slightly different when
        // a single match is returned, compared to multiple matches.
        // I love these traps that leave us scratching our heads for hours. Why a
        // set of one record should be handled differently than a set of two records,
        // I will never know.

        if (count($response) == 1) {
            $ids[] = (int)$response['int'];
        } else {
            foreach ($response as $value) {
                $ids[] = (int)$value['int'];
            }
        }

        return $ids;
    }

    /**
     * @param $model
     * @param $ids database IDs
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
     * Read by external IDs (rather than internal IDs).
     * @param $model
     * @param $ids database IDs
     * @param array $fields
     * @return array
     */
    public function readExternal($model, $ids, $fields = array())
    {
        // Get all records.
        $offset = 0;
        $limit = 0;

        // Get the basic criteria.
        // The localisation will be set to the current user's localisation.

        $ir_criteria = array(
            array('model', '=', $model),
            //array('complete_name', 'in', $ids),
        );

        // To search, we need to split the complete names into modules and names.
        $modules = array();
        foreach($ids as $id) {
            $id_parts = explode('.', $id, 2);
            if (count($id_parts) == 2) {
                // Two parts.
                list($module, $name) = $id_parts;
            } else {
                // One part.
                $module = '';
                $name = $id;
            }

            if ( ! isset($modules[$module])) {
                $modules[$module] = array();
            }

            $modules[$module][] = $name;
        }

        // There could be one or more modules in the criteria list.
        // This question asks if we can search for all modules at once, or need to
        // do separate searches for each and merge the results:
        // https://openerp.my.openerp.com/forum/help-1/question/xml-rpc-api-object-search-mixing-and-and-or-58468
        // It seems we can use polish notation.

        // Polish notation.
        // If there are more than one module, then OR them together; precede them with
        // number-of-modules OR operators minus one.

        //for($i = count($modules); $i > 1; $i--) {
        //    $ir_criteria[] = '|';
        //}
        if (count($modules) > 1) {
            $ir_criteria = array_merge($ir_criteria, array_pad(array(), count($modules) - 1, '|'));
        }

        foreach($modules as $module => $names) {
            // Polish notation: make sure the following two operations are ANDed, before they are ORed.
            $ir_criteria[] = '&';
            $ir_criteria[] = array('module', '=', $module);
            $ir_criteria[] = array('name', 'in', $names);
        }

        // Fetch all the IR IDs.
        $ir_ids = $this->search($this->ir_model_data, $ir_criteria, $offset, $limit);

        // Get the list of source model records from the IR model data - these are the external IDs for the data.
        $ir_records = $this->read($this->ir_model_data, $ir_ids);

        // Get the resource model IDs.
        $res_ids = array();
        foreach($ir_records as $ir_field) {
            $res_ids[] = $ir_field['res_id'];
        }

        // Now do a standard read with the resource IDs we have found.
        // However, we probably need to inject the external IDs back into the results,
        // otherwise we will never know which record is which.

        return $this->read($model, $res_ids, $fields);
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

    /**
     * Get all records from a model that have external IDs.
     * @param $model string The name of the model to fetch from.
     * @param $res_additional_fields array Additional base resource model fields.
     * 
     * @todo param Additional IR model criteria.
     * @todo param Offset for paging..
     * @todo param Limit for paging.
     * @todo Ability to determine what the index is - res_id, id, complete_name or sequence. Default is res_id.
     * @todo If we fetch from the base model first, we can include records without external IDs.
     *
     * Return elements:
     *  display_name The record name/title from the IR data, which may be localised.
     *  name - The record name/title from the underlying model.
     *  res_id - the database ID of the resource from the underlying model.
     *  id - the database ID from the IR model record.
     *  complete_name - the IR external ID, which may be localised.
     */
    public function getAllExternal($model_name, $res_additional_fields = null)
    {
        // Get all records (no paging).
        $offset = 0;
        $limit = 0;

        // Get the basic criteria.
        // The localisation will be set to the current user's localisation.

        $ir_criteria = array(
            array('model', '=', $model_name),
        );

        $ir_ids = $this->search($this->ir_model_data, $ir_criteria, $offset, $limit);

        $ir_field_list = array(
            // The record data from the module.
            'display_name',
            // complete_name is the {module}.{name} or {name} string.
            'complete_name',
            // Database ID
            //'id',
            'res_id',
            // Timestamps of the IR records.
            //'date_init',
            //'date_update',
        );

        // Get the list of records from the IR model data - these are the external IDs for the data.
        $ir_records = $this->read('ir.model.data', $ir_ids, $ir_field_list);

        // Get the base model IDs.
        $res_ids = array();
        foreach($ir_records as $ir_field) {
            $res_ids[] = $ir_field['res_id'];
        }

        $res_field_list = array(
            'id',
            'name',
        );

        if ( ! empty($res_additional_fields)) {
            if ($res_additional_fields == '*' || (is_array($res_additional_fields) && reset($res_additional_fields) == '*')) {
                // Wildcard passed in, so select all columns.
                $res_field_list = array();
            } elseif (is_array($res_additional_fields)) {
                $res_field_list = array_merge($res_field_list, array_values($res_additional_fields));
            }
        }

        // Get the resource records.
        $res_records = $this->read($model_name, $res_ids, $res_field_list);

        // Get a database ID lookup list for the resource records.
        $res_id_lookup = array();
        foreach($res_records as $key => $value) {
            $res_id_lookup[$value['id']] = $key;
        }

        $records = array();

        foreach($ir_records as $ir_record) {
            $res_id = $ir_record['res_id'];

            if (isset($res_id_lookup[$res_id])) {
                $record = $ir_record;
                $record += $res_records[$res_id_lookup[$res_id]];

                $records[$res_id] = $record;
            }
        }

        return $records;
    }
}

