<?php

namespace ObjectivePHP\Gateway\Exception;

class GatewayException extends \Exception
{
    const ENTITY_NOT_FOUND = 1;
    const INVALID_ENTITY = 2;
}
