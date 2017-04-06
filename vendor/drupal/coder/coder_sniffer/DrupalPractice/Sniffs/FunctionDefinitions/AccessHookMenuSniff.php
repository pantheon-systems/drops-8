<?php
/**
 * DrupalPractice_Sniffs_FunctionDefinitions_AccessHookMenuSniff.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Checks that there are no undocumented open access callbacks in hook_menu().
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class DrupalPractice_Sniffs_FunctionDefinitions_AccessHookMenuSniff extends Drupal_Sniffs_Semantics_FunctionDefinition
{


    /**
     * Process this function definition.
     *
     * @param PHP_CodeSniffer_File $phpcsFile   The file being scanned.
     * @param int                  $stackPtr    The position of the function name
     *                                          in the stack.
     * @param int                  $functionPtr The position of the function keyword
     *                                          in the stack.
     *
     * @return void
     */
    public function processFunction(PHP_CodeSniffer_File $phpcsFile, $stackPtr, $functionPtr)
    {
        $fileExtension = strtolower(substr($phpcsFile->getFilename(), -6));
        // Only check in *.module files.
        if ($fileExtension !== 'module') {
            return;
        }

        $fileName = substr(basename($phpcsFile->getFilename()), 0, -7);
        $tokens   = $phpcsFile->getTokens();
        if ($tokens[$stackPtr]['content'] !== ($fileName.'_menu')) {
            return;
        }

        // Search for 'access callabck' => TRUE in the function body.
        $string = $phpcsFile->findNext(
            T_CONSTANT_ENCAPSED_STRING,
            $tokens[$functionPtr]['scope_opener'],
            $tokens[$functionPtr]['scope_closer']
        );
        while ($string !== false) {
            if (substr($tokens[$string]['content'], 1, -1) === 'access callback') {
                $array_operator = $phpcsFile->findNext(
                    PHP_CodeSniffer_Tokens::$emptyTokens,
                    ($string + 1),
                    null,
                    true
                );
                if ($array_operator !== false
                    && $tokens[$array_operator]['code'] === T_DOUBLE_ARROW
                ) {
                    $callback = $phpcsFile->findNext(
                        PHP_CodeSniffer_Tokens::$emptyTokens,
                        ($array_operator + 1),
                        null,
                        true
                    );
                    if ($callback !== false && $tokens[$callback]['code'] === T_TRUE) {
                        // Check if there is a comment before the line that might
                        // explain stuff.
                        $commentBefore = $phpcsFile->findPrevious(
                            T_WHITESPACE,
                            ($string - 1),
                            $tokens[$functionPtr]['scope_opener'],
                            true
                        );
                        if ($commentBefore !== false && in_array($tokens[$commentBefore]['code'], PHP_CodeSniffer_Tokens::$commentTokens) === false) {
                            $warning = 'Open page callback found, please add a comment before the line why there is no access restriction';
                            $phpcsFile->addWarning($warning, $callback, 'OpenCallback');
                        }
                    }
                }//end if
            }//end if

            $string = $phpcsFile->findNext(
                T_CONSTANT_ENCAPSED_STRING,
                ($string + 1),
                $tokens[$functionPtr]['scope_closer']
            );
        }//end while

    }//end processFunction()


}//end class
