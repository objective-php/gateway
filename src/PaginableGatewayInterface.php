<?php

namespace ObjectivePHP\Gateway;


interface PaginableGatewayInterface
{
    
    public function paginate($page, $resultsPerPage = null);
    
}
