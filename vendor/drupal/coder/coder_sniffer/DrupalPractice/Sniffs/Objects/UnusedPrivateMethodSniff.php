<?php
/**
 * DrupalPractice_Sniffs_Objects_UnusedPrivateMethodSniff.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Checks that private methods are actually used in a class.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class DrupalPractice_Sniffs_Objects_UnusedPrivateMethodSniff extends PHP_CodeSniffer_Standards_AbstractScopeSniff
{


    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct([T_CLASS], [T_FUNCTION], false);

    }//end __construct()


    /**
     * Processes the tokens within the scope.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being processed.
     * @param int                  $stackPtr  The position where this token was
     *                                        found.
     * @param int                  $currScope The position of the current scope.
     *
     * @return void
     */
    protected function processTokenWithinScope(PHP_CodeSniffer_File $phpcsFile, $stackPtr, $currScope)
    {
        // Only check private methods.
        $methodProperties = $phpcsFile->getMethodProperties($stackPtr);
        if ($methodProperties['scope'] !== 'private' || $methodProperties['is_static'] === true) {
            return;
        }

        $tokens     = $phpcsFile->getTokens();
        $methodName = $phpcsFile->getDeclarationName($stackPtr);

        $classPtr = key($tokens[$stackPtr]['conditions']);

        // Search for direct $this->methodCall() or indirect callbacks [$this,
        // 'methodCall'].
        $current = $tokens[$classPtr]['scope_opener'];
        $end     = $tokens[$classPtr]['scope_closer'];
        while (($current = $phpcsFile->findNext(T_VARIABLE, ($current + 1), $end)) !== false) {
            if ($tokens[$current]['content'] !== '$this') {
                continue;
            }

            $next = $phpcsFile->findNext(PHP_CodeSniffer_Tokens::$emptyTokens, ($current + 1), null, true);
            if ($next === false) {
                continue;
            }

            if ($tokens[$next]['code'] === T_OBJECT_OPERATOR) {
                $call = $phpcsFile->findNext(PHP_CodeSniffer_Tokens::$emptyTokens, ($next + 1), null, true);
                if ($call === false || $tokens[$call]['content'] !== $methodName) {
                    continue;
                }

                $parenthesis = $phpcsFile->findNext(PHP_CodeSniffer_Tokens::$emptyTokens, ($call + 1), null, true);
                if ($parenthesis === false || $tokens[$parenthesis]['code'] !== T_OPEN_PARENTHESIS) {
                    continue;
                }

                // At this point this is a method call to the private method, so we
                // can stop.
                return;
            } else if ($tokens[$next]['code'] === T_COMMA) {
                $call = $phpcsFile->findNext(PHP_CodeSniffer_Tokens::$emptyTokens, ($next + 1), null, true);
                if ($call === false || substr($tokens[$call]['content'], 1, -1) !== $methodName) {
                    continue;
                }

                // At this point this is likely the private method as callback on a
                // function such as array_filter().
                return;
            }//end if
        }//end while

        $warning = 'Unused private method %s()';
        $data    = [$methodName];
        $phpcsFile->addWarning($warning, $stackPtr, 'UnusedMethod', $data);

    }//end processTokenWithinScope()


}//end class
