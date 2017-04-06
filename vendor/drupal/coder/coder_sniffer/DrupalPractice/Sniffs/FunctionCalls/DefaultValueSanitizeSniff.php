<?php
/**
 * DrupalPractice_Sniffs_FunctionCalls_DefaultValueSanitizeSniff
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Check that sanitization functions such as check_plain() are not used on Form
 * API #default_value elements.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class DrupalPractice_Sniffs_FunctionCalls_DefaultValueSanitizeSniff extends Drupal_Sniffs_Semantics_FunctionCall
{


    /**
     * Returns an array of function names this test wants to listen for.
     *
     * @return array
     */
    public function registerFunctionNames()
    {
        return array(
                'check_markup',
                'check_plain',
                'check_url',
                'filter_xss',
                'filter_xss_admin',
               );

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
        $tokens = $phpcsFile->getTokens();

        // We assume that the sequence '#default_value' => check_plain(...) is
        // wrong because the Form API already sanitizes #default_value.
        $arrow = $phpcsFile->findPrevious(PHP_CodeSniffer_Tokens::$emptyTokens, ($stackPtr - 1), null, true);
        if ($arrow === false || $tokens[$arrow]['code'] !== T_DOUBLE_ARROW) {
            return;
        }

        $arrayKey = $phpcsFile->findPrevious(PHP_CodeSniffer_Tokens::$emptyTokens, ($arrow - 1), null, true);
        if ($arrayKey === false
            || $tokens[$arrayKey]['code'] !== T_CONSTANT_ENCAPSED_STRING
            || substr($tokens[$arrayKey]['content'], 1, -1) !== '#default_value'
        ) {
            return;
        }

        $warning = 'Do not use the %s() sanitization function on Form API #default_value elements, they get escaped automatically';
        $data    = array($tokens[$stackPtr]['content']);
        $phpcsFile->addWarning($warning, $stackPtr, 'DefaultValue', $data);

    }//end processFunctionCall()


}//end class
