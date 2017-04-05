<?php
/**
 * Drupal_Sniffs_Commenting_DataTypeNamespaceSniff.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Checks that data types in param, return, var tags are fully namespaced.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class Drupal_Sniffs_Commenting_DataTypeNamespaceSniff implements PHP_CodeSniffer_Sniff
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

        // Seek to the end of the statement and get the string before the semi colon.
        $semiColon = $phpcsFile->findEndOfStatement($stackPtr);
        if ($tokens[$semiColon]['code'] !== T_SEMICOLON) {
            return;
        }

        $classPtr = $phpcsFile->findPrevious(
            PHP_CodeSniffer_Tokens::$emptyTokens,
            ($semiColon - 1),
            null,
            true
        );

        if ($tokens[$classPtr]['code'] !== T_STRING) {
            return;
        }

        // Replace @var data types in doc comments with the fully qualified class
        // name.
        $useNamespacePtr = $phpcsFile->findNext([T_STRING], ($stackPtr + 1));
        $useNamespaceEnd = $phpcsFile->findNext(
            [
             T_NS_SEPARATOR,
             T_STRING,
            ],
            ($useNamespacePtr + 1),
            null,
            true
        );
        $fullNamespace   = $phpcsFile->getTokensAsString($useNamespacePtr, ($useNamespaceEnd - $useNamespacePtr));

        $tag = $phpcsFile->findNext(T_DOC_COMMENT_TAG, ($stackPtr + 1));

        while ($tag !== false) {
            if (($tokens[$tag]['content'] === '@var'
                || $tokens[$tag]['content'] === '@return'
                || $tokens[$tag]['content'] === '@param')
                && isset($tokens[($tag + 1)]) === true
                && $tokens[($tag + 1)]['code'] === T_DOC_COMMENT_WHITESPACE
                && isset($tokens[($tag + 2)]) === true
                && $tokens[($tag + 2)]['code'] === T_DOC_COMMENT_STRING
                && strpos($tokens[($tag + 2)]['content'], $tokens[$classPtr]['content']) === 0
            ) {
                $error = 'Data types in %s tags need to be fully namespaced';
                $data  = [$tokens[$tag]['content']];
                $fix   = $phpcsFile->addFixableError($error, ($tag + 2), 'DataTypeNamespace', $data);
                if ($fix === true) {
                    $replacement = '\\'.$fullNamespace.substr($tokens[($tag + 2)]['content'], strlen($tokens[$classPtr]['content']));
                    $phpcsFile->fixer->replaceToken(($tag + 2), $replacement);
                }
            }

            $tag = $phpcsFile->findNext(T_DOC_COMMENT_TAG, ($tag + 1));
        }//end while

    }//end process()


}//end class
