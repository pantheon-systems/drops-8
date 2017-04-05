<?php
/**
 * Generic_Sniffs_Strings_UnnecessaryStringConcatSniff.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @version   CVS: $Id: UnnecessaryStringConcatSniff.php 304603 2010-10-22 03:07:04Z squiz $
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Generic_Sniffs_Strings_UnnecessaryStringConcatSniff.
 *
 * Checks that two strings are not concatenated together; suggests
 * using one string instead.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @version   Release: 1.3.1
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class Drupal_Sniffs_Strings_UnnecessaryStringConcatSniff extends Generic_Sniffs_Strings_UnnecessaryStringConcatSniff
{


    /**
     * Processes this sniff, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token
     *                                        in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        // Work out which type of file this is for.
        $tokens = $phpcsFile->getTokens();
        if ($tokens[$stackPtr]['code'] === T_STRING_CONCAT) {
            if ($phpcsFile->tokenizerType === 'JS') {
                return;
            }
        } else {
            if ($phpcsFile->tokenizerType === 'PHP') {
                return;
            }
        }

        $prev = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
        $next = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
        if ($prev === false || $next === false) {
            return;
        }

        $stringTokens = PHP_CodeSniffer_Tokens::$stringTokens;
        if (in_array($tokens[$prev]['code'], $stringTokens) === true
            && in_array($tokens[$next]['code'], $stringTokens) === true
        ) {
            if ($tokens[$prev]['content'][0] === $tokens[$next]['content'][0]) {
                // Before we throw an error for PHP, allow strings to be
                // combined if they would have < and ? next to each other because
                // this trick is sometimes required in PHP strings.
                if ($phpcsFile->tokenizerType === 'PHP') {
                    $prevChar = substr($tokens[$prev]['content'], -2, 1);
                    $nextChar = $tokens[$next]['content'][1];
                    $combined = $prevChar.$nextChar;
                    if ($combined === '?'.'>' || $combined === '<'.'?') {
                        return;
                    }
                }

                // Before we throw an error check if the string is longer than
                // the line length limit.
                $lineLengthLimitSniff = new Drupal_Sniffs_Files_LineLengthSniff;

                $lineLenght   = $lineLengthLimitSniff->getLineLength($phpcsFile, $tokens[$prev]['line']);
                $stringLength = ($lineLenght + strlen($tokens[$next]['content']) - 4);
                if ($stringLength > $lineLengthLimitSniff->lineLimit) {
                    return;
                }

                $error = 'String concat is not required here; use a single string instead';
                if ($this->error === true) {
                    $phpcsFile->addError($error, $stackPtr, 'Found');
                } else {
                    $phpcsFile->addWarning($error, $stackPtr, 'Found');
                }
            }//end if
        }//end if

    }//end process()


}//end class
