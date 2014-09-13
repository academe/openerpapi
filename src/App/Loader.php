<?php

namespace Academe\OpenErpApi\App;

use Opwall\OpenErp\Api as ErpApi;

/**
 * Used as a helper for loading CSV data into models.
 * Some useful features: handle API exceptions; better access to the highly-
 * structured API errors; load multiple records and handle multiple load results
 * (possibly supporting callbacks).
 * The load method on a model will load all records or none.
 * The return value is [messages => [], ids => array[...]]
 * If a load fails, ids will be false, not [].
 * On a successful load, messages is [], not false.
 * Multiple messages will be returned, if relevant (so each row can result in more
 * then one error).
 * message structure:
 * [field => field_name, record => record_number, message => text, moreinfo => mixed, type => error]
 * Other types may exist (e.g. warning). Other fields exist, but are more relevant to 
 * the GUI CSV import process.
 * moreinfo can range from a string message to complex nested arrays.
 */

class Loader extends Interfaces\Object
{
    /**
     * A general low-level data load.
     * Will attempt to load all supplied records at once.
     * param $model string The model name e.g. res.partner
     * param $keys array An array of key (field) names.
     * param $records array An array of records. Each record is an array of field values.
     */
    public function load($model, $keys, $records)
    {
        $object = ErpApi::getInterface('object');

        return $object->load($model, $keys, $records);
    }

    /**
     * Load one record.
     * Catch exceptions and any errors.
     */
    public function loadOne($model, $keys_or_record, $record = null)
    {
        // If the record is passed in as key/value elements, then split them
        // up into separate keys and values.

        if ( ! isset($record)) {
            $record = array_values($keys_or_record);
            $keys_or_record = array_keys($keys_or_record);
        }

        try {
            $response = $this->load($model, $keys, [$record]);
        } catch (\Exception $e) {
            // Create a fake response.
            $response = [];
            throw $e;
        }

        // TODO: decide what to return.
        // ...
    }
}

