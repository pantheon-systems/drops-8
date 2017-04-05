<?php
/**
 * DrupalPractice_Sniffs_Commenting_ExpectedExceptionSniff.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Checks that the PHPunit @expectedExcpetion tags are not used.
 *
 * See https://thephp.cc/news/2016/02/questioning-phpunit-best-practices .
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class DrupalPractice_Sniffs_Commenting_ExpectedExceptionSniff implements PHP_CodeSniffer_Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_DOC_COMMENT_TAG);

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token
     *                                         in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $content = $tokens[$stackPtr]['content'];
        if ($content === '@expectedException' || $content === '@expectedExceptionCode'
            || $content === '@expectedExceptionMessage'
            || $content === '@expectedExceptionMessageRegExp'
        ) {
            $warning = '%s tags should not be used, use $§this->setExpectedException() or $this->expectException() instead';
            $phpcsFile->addWarning($warning, $stackPtr, 'TagFound', [$content]);
        }

    }//end process()


}//end class
