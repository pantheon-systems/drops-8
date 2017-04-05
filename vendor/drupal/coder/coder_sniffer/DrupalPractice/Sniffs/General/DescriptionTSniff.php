<?php
/**
 * DrupalPractice_Sniffs_General_DescriptionTSniff
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Checks that string values for #description in render arrays are translated.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class DrupalPractice_Sniffs_General_DescriptionTSniff implements PHP_CodeSniffer_Sniff
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
        // Look for the string "#description".
        $tokens = $phpcsFile->getTokens();
        if ($tokens[$stackPtr]['content'] !== '"#description"' && $tokens[$stackPtr]['content'] !== "'#description'") {
            return;
        }

        // Look for an array pattern that starts to define #description values.
        $statementEnd = $phpcsFile->findNext(T_SEMICOLON, ($stackPtr + 1));
        $arrayString  = $phpcsFile->getTokensAsString(($stackPtr + 1), ($statementEnd - $stackPtr));
        // Cut out all the white space.
        $arrayString = preg_replace('/\s+/', '', $arrayString);

        if (strpos($arrayString, '=>"') !== 0 && strpos($arrayString, ']="') !== 0
            && strpos($arrayString, "=>'") !== 0 && strpos($arrayString, "]='") !== 0
        ) {
            return;
        }

        $stringToken = $phpcsFile->findNext(T_CONSTANT_ENCAPSED_STRING, ($stackPtr + 1));
        $content     = strip_tags($tokens[$stringToken]['content']);

        if (strlen($content) > 5) {
            $warning = '#description values usually have to run through t() for translation';
            $phpcsFile->addWarning($warning, $stringToken);
        }

    }//end process()


}//end class
