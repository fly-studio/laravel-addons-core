<?php

namespace Addons\Core\Exceptions;

use RuntimeException;

class PermissionException extends RuntimeException {
    public function __construct($message = "", $code = 0, $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}