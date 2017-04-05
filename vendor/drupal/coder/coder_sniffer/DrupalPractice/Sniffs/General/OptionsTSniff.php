<?php
/**
 * DrupalPractice_Sniffs_General_OptionsTSniff
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Checks that values in #otions form arrays are translated.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class DrupalPractice_Sniffs_General_OptionsTSniff implements PHP_CodeSniffer_Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_CONSTANT_ENCAPSED_STRING);

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The current file being processed.
     * @param int                  $stackPtr  The position of the current token
     *                                        in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        // Look for the string "#options".
        $tokens = $phpcsFile->getTokens();
        if ($tokens[$stackPtr]['content'] !== '"#options"' && $tokens[$stackPtr]['content'] !== "'#options'") {
            return;
        }

        // Look for an opening array pattern that starts to define #options
        // values.
        $statementEnd = $phpcsFile->findNext(T_SEMICOLON, ($stackPtr + 1));
        $arrayString  = $phpcsFile->getTokensAsString(($stackPtr + 1), ($statementEnd - $stackPtr));
        // Cut out all the white space.
        $arrayString = preg_replace('/\s+/', '', $arrayString);

        if (strpos($arrayString, '=>array(') !== 0 && strpos($arrayString, ']=array(') !== 0) {
            return;
        }

        // We only search within the #options array.
        $arrayToken   = $phpcsFile->findNext(T_ARRAY, ($stackPtr + 1));
        $statementEnd = $tokens[$arrayToken]['parenthesis_closer'];

        // Go through the array by examining stuff after "=>".
        $arrow = $phpcsFile->findNext(T_DOUBLE_ARROW, ($stackPtr + 1), $statementEnd, false, null, true);
        while ($arrow !== false) {
            $arrayValue = $phpcsFile->findNext(T_WHITESPACE, ($arrow + 1), $statementEnd, true);
            // We are only interested in string literals that are not numbers
            // and more than 3 characters long.
            if ($tokens[$arrayValue]['code'] === T_CONSTANT_ENCAPSED_STRING
                && is_numeric(substr($tokens[$arrayValue]['content'], 1, -1)) === false
                && strlen($tokens[$arrayValue]['content']) > 5
            ) {
                // We need to make sure that the string is the one and only part
                // of the array value.
                $afterValue = $phpcsFile->findNext(T_WHITESPACE, ($arrayValue + 1), $statementEnd, true);
                if ($tokens[$afterValue]['code'] === T_COMMA || $tokens[$afterValue]['code'] === T_CLOSE_PARENTHESIS) {
                    $warning = '#options values usually have to run through t() for translation';
                    $phpcsFile->addWarning($warning, $arrayValue, 'TforValue');
                }
            }

            $arrow = $phpcsFile->findNext(T_DOUBLE_ARROW, ($arrow + 1), $statementEnd, false, null, true);
        }

    }//end process()


}//end class
