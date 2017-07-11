<?php

namespace ObjectivePHP\Gateway;


class GatewayException extends \Exception
{

    const INVALID_RESOURCE = 1;
    const SQL_ERROR = 2;
}
