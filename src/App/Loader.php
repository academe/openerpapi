<?php

namespace Academe\OpenErpApi\App;

use Academe\OpenErpApi\Interfaces\OdooObject;
use Academe\OpenErpApi\OpenErp;

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

class Loader extends OdooObject
{
    /**
     * A general low-level data load.
     * Will attempt to load all supplied records at once.
     * param $model string The model name e.g. res.partner
     * param $keys array An array of key (field) names.
     * param $records array An array of records. Each record is an array of field values.
     */
    public function loadRaw($model, $keys, $records)
    {
        return $this->load($model, $keys, $records);
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
            $keys = array_keys($keys_or_record);
        } else {
            $keys = $keys_or_record;
        }

        try {
            $response = $this->load($model, $keys, [$record]);
        } catch (\Exception $e) {
            // Create a fake response, including the exception raised as a
            // "_internal_" field.
            // This will often be a Python error, uncaught on the OpenERP server and
            // raised as an API exception in the Connection. It is needed for debugging,
            // but the _underscores_ should differentiate it from any normal model object
            // fields.

            $response = [
                'messages' => [
                    [
                        'field' => '_internal_',
                        'message' => $e->getMessage()
                    ]
                ],
                'ids' => false
            ];
            //throw $e; // Throw the exception for dev, so we can see what type of exceptions there are.
        }

        // Return the result.
        // TODO: put this in the DIC for convenience.
        $record_result = new LoaderRecordResult();
        return $record_result->setResult($response);
    }
}

