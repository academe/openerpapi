<?php

namespace Academe\OpenErpApi\Interfaces;

use Academe\OpenErpApi;

/**
 * Access to the "object" interface.
 * The interface methods are: 'execute', 'execute_kw' and 'exec_workflow'.
 * I *think* that execute and execute_kw are the same through the API. Within the ERP, execute takes
 * references to its parameters, while execute_kw takes a copy of its parameters, so it may have
 * something to do with the way parameters are passed back out by reference.
 * The first three parameters are always: database, uid, password
 * @package Simbigo\OpenERP
 *
 * Many of these methods can be found in openerp/osv/orm.py
 * Use the execute() method directly to access methods that are not listed here, or are specific
 * to the model being accessed.
 */
class OdooObject extends InterfacesAbstract
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
     * Offset and limit for fetching all records.
     */
    protected $offset_all = 0;
    protected $limit_all = 0;

    /**
     * Execute a remote method in a model.
     * @param $model string The name of the model.
     * @param $method string The method to call.
     * @param mixed Additional parameters that will be passed to the method as positional parameters.
     */
    public function execute($model, $method) // [, ...]
    {
        // If not logged in, then log in automatically (use the DIC to get access to common/login).
        // Dp this first, before setting the service entry point as the entry point could get changed
        // if logging in.

        $this->checkLogin();

        // Set the object service path.
        $this->connection->setService($this->service);

        // Set the common parameters.
        $params = array(
            $this->connection->getDb(),
            $this->connection->getUid(),
            $this->connection->getPassword(),
            $model,
            $method,
        );

        // Append any additional parameters passed to this method.
        if (func_num_args() > 2) {
            $params = array_merge($params, array_slice(func_get_args(), 2));
        }

        // This is a good point to put a debug call in to capture what is being
        // sent to the server. A logging object can be added to the DIC, with perhaps
        // a dummy object for production. A PSR-3 Psr\Log\LoggerInterface would be a
        // nice move.

        // Call the remote system.
        $response = $this->connection->call('execute', $params);

        return $response;
    }

    /**
     * Magic method for execute().
     * Call $object->method($model [, params ...])
     */
    public function __call($method, $params)
    {
        // Take the model off the front of the params.
        $model = array_shift($params);

        // Insert the method as the second element.
        array_unshift($params, $method);

        // Put the model back on.
        array_unshift($params, $model);

        // Invoke the execute method.
        return call_user_func_array([$this, 'execute'], $params);
    }

    /**
     * @param $model
     * @param $data
     * @return int
     */
    public function create($model, $data)
    {
        $response = $this->execute($model, 'create', $data);

        return $response;
    }

    /**
     * @param $model
     * @param $data array of terms
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function search($model, $data, $offset = 0, $limit = 1000)
    {
        $response = $this->execute($model, 'search', $data, $offset, $limit);

        return $response;
    }

    /**
     * @param $model
     * @param $ids database IDs
     * @param array $fields
     * @return array
     */
    public function read($model, $ids, $fields = array())
    {
        // In case a single ID has been passed in.
        if ( ! is_array($ids)) {
            $ids = array($ids);
        }

        $response = $this->execute($model, 'read', $ids, $fields);

        // TODO: We might want to re-index the records by 'id' to be more useful.

        return $response;
    }

    /**
     * Read by external IDs (rather than internal IDs).
     * We go external_ids->ir_ids->rec_ids->model records
     * @param $model
     * @param $ids database IDs
     * @param array $fields
     * @return array
     * @todo Split this up: (1) fetch the external_id/model_db_id mapping; then (2) fetch the resoruce models.
     */
    public function readExternal($model, $ids, $fields = array())
    {
        if ( ! is_array($ids)) {
            $ids = array($ids);
        }

        // Get the basic criteria.
        // The localisation will be set to the current user's localisation.

        $ir_criteria = array(
            array('model', '=', $model),
        );

        // To search, we need to split the complete names into modules and names.
        // There may be a mix of different modules, and that needs some clever
        // handling later.

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
        $ir_ids = $this->search($this->ir_model_data, $ir_criteria, $this->offset_all, $this->limit_all);

        // Get the list of source model records from the IR model data.
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
        $response = $this->execute($model, 'write', $ids, $fields);

        return $response;
    }

    /**
     * @param $model
     * @param $ids
     * @return bool|mixed|\SimpleXMLElement|string
     */
    public function unlink($model, $ids)
    {
        $response = $this->execute($model, 'unlink', $ids);

        return $response;
    }

    /**
     * Get all records, from a model, that have external IDs.
     * This is most useful on lookup lists such as countries, states and partner titles.
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
    public function getAllExternal($model_name, $res_additional_fields = array())
    {
        // Get the basic criteria.
        // The localisation will be set to the current user's localisation.

        $ir_criteria = array(
            array('model', '=', $model_name),
        );

        $ir_ids = $this->search($this->ir_model_data, $ir_criteria, $this->offset_all, $this->limit_all);

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
        $ir_records = $this->read($this->ir_model_data, $ir_ids, $ir_field_list);

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

    /**
     * Split an external name into a module and [short] name.
     * @return array array(model_name, database_id)
     */
    public function splitExternalId($external_id)
    {
        $id_parts = explode('.', $external_id, 2);

        if (count($id_parts) == 2) {
            // Two parts.
            list($module, $name) = $id_parts;
        } else {
            // One part.
            $module = '';
            $name = $id;
        }

        return array($module, $name);
    }

    /**
     * Get a list of database IDs based on external IDs from a single model.
     * Unrecognised external IDs will be silently ignored.
     * @return array Array of (external_id => database_id) elements.
     * @todo a version to return the split module/name would be handy.
     */
    public function getObjectReferences($model, $external_ids)
    {
        $ir_criteria = array(
            array('model', '=', $model),
        );

        if ( ! is_array($external_ids)) {
            $external_ids = array($external_ids);
        }

        // To search, we need to split the complete names into modules and names.

        $modules = array();
        foreach($external_ids as $id) {
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
        $ir_ids = $this->search($this->ir_model_data, $ir_criteria, $this->offset_all, $this->limit_all);

        // Get the list of records from the IR model data.
        $ir_records = $this->read($this->ir_model_data, $ir_ids, array('res_id', 'complete_name'));

        // Collect together the complete mapping.
        $res_ids = array();
        foreach($ir_records as $ir_record) {
            $res_ids[$ir_record['complete_name']] = $ir_record['res_id'];
        }

        return $res_ids;
    }

    /**
     * Get the model and database ID for a record identified by an external ID.
     * @todo Catch exception "No such external ID currently defined in the system".
     * There is also a get_object() method that may provide more detail, saving a second call to get the record.
     * TODO: rename to getExternalId()
     */
    public function getObjectReference($external_id_or_module, $xml_id = null)
    {
        $model_name = 'ir.model.data';

        if ( ! isset($xml_id)) {
            list($module, $xml_id) = $this->splitExternalId($external_id_or_module);
        } else {
            $module = $external_id_or_module;
        }

        $response = $this->execute($model_name, 'get_object_reference', $module, $xml_id);

        // Returns array($model_name, $database_id)

        return $response;
    }

    /**
     * Fetch a list of lookup values for a field in a model.
     * Generally used for auto-complete functions.
     */
    public function distinctFieldGet($model, $field, $value, $args = null, $offset = 0, $limit = null)
    {
        $response = $this->execute($model, 'distinct_field_get', $field, $value, $args, $offset, $limit);

        return $response;
    }

    /**
     * Test to see if a list of records exist.
     * All records, by database ID, that do exist, will be returned in an array.
     * Those that do not exist for the model, will not be returned.
     */
    public function exists($model, $ids)
    {
        if ( ! is_array($ids)) {
            $ids = array($ids);
        }

        $response = $this->execute($model, 'exists', $ids);

        return $response;
    }
}

