<?php

namespace Academe\OpenErpApi\App;

use InvalidArgumentException;

/**
 * The result of loading a single record to OpenERP.
 * Provides easy access to the result, messages, further
 * details etc.
 * It may be useful to be able to handle the results of loading
 * multiple records at some point. For now the assumption is
 * one record is loaded at a time, and the result processed.
 */

class LoaderRecordResult
{
    // The result nested array returned by the API load method.
    protected $result_source;

    // The OpenERP internal ID of the record loaded (created or updated).
    protected $record_id = false;

    /**
     * Set the result array from loading a single record.
     */
    public function setResult($result)
    {
        // If this appears to be the result of loading more than
        // one record (more than one ID present), then raise an exception.
        if (is_array($result['ids']) && count($result['ids']) > 1) {
            throw new InvalidArgumentException('Result is for more than one record load.');
        }

        $this->result_source = $result;

        // Save the ID.
        if ($result['ids'] !== false) {
            $this->record_id = $result['ids'][0];
        }

        return $this;
    }

    /**
     * Determine whether the record was successfuly loaded.
     */
    public function isLoaded()
    {
        // The record has loaded if we have its ID.
        return $this->record_id ? true : false;
    }

    /**
     * Get the messages array.
     */
    public function getMessages()
    {
        $messages = array();

        if (empty($this->result_source['messages'])) {
            // No messages present.
            return $messages;
        }

        foreach($this->result_source['messages'] as $message) {
            // TODO: decipher more of the array to provide useful information.
            $messages[] = array(
                'field' => $message['field'],
                'message' => $message['message'],
            );
        }

        return $messages;
    }

    /**
     * Return the ID of the loaded record.
     * false if no record was successfuly loaded.
     */
    public function getId()
    {
        return $this->record_id;
    }

    /**
     * Return the raw result array.
     */
    public function getRawResult()
    {
        return $this->result_source;
    }
}

