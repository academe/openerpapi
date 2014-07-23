<?php

namespace Academe\OpenErpApi\App;

use Academe\OpenErpApi\Interfaces;

/**
 * Business processes concerning address information.
 * @package 
 * @todo Think about how a business object could extend or use multiple interfaces.
 * Do we actually inject a connection and create interfaces when needed? Then we will
 * need a container or locator to avoid creating multiple instances. Actuall, just by
 * extending, we do have access to the connection and can reuse that to launch new
 * interfaces.
 */
class Address extends Interfaces\Object
{
    /**
     * Get a complete list of states.
     */
    public function getStateList()
    {
        // The returned country_id contains the country name and its database ID (not external ID).

        return $this->getAllExternal('res.country.state', array('code', 'country_id'));
    }

    /**
     * Get a complete list of countries.
     */
    public function getCountryList()
    {
        return $this->getAllExternal('res.country', array('code'));
    }
}
