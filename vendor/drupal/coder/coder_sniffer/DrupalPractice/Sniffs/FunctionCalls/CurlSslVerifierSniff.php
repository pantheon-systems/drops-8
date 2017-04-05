<?php
/**
 * Drupal_Sniffs_FunctionCalls_CurlSslVerifierSniff
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Make sure that CURLOPT_SSL_VERIFYPEER is not disabled, since that is a
 * security issue.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class DrupalPractice_Sniffs_FunctionCalls_CurlSslVerifierSniff extends Drupal_Sniffs_Semantics_FunctionCall
{


    /**
     * Returns an array of function names this test wants to listen for.
     *
     * @return array
     */
    public function registerFunctionNames()
    {
        return array('curl_setopt');

    }//end registerFunctionNames()


    /**
     * Processes this function call.
     *
     * @param PHP_CodeSniffer_File $phpcsFile    The file being scanned.
     * @param int                  $stackPtr     The position of the function call in
     *                                           the stack.
     * @param int                  $openBracket  The position of the opening
     *                                           parenthesis in the stack.
     * @param int                  $closeBracket The position of the closing
     *                                           parenthesis in the stack.
     *
     * @return void
     */
    public function processFunctionCall(
        PHP_CodeSniffer_File $phpcsFile,
        $stackPtr,
        $openBracket,
        $closeBracket
    ) {
        $tokens = $phpcsFile->getTokens();
        $option = $this->getArgument(2);
        if ($tokens[$option['start']]['content'] !== 'CURLOPT_SSL_VERIFYPEER') {
            return;
        }

        $value = $this->getArgument(3);
        if ($tokens[$value['start']]['content'] === 'FALSE' || $tokens[$value['start']]['content'] === '0') {
            $warning = 'Potential security problem: SSL peer verification must not be disabled';
            $phpcsFile->addWarning($warning, $value['start'], 'SslPeerVerificationDisabled');
        }

    }//end processFunctionCall()


}//end class
