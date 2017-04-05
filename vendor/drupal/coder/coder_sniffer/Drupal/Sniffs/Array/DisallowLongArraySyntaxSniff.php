<?php
/**
 * Drupal_Sniffs_Array_DisallowLongArraySyntaxSniff.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Bans the use of the PHP long array syntax in Drupal 8.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class Drupal_Sniffs_Array_DisallowLongArraySyntaxSniff extends Generic_Sniffs_Arrays_DisallowLongArraySyntaxSniff
{


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token
     *                                        in the stack passed in $tokens.
     *
     * @return void|int
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $drupalVersion = DrupalPractice_Project::getCoreVersion($phpcsFile);
        if ($drupalVersion !== '8.x') {
            // No need to check this file again, mark it as done.
            return ($phpcsFile->numTokens + 1);
        }

        return parent::process($phpcsFile, $stackPtr);

    }//end process()


}//end class
