<?php

namespace Consolidation\Filter;

use PHPUnit\Framework\TestCase;
use Dflydev\DotAccessData\Data;

class FilterOutputDataTest extends TestCase
{
    protected $factory;
    protected $filter;

    public function setUp()
    {
        $this->factory = LogicalOpFactory::get();
        $this->filter = new FilterOutputData();
    }

    /**
     * Data provider for testFilterData.
     *
     * Return an array of arrays, each of which contains the parameter
     * values to be used in one invocation of the testExample test function.
     */
    public function testFilterDataValues()
    {
        $source = [
            'a' => ['color' => 'red', 'shape' => 'round', ],
            'b' => ['color' => 'blue', 'shape' => 'square', ],
            'c' => ['color' => 'green', 'shape' => 'triangular', ],
        ];

        return [
            [$source, 'color=red', 'a', ],
            [$source, 'color=blue||shape=triangular', 'b,c', ],
            [$source, 'color=red&&shape=square', '', ],
        ];
    }

    /**
     * Test our example class. Each time this function is called, it will
     * be passed data from the data provider function idendified by the
     * dataProvider annotation.
     *
     * @dataProvider testFilterDataValues
     */
    public function testFilterData($source, $expr, $expected)
    {
        $op = $this->factory->evaluate($expr);
        $actual = $this->filter->filter($source, $op);
        $this->assertEquals($expected, implode(',', array_keys($actual)));
    }
}
