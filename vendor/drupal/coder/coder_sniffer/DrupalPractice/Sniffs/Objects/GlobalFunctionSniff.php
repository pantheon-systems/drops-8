<?php
/**
 * DrupalPractice_Sniffs_Objects_GlobalFunctionSniff.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Checks that global functions like t() are not used in forms or controllers.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class DrupalPractice_Sniffs_Objects_GlobalFunctionSniff implements PHP_CodeSniffer_Sniff
{

    /**
     * List of global functions that should not be called.
     *
     * @var string[]
     */
    protected $functions = array(
                            'drupal_get_destination'   => 'the "redirect.destination" service',
                            'drupal_render'            => 'the "renderer" service',
                            'entity_load'              => 'the "entity_type.manager" service',
                            'file_load'                => 'the "entity_type.manager" service',
                            'format_date'              => 'the "date.formatter" service',
                            'node_load'                => 'the "entity_type.manager" service',
                            'node_type_load'           => 'the "entity_type.manager" service',
                            't'                        => '$this->t()',
                            'taxonomy_term_load'       => 'the "entity_type.manager" service',
                            'taxonomy_vocabulary_load' => 'the "entity_type.manager" service',
                            'user_load'                => 'the "entity_type.manager" service',
                            'user_role_load'           => 'the "entity_type.manager" service',
                           );


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_STRING);

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token
     *                                         in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // We are only interested in function calls, which are not in the global
        // scope.
        if (isset($this->functions[$tokens[$stackPtr]['content']]) === false
            || isset($tokens[($stackPtr + 1)]) === false
            || $tokens[($stackPtr + 1)]['code'] !== T_OPEN_PARENTHESIS
            || empty($tokens[$stackPtr]['conditions']) === true
        ) {
            return;
        }

        // If there is an object operator before the call then this is a method
        // invocation, not a function call.
        $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
        if ($tokens[$previous]['code'] === T_OBJECT_OPERATOR) {
            return;
        }

        // Check that this statement is not in a static function.
        foreach ($tokens[$stackPtr]['conditions'] as $conditionPtr => $conditionCode) {
            if ($conditionCode === T_FUNCTION && $phpcsFile->getMethodProperties($conditionPtr)['is_static'] === true) {
                return;
            }
        }

        // Check if the class extends another class and get the name of the class
        // that is extended.
        $classPtr    = key($tokens[$stackPtr]['conditions']);
        $extendsName = $phpcsFile->findExtendedClassName($classPtr);

        if (($extendsName === false
            || in_array($extendsName, DrupalPractice_Sniffs_Objects_GlobalDrupalSniff::$baseClasses) === false)
            && DrupalPractice_Project::isServiceClass($phpcsFile, $classPtr) === false
        ) {
            return;
        }

        $warning = '%s() calls should be avoided in classes, use dependency injection and %s instead';
        $data    = array(
                    $tokens[$stackPtr]['content'],
                    $this->functions[$tokens[$stackPtr]['content']],
                   );
        $phpcsFile->addWarning($warning, $stackPtr, 'GlobalFunction', $data);

    }//end process()


}//end class
