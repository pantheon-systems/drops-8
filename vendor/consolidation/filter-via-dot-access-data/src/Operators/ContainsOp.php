<?php
namespace Consolidation\Filter\Operators;

use Consolidation\Filter\OperatorInterface;
use Dflydev\DotAccessData\Data;

/**
 * Test for equality
 */
class ContainsOp implements OperatorInterface
{
    protected $key;
    protected $comparitor;

    public function __construct($key, $comparitor)
    {
        $this->key = $key;
        $this->comparitor = $comparitor;
    }

    /**
     * Test the provided value to see if it matches our criteria.
     *
     * @param mixed $value
     * @return bool
     */
    public function test(Data $row)
    {
        $value = $row->get($this->key);
        if (is_array($value)) {
            return in_array($this->comparitor, $value);
        }
        return stripos($value, $this->comparitor) !== false;
    }

    /**
     * Return a string representation of this operator
     */
    public function __toString()
    {
        return "{$this->key}*={$this->comparitor}";
    }
}
