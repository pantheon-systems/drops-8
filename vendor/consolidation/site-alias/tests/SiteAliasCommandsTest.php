<?php

namespace Consolidation\SiteAlias;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

class ExampleCommandsTest extends TestCase
{
    /** @var string[] */
    protected $commandClasses;

    /** @var string */
    protected $appName;

    /** @var string */
    protected $appVersion;

    const STATUS_OK = 0;
    const STATUS_ERROR = 1;

    /**
     * Instantiate a new runner
     */
    public function setUp()
    {
        // Store the command classes we are going to test
        $this->commandClasses = [ \Consolidation\SiteAlias\Cli\SiteAliasCommands::class ];

        // Define our invariants for our test
        $this->appName = 'TestFixtureApp';
        $this->appVersion = '1.0.1';
    }

    /**
     * Data provider for testExample.
     *
     * Return an array of arrays, each of which contains the parameter
     * values to be used in one invocation of the testExample test function.
     */
    public function exampleTestCommandParameters()
    {
        return [

            [
                'Add search location: /fixtures/sitealiases/sites', self::STATUS_ERROR,
                'site:list', '/fixtures/sitealiases/sites',
            ],

            [
                'List available site aliases', self::STATUS_OK,
                'list',
            ],

        ];
    }

    /**
     * Test our example class. Each time this function is called, it will
     * be passed data from the data provider function idendified by the
     * dataProvider annotation.
     *
     * @dataProvider exampleTestCommandParameters
     */
    public function testExampleCommands($expectedOutput, $expectedStatus, $variable_args)
    {
        // Create our argv array and run the command
        $argv = $this->argv(func_get_args());
        list($actualOutput, $statusCode) = $this->execute($argv);

        // Confirm that our output and status code match expectations
        $this->assertContains($expectedOutput, $actualOutput);
        $this->assertEquals($expectedStatus, $statusCode);
    }

    /**
     * Prepare our $argv array; put the app name in $argv[0] followed by
     * the command name and all command arguments and options.
     */
    protected function argv($functionParameters)
    {
        $argv = $functionParameters;
        array_shift($argv);
        array_shift($argv);
        array_unshift($argv, $this->appName);

        // TODO: replace paths beginning with '/fixtures' with actual path to fixture data

        return $argv;
    }

    /**
     * Simulated front controller
     */
    protected function execute($argv)
    {
        // Define a global output object to capture the test results
        $output = new BufferedOutput();

        // We can only call `Runner::execute()` once; then we need to tear down.
        $runner = new \Robo\Runner($this->commandClasses);
        $statusCode = $runner->execute($argv, $this->appName, $this->appVersion, $output);
        \Robo\Robo::unsetContainer();

        // Return the output and status code.
        $actualOutput = trim($output->fetch());
        return [$actualOutput, $statusCode];
    }
}
