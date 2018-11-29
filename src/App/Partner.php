<?php

namespace Academe\OpenErpApi\App;

use Academe\OpenErpApi\Interfaces\OdooObject;
use Config; // <-- Laravel thing, remove it!

/**
 * Business processes concerning partners (individuals and organisations) information.
 * @package 
 * @todo Think about how a business object could extend or use multiple interfaces.
 * Do we actually inject a connection and create interfaces when needed? Then we will
 * need a container or locator to avoid creating multiple instances. Actuall, just by
 * extending, we do have access to the connection and can reuse that to launch new
 * interfaces.
 */
class Partner extends OdooObject
{
    /**
     * Get the list of titles (salutations) that can be assigned to individuals.
     */
    public function getTitleList()
    {
        return $this->getAllExternal('res.partner.title');
    }

    /**
     * Get all invoices for a partner.
     * We probably need a more consistent way of specifying fields and search criteria
     * across similar methods.
     * Set parnter_id = null to get all invoices the current user can see.
     * $state is 'open' or 'paid'.
     * $direction is 'out' (generated here) or 'in' (from supplier)
     * $type is 'invoice' or 'refund'
     */
    public function getInvoices($partner_id, $state = null, $direction = null, $type = null)
    {
        $model = 'account.invoice';

        $context = array();

        $invoice_type = '{direction}_{type}';

        if (! isset($direction) || ($direction != 'in' && $direction != 'out')) {
            $direction = '%';
        }
        $invoice_type = str_replace('{direction}', $direction, $invoice_type);

        if ( ! isset($type) || ($type != 'invoice' && $type != 'refund')) {
            $type = '%';
        }
        $invoice_type = str_replace('{type}', $type, $invoice_type);

        if (isset($partner_id)) $context[] = array('partner_id', '=', (int)$partner_id);

        if (isset($state)) $context[] = array('state', '=', strtolower($state));

        // type is:
        // 'out_invoice' (Invoice), 'out_refund' (Refund),
        // 'in_invoice' (Supplier Invoice), 'in_refund' (Supplier Refund).
        $context[] = array('type', 'like', $invoice_type);

        // Get all the partner invoice IDs.
        $ids = $this->search($model, $context);

        // Now get the invoices.
        if (!empty($ids)) {
            $invoices = $this->read($model, $ids);
        } else {
            $invoices = array();
        }

        return $invoices;
    }

    /**
     * Get all invoices for a partner.
     * We probably need a more consistent way of specifying fields and search criteria
     * across similar methods.
     * Set parnter_id = null to get all invoices the current user can see.
     */
    public function getReceivablesPayables($partner_id = null)
    {
        $model = 'account.move.line';

        $context = array();

        if (isset($partner_id)) {
            $context[] = array('partner_id', '=', (int)$partner_id);
            $context[] = array('account_id.reconcile', '=', false);
        }

        // Get all the partner account movements.
        $ids = $this->search($model, $context);

        // Now get the records.
        if (!empty($ids)) {
            $journal_lines = $this->read($model, $ids);
        } else {
            $journal_lines = array();
        }

        return $journal_lines;
    }

    /**
     * TODO: Move this out, as it is project-specific.
     * Return the ERP external ID that a partner (customer) would have,
     * given the CRM contact's or account's number.
     * $number is the contact or account number
     * $type is "account" or "contact".
     *
     * @deprecated
     */
    public function externalId($number, $type = 'contact')
    {
        switch (strtolower($type)) {
            case "account":
                $template = Config::get('opwall.openerp.account_id_template');
                break;

            case "contact":
                $template = Config::get('opwall.openerp.contact_id_template');
                break;

            default:
                throw new \Exception(sprintf("Account must be 'contact' or 'account'; %s supplied", $type));
                break;
        }

        return str_replace('{number}', (string)$number, $template);
    }

    /**
     * Fetch the internal ID of a partner, given their external ID.
     */
    public function getInternalId($external_id)
    {
        // Get the partner by external ID. We only want the ID field.
        // This actually fetches the partner, given its ID, in order to get its ID. There is
        // a note in that method about splitting it into two stages.

        $object = \App\Opwall\OpenErp\Api::getInterface('object');
        $item = $object->readExternal('res.partner', $external_id, array('id'));

        if (isset($item[0]['id'])) {
            return $item[0]['id'];
        } else {
            return null;
        }
    }
}
