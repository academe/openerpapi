<?php

namespace Academe\OpenErpApi\App;

use Academe\OpenErpApi\Interfaces\OdooObject;

/**
 * Business processes concerning partners (individuals and organisations) information.
 * @package 
 * @todo Think about how a business object could extend or use multiple interfaces.
 */
class SaleOrder extends OdooObject
{
    protected $model = 'sale.order';

    public function fetch()
    {
        $context = [];

        //$context[] = ['booking_number', '=', '22675'];
        $context[] = ['booking_number', '=', '12345'];

        //$ids = $this->search($this->model, $context);
        $ids = $this->search('account.invoice', $context);

        if (! empty($ids)) {
            $saleOrders = $this->read($this->model, $ids);
        } else {
            $saleOrders = [];
        }

        return $saleOrders;
    }
}
