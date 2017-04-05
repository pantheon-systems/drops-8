<?php
/**
 * DrupalPractice_Sniffs_General_LanguageNoneSniff
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Checks that ['und'] is not used, should be LANGUAGE_NONE.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class DrupalPractice_Sniffs_General_LanguageNoneSniff implements PHP_CodeSniffer_Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(
                T_OPEN_SQUARE_BRACKET,
                T_OPEN_SHORT_ARRAY,
               );

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The current file being processed.
     * @param int                  $stackPtr  The position of the current token
     *                                        in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $sequence = $phpcsFile->getTokensAsString($stackPtr, 3);
        if ($sequence === "['und']" || $sequence === '["und"]') {
            $warning = "Are you accessing field values here? Then you should use LANGUAGE_NONE instead of 'und'";
            $phpcsFile->addWarning($warning, ($stackPtr + 1), 'Und');
        }

    }//end process()


}//end class
