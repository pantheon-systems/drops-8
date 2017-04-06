<?php

/**
 * DrupalPractice_Sniffs_Commenting_CommentEmptyLineSniff
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Throws a warning if there is a blank line after an inline comment.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class DrupalPractice_Sniffs_Commenting_CommentEmptyLineSniff implements PHP_CodeSniffer_Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_COMMENT);

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token
     *                                        in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $comment = rtrim($tokens[$stackPtr]['content']);

        // Only want inline comments.
        if (substr($comment, 0, 2) !== '//') {
            return;
        }

        // The line below the last comment cannot be empty.
        for ($i = ($stackPtr + 1); $i < $phpcsFile->numTokens; $i++) {
            if ($tokens[$i]['line'] === ($tokens[$stackPtr]['line'] + 1)) {
                if ($tokens[$i]['code'] !== T_WHITESPACE) {
                    return;
                }
            } else if ($tokens[$i]['line'] > ($tokens[$stackPtr]['line'] + 1)) {
                break;
            }
        }

        $warning = 'There must be no blank line following an inline comment';
        $phpcsFile->addWarning($warning, $stackPtr, 'SpacingAfter');

    }//end process()


}//end class
