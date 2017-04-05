<?php
/**
 * Drupal_Sniffs_FunctionCalls_DbSelectBracesSniff
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Check that db_select() calls do not use {} braces for the table name.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class DrupalPractice_Sniffs_FunctionCalls_DbSelectBracesSniff extends Drupal_Sniffs_Semantics_FunctionCall
{


    /**
     * Returns an array of function names this test wants to listen for.
     *
     * @return array
     */
    public function registerFunctionNames()
    {
        return array('db_select');

    }//end registerFunctionNames()


    /**
     * Processes this function call.
     *
     * @param PHP_CodeSniffer_File $phpcsFile    The file being scanned.
     * @param int                  $stackPtr     The position of the function call in
     *                                           the stack.
     * @param int                  $openBracket  The position of the opening
     *                                           parenthesis in the stack.
     * @param int                  $closeBracket The position of the closing
     *                                           parenthesis in the stack.
     *
     * @return void
     */
    public function processFunctionCall(
        PHP_CodeSniffer_File $phpcsFile,
        $stackPtr,
        $openBracket,
        $closeBracket
    ) {
        $tokens   = $phpcsFile->getTokens();
        $argument = $this->getArgument(1);

        if ($argument !== false && $tokens[$argument['start']]['code'] === T_CONSTANT_ENCAPSED_STRING
            && strpos($tokens[$argument['start']]['content'], '{') !== false
        ) {
            $warning = 'Do not use {} curly brackets in db_select() table names';
            $phpcsFile->addWarning($warning, $argument['start'], 'DbSelectBrace');
        }

    }//end processFunctionCall()


}//end class
