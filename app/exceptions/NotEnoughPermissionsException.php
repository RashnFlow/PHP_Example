<?php


namespace exceptions;

use Exception;

class NotEnoughPermissionsException extends Exception
{
    const MESSAGE = 'У вас недостаточно прав. Приобретите канал';

    public function __construct()
    {
        parent::__construct(self::MESSAGE, ACCESS_DENIED);
    }
}