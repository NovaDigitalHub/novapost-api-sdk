<?php

declare(strict_types=1);

namespace NovaDigital\NovaPost\Exception;

use Exception;

/**
 * Exception thrown when a JWT token has expired and needs to be refreshed.
 */
class TokenExpiredException extends Exception
{
}
