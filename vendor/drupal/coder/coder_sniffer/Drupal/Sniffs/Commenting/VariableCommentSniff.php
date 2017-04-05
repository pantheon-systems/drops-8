<?php
/**
 * Parses and verifies class property doc comments.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Parses and verifies class property doc comments.
 *
 * Laregely copied from Squiz_Sniffs_Commenting_VariableCommentSniff.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class Drupal_Sniffs_Commenting_VariableCommentSniff extends PHP_CodeSniffer_Standards_AbstractVariableSniff
{


    /**
     * Called to process class member vars.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token
     *                                        in the stack passed in $tokens.
     *
     * @return void
     */
    public function processMemberVar(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens       = $phpcsFile->getTokens();
        $commentToken = array(
                         T_COMMENT,
                         T_DOC_COMMENT_CLOSE_TAG,
                        );

        $commentEnd = $phpcsFile->findPrevious($commentToken, $stackPtr);
        if ($commentEnd === false) {
            return;
        }

        if ($tokens[$commentEnd]['code'] === T_COMMENT) {
            $fix = $phpcsFile->addFixableError('You must use "/**" style comments for a member variable comment', $stackPtr, 'WrongStyle');
            if ($fix === true) {
                // Convert the comment into a doc comment.
                $phpcsFile->fixer->beginChangeset();
                $comment = '';
                for ($i = $commentEnd; $tokens[$i]['code'] === T_COMMENT; $i--) {
                    $comment = ' *'.ltrim($tokens[$i]['content'], '/* ').$comment;
                    $phpcsFile->fixer->replaceToken($i, '');
                }

                $phpcsFile->fixer->replaceToken($commentEnd, "/**\n".rtrim($comment, "*/\n")."\n */\n");
                $phpcsFile->fixer->endChangeset();
            }

            return;
        } else if ($tokens[$commentEnd]['code'] !== T_DOC_COMMENT_CLOSE_TAG) {
            return;
        } else {
            // Make sure the comment we have found belongs to us.
            $commentFor = $phpcsFile->findNext(array(T_VARIABLE, T_CLASS, T_INTERFACE), ($commentEnd + 1));
            if ($commentFor !== $stackPtr) {
                return;
            }
        }//end if

        $commentStart = $tokens[$commentEnd]['comment_opener'];

        $commentContent = $phpcsFile->getTokensAsString($commentStart, ($commentEnd - $commentStart));
        if (strpos($commentContent, '{@inheritdoc}') !== false) {
            return;
        }

        $foundVar = null;
        foreach ($tokens[$commentStart]['comment_tags'] as $tag) {
            if ($tokens[$tag]['content'] === '@var') {
                if ($foundVar !== null) {
                    $error = 'Only one @var tag is allowed in a member variable comment';
                    $phpcsFile->addError($error, $tag, 'DuplicateVar');
                } else {
                    $foundVar = $tag;
                }
            } else if ($tokens[$tag]['content'] === '@see') {
                // Make sure the tag isn't empty.
                $string = $phpcsFile->findNext(T_DOC_COMMENT_STRING, $tag, $commentEnd);
                if ($string === false || $tokens[$string]['line'] !== $tokens[$tag]['line']) {
                    $error = 'Content missing for @see tag in member variable comment';
                    $phpcsFile->addError($error, $tag, 'EmptySees');
                }
            }//end if
        }//end foreach

        // The @var tag is the only one we require.
        if ($foundVar === null) {
            $error = 'Missing @var tag in member variable comment';
            $phpcsFile->addError($error, $commentEnd, 'MissingVar');
            return;
        }

        $firstTag = $tokens[$commentStart]['comment_tags'][0];
        if ($foundVar !== null && $tokens[$firstTag]['content'] !== '@var') {
            $error = 'The @var tag must be the first tag in a member variable comment';
            $phpcsFile->addError($error, $foundVar, 'VarOrder');
        }

        // Make sure the tag isn't empty and has the correct padding.
        $string = $phpcsFile->findNext(T_DOC_COMMENT_STRING, $foundVar, $commentEnd);
        if ($string === false || $tokens[$string]['line'] !== $tokens[$foundVar]['line']) {
            $error = 'Content missing for @var tag in member variable comment';
            $phpcsFile->addError($error, $foundVar, 'EmptyVar');
            return;
        }

        $varType = $tokens[($foundVar + 2)]['content'];

        // There may be multiple types separated by pipes.
        $suggestedTypes = array();
        foreach (explode('|', $varType) as $type) {
            $suggestedTypes[] = Drupal_Sniffs_Commenting_FunctionCommentSniff::suggestType($type);
        }

        $suggestedType = implode('|', $suggestedTypes);

        // Detect and auto-fix the common mistake that the variable name is
        // appended to the type declaration.
        $matches = array();
        if (preg_match('/^([^\s]+)(\s+\$.+)$/', $varType, $matches) === 1) {
            $error = 'Do not append variable name "%s" to the type declaration in a member variable comment';
            $data  = array(
                      trim($matches[2]),
                     );
            $fix   = $phpcsFile->addFixableError($error, ($foundVar + 2), 'InlineVariableName', $data);
            if ($fix === true) {
                $phpcsFile->fixer->replaceToken(($foundVar + 2), $matches[1]);
            }
        } else if ($varType !== $suggestedType) {
            $error = 'Expected "%s" but found "%s" for @var tag in member variable comment';
            $data  = array(
                      $suggestedType,
                      $varType,
                     );
            $fix   = $phpcsFile->addFixableError($error, ($foundVar + 2), 'IncorrectVarType', $data);
            if ($fix === true) {
                $phpcsFile->fixer->replaceToken(($foundVar + 2), $suggestedType);
            }
        }//end if

    }//end processMemberVar()


    /**
     * Called to process a normal variable.
     *
     * Not required for this sniff.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The PHP_CodeSniffer file where this token was found.
     * @param int                  $stackPtr  The position where the double quoted
     *                                        string was found.
     *
     * @return void
     */
    protected function processVariable(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {

    }//end processVariable()


    /**
     * Called to process variables found in double quoted strings.
     *
     * Not required for this sniff.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The PHP_CodeSniffer file where this token was found.
     * @param int                  $stackPtr  The position where the double quoted
     *                                        string was found.
     *
     * @return void
     */
    protected function processVariableInString(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {

    }//end processVariableInString()


}//end class
