<?php
/**
 * DrupalPractice_Sniffs_General_FormStateInputSniff.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Throws a message whenever $form_state['input'] is used. $form_state['values']
 * is preferred.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class DrupalPractice_Sniffs_General_FormStateInputSniff implements PHP_CodeSniffer_Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_VARIABLE);

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
        if ($phpcsFile->getTokensAsString($stackPtr, 4) === '$form_state[\'input\']'
            || $phpcsFile->getTokensAsString($stackPtr, 4) === '$form_state["input"]'
        ) {
            $warning = 'Do not use the raw $form_state[\'input\'], use $form_state[\'values\'] instead where possible';
            $phpcsFile->addWarning($warning, $stackPtr, 'Input');
        }

    }//end process()


}//end class
