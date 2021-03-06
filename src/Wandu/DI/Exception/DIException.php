<?php
namespace Wandu\DI\Exception;

use RuntimeException;

/**
 * @deprecated use RuntimeException directly.
 */
class DIException extends RuntimeException
{
    /** @var string */
    protected $class;

    /**
     * @param string $class
     */
    public function __construct($class)
    {
        $this->class = $class;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }
}
