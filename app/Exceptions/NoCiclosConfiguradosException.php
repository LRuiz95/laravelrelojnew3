<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Se lanza cuando no hay ciclos escolares configurados en la base de datos
 * y se necesita uno para renderizar una vista de Academia.
 */
class NoCiclosConfiguradosException extends Exception
{
    public function __construct(string $message = 'No hay ciclos escolares configurados')
    {
        parent::__construct($message);
    }
}
