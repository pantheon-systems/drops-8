<?php
/**
 * DrupalPractice_Sniffs_General_AccessAdminPagesSniff
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Throws a warning if the "access administration pages" string is found in
 * hook_menu().
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class DrupalPractice_Sniffs_General_AccessAdminPagesSniff extends Drupal_Sniffs_Semantics_FunctionDefinition
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

        $tokens = $phpcsFile->getTokens();

        $fileName = substr(basename($phpcsFile->getFilename()), 0, -7);
        if ($tokens[$stackPtr]['content'] !== ($fileName.'_menu')) {
            return;
        }

        // Search in the function body for "access administration pages" strings.
        $string = $phpcsFile->findNext(
            T_CONSTANT_ENCAPSED_STRING,
            $tokens[$functionPtr]['scope_opener'],
            $tokens[$functionPtr]['scope_closer']
        );

        while ($string !== false) {
            if (substr($tokens[$string]['content'], 1, -1) === 'access administration pages') {
                $warning = 'The administration menu callback should probably use "administer site configuration" - which implies the user can change something - rather than "access administration pages" which is about viewing but not changing configurations.';
                $phpcsFile->addWarning($warning, $string, 'PermissionFound');
            }

            $string = $phpcsFile->findNext(
                T_CONSTANT_ENCAPSED_STRING,
                ($string + 1),
                $tokens[$functionPtr]['scope_closer']
            );
        }//end while

    }//end processFunction()


}//end class
