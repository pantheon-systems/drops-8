<?php

namespace Consolidation\Filter\Cli;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\Filter\LogicalOpFactory;
use Consolidation\Filter\FilterOutputData;
use Symfony\Component\Yaml\Yaml;

class OpCommands extends \Robo\Tasks
{
    /**
     * Test the expression parser
     *
     * @command parse
     * @return array
     */
    public function parse($expr, $options = ['format' => 'yaml', 'dump' => false])
    {
        $factory = LogicalOpFactory::get();
        $op = $factory->evaluate($expr);

        $result = (string)$op;

        if ($options['dump']) {
            $result = var_export($op, true) . "\n$result";
        }

        return $result;
    }

    /**
     * Convert a command from one format to another, potentially with filtering.
     *
     * @command edit
     * @aliases ed
     * @filter-default-field color
     * @filter-output
     * @return array
     */
    public function edit($data, $options = ['format' => 'yaml', 'in' => 'auto'])
    {
        return $this->read($data, $options['in']);
    }

    /**
     * Read the data provided to this command.
     */
    protected function read($data, $in)
    {
        // If our input spec is '-' then read from stdin
        if ($data == '-') {
            $data = 'php://stdin';
        }
        // If our input spec is a file or a url then read it. Otherwise
        // we'll presume the data was provided directly on the command line.
        if (file_exists($data) || preg_match('#^[a-z]*://#', $data)) {
            $data = file_get_contents($data);
        }

        return $this->parseData($data, $in);
    }

    /**
     * Convert our provided input data to a php array.
     */
    protected function parseData($data, $in)
    {
        $in = $this->inferInputFormat($data, $in);

        switch ($in) {
            case 'json':
                return json_decode($data, true);

            case 'yaml':
            default:
                return (array) Yaml::parse($data);
        }
    }

    /**
     * If the data type is 'auto', then try to infer what data type
     * we should parse as based on the contents of the data.
     */
    protected function inferInputFormat($data, $in)
    {
        // If the user explicitly set the data type then use that
        if ($in != 'auto') {
            return $in;
        }

        // If data begins with '{' then presume it is json
        if ($data[0] == '{') {
            return 'json';
        }

        // We don't know what the data type should be.
        return $in;
    }
}
