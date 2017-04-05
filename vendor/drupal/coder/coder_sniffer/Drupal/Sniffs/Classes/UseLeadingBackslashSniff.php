<?php
/**
 * Drupal_Sniffs_Classes_UseLeadingBackslashSniff.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Use statements to import classes must not begin with "\".
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class Drupal_Sniffs_Classes_UseLeadingBackslashSniff implements PHP_CodeSniffer_Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_USE);

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token in
     *                                        the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // Only check use statements in the global scope.
        if (empty($tokens[$stackPtr]['conditions']) === false) {
            return;
        }

        $startPtr = $phpcsFile->findNext(
            PHP_CodeSniffer_Tokens::$emptyTokens,
            ($stackPtr + 1),
            null,
            true
        );

        if ($startPtr !== null && $tokens[$startPtr]['code'] === T_NS_SEPARATOR) {
            $error = 'When importing a class with "use", do not include a leading \\';
            $fix   = $phpcsFile->addFixableError($error, $startPtr, 'SeparatorStart');
            if ($fix === true) {
                $phpcsFile->fixer->replaceToken($startPtr, '');
            }
        }

    }//end process()


}//end class
