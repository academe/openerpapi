<?php

namespace Academe\OpenErpApi\App;

use Academe\OpenErpApi\Interfaces;

/**
 * Business processes concerning partners (individuals and organisations) information.
 * @package 
 * @todo Think about how a business object could extend or use multiple interfaces.
 * Do we actually inject a connection and create interfaces when needed? Then we will
 * need a container or locator to avoid creating multiple instances. Actuall, just by
 * extending, we do have access to the connection and can reuse that to launch new
 * interfaces.
 */
class Partner extends Interfaces\Object
{
    /**
     * Get the list of titles (salutations) that can be assigned to individuals.
     */
    public function getTitleList()
    {
        return $this->getAllExternal('res.partner.title');
    }
}

