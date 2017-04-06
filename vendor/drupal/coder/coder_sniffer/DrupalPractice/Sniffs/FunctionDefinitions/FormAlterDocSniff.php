<?php
/**
 * DrupalPractice_Sniffs_FunctionDefinitions_FormAlterDocSniff.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Checks that the comment "Implements hook_form_alter()." actually matches the
 * function signature.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class DrupalPractice_Sniffs_FunctionDefinitions_FormAlterDocSniff extends Drupal_Sniffs_Semantics_FunctionDefinition
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
        $tokens        = $phpcsFile->getTokens();
        $docCommentEnd = $phpcsFile->findPrevious(T_WHITESPACE, ($functionPtr - 1), null, true);

        // If there is no doc comment there is nothing we can check.
        if ($docCommentEnd === false || $tokens[$docCommentEnd]['code'] !== T_DOC_COMMENT_CLOSE_TAG) {
            return;
        }

        $commentLine  = ($docCommentEnd - 1);
        $commentFound = false;
        while ($tokens[$commentLine]['code'] !== T_DOC_COMMENT_OPEN_TAG) {
            if (strpos($tokens[$commentLine]['content'], 'Implements hook_form_alter().') === 0) {
                $commentFound = true;
                break;
            }

            $commentLine--;
        }

        if ($commentFound === false) {
            return;
        }

        $projectName = DrupalPractice_Project::getName($phpcsFile);
        if ($projectName === false) {
            return;
        }

        if ($tokens[$stackPtr]['content'] !== $projectName.'_form_alter') {
            $warning = 'Doc comment indicates hook_form_alter() but function signature is "%s" instead of "%s". Did you mean hook_form_FORM_ID_alter()?';
            $data    = array(
                        $tokens[$stackPtr]['content'],
                        $projectName.'_form_alter',
                       );
            $phpcsFile->addWarning($warning, $commentLine, 'Different', $data);
        }

    }//end processFunction()


}//end class
