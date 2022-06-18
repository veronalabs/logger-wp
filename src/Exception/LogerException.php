<?php
/*
 * This file is part of the LoggerWP package.
 *
 * License: MIT License
 */

namespace LoggerWp\Exception;

use Exception;
use LoggerWp\Logger;
use Throwable;

class LogerException extends Exception
{
    /**
     * @param $message
     * @param $code
     * @param Throwable|null $previous
     */
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        Logger::getInstance()->warning($message, [
            'exception' => $this,
        ]);
    }
}
