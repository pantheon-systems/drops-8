<?php
/**
 * DrupalPractice_Sniffs_FunctionDefinitions_HookInitCssSniff.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Checks that drupal_add_css() is not used in hook_init().
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class DrupalPractice_Sniffs_FunctionDefinitions_HookInitCssSniff extends Drupal_Sniffs_Semantics_FunctionDefinition
{


    /**
     * Process this function definition.
     *
     * @param PHP_CodeSniffer_File $phpcsFile   The file being scanned.
     * @param int                  $stackPtr    The position of the function name in the stack.
     *                                           name in the stack.
     * @param int                  $functionPtr The position of the function keyword in the stack.
     *                                           keyword in the stack.
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

        // This check only applies to Drupal 7, not Drupal 6.
        if (DrupalPractice_Project::getCoreVersion($phpcsFile) !== '7.x') {
            return;
        }

        $fileName = substr(basename($phpcsFile->getFilename()), 0, -7);
        $tokens   = $phpcsFile->getTokens();
        if ($tokens[$stackPtr]['content'] !== ($fileName.'_init') && $tokens[$stackPtr]['content'] !== ($fileName.'_page_build')) {
            return;
        }

        // Search in the function body for drupal_add_css() calls.
        $string = $phpcsFile->findNext(
            T_STRING,
            $tokens[$functionPtr]['scope_opener'],
            $tokens[$functionPtr]['scope_closer']
        );
        while ($string !== false) {
            if ($tokens[$string]['content'] === 'drupal_add_css' || $tokens[$string]['content'] === 'drupal_add_js') {
                $opener = $phpcsFile->findNext(
                    PHP_CodeSniffer_Tokens::$emptyTokens,
                    ($string + 1),
                    null,
                    true
                );
                if ($opener !== false
                    && $tokens[$opener]['code'] === T_OPEN_PARENTHESIS
                ) {
                    if ($tokens[$stackPtr]['content'] === ($fileName.'_init')) {
                        $warning = 'Do not use %s() in hook_init(), use #attached for CSS and JS in your page/form callback or in hook_page_build() instead';
                        $phpcsFile->addWarning($warning, $string, 'AddFunctionFound', array($tokens[$string]['content']));
                    } else {
                        $warning = 'Do not use %s() in hook_page_build(), use #attached for CSS and JS on the $page render array instead';
                        $phpcsFile->addWarning($warning, $string, 'AddFunctionFoundPageBuild', array($tokens[$string]['content']));
                    }
                }
            }

            $string = $phpcsFile->findNext(
                T_STRING,
                ($string + 1),
                $tokens[$functionPtr]['scope_closer']
            );
        }//end while

    }//end processFunction()


}//end class
