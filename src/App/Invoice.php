<?php

namespace Academe\OpenErpApi\App;

use Academe\OpenErpApi\Interfaces\OdooObject;
use Academe\OpenErpApi\Models\Invoice as InvoiceModel;

/**
 * Business processes concerning partners (individuals and organisations) information.
 * @package 
 * @todo Think about how a business object could extend or use multiple interfaces.
 */
class Invoice extends OdooObject
{
    protected $model = 'account.invoice';

    /**
     * Fetch multiple invoices
     */
    public function fetchContext(array $context, $offset = 0, $limit = 1000)
    {
        //"type" => "out_invoice" (out_% for both "out_invoice" and "out_refund")

        $ids = $this->search($this->model, $context, $offset, $limit);

        //dump($ids);

        if (! empty($ids)) {
            $invoices = $this->read($this->model, $ids);
        } else {
            $invoices = [];
        }

        // "amount_total" => 1225.0 <-- original full amount
        // "date_invoice" => "2018-06-22"
        // "date_due" => "2018-06-22"
        // "booking_number" => "12345"
        // "is_package" => false
        // "state" => "open"
        // "residual" => 735.0 <-- seems to be what is left
        // "internal_number" => "UK/2018/2515"
        // "payment_ids" => array:3 [
        //    0 => 146480
        //    1 => 146479
        //    2 => 146478
        //  ] <-- payments already made

        // TODO: instantiate the list of invoice objects.

        $result = [];

        foreach ($invoices as $invoice) {
            $result[] = new InvoiceModel($invoice);
        }

        return $result;
    }
}
