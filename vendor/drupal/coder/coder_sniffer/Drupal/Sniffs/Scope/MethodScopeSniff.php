<?php
/**
 * Verifies that class/interface/trait methods have scope modifiers.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Verifies that class/interface/trait methods have scope modifiers.
 *
 * Laregely copied from Squiz_Sniffs_Scope_MethodScopeSniff to work on traits
 * and have a fixer.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class Drupal_Sniffs_Scope_MethodScopeSniff extends PHP_CodeSniffer_Standards_AbstractScopeSniff
{


    /**
     * Constructs a Squiz_Sniffs_Scope_MethodScopeSniff.
     */
    public function __construct()
    {
        parent::__construct(array(T_CLASS, T_INTERFACE, T_TRAIT), array(T_FUNCTION));

    }//end __construct()


    /**
     * Processes the function tokens within the class.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file where this token was found.
     * @param int                  $stackPtr  The position where the token was found.
     * @param int                  $currScope The current scope opener token.
     *
     * @return void
     */
    protected function processTokenWithinScope(PHP_CodeSniffer_File $phpcsFile, $stackPtr, $currScope)
    {
        $tokens = $phpcsFile->getTokens();

        $methodName = $phpcsFile->getDeclarationName($stackPtr);
        if ($methodName === null) {
            // Ignore closures.
            return;
        }

        if ($phpcsFile->hasCondition($stackPtr, T_FUNCTION) === true) {
            // Ignore nested functions.
            return;
        }

        $modifier = null;
        for ($i = ($stackPtr - 1); $i > 0; $i--) {
            if ($tokens[$i]['line'] < $tokens[$stackPtr]['line']) {
                break;
            } else if (isset(PHP_CodeSniffer_Tokens::$scopeModifiers[$tokens[$i]['code']]) === true) {
                $modifier = $i;
                break;
            }
        }

        if ($modifier === null) {
            $error = 'Visibility must be declared on method "%s"';
            $data  = array($methodName);
            $fix   = $phpcsFile->addFixableError($error, $stackPtr, 'Missing', $data);

            if ($fix === true) {
                // No scope modifier means the method is public in PHP, fix that
                // to be explicitly public.
                $phpcsFile->fixer->addContentBefore($stackPtr, 'public ');
            }
        }

    }//end processTokenWithinScope()


}//end class
