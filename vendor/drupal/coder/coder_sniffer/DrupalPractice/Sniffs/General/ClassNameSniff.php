<?php
/**
 * DrupalPractice_Sniffs_General_ClassNameSniff
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Checks that classes without namespaces are properly prefixed with the module
 * name.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class DrupalPractice_Sniffs_General_ClassNameSniff implements PHP_CodeSniffer_Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(
                T_CLASS,
                T_INTERFACE,
               );

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
        // If there is a PHP 5.3 namespace declaration in the file we return
        // immediately as classes can be named arbitrary within a namespace.
        $namespace = $phpcsFile->findPrevious(T_NAMESPACE, ($stackPtr - 1));
        if ($namespace !== false) {
            return;
        }

        $moduleName = DrupalPractice_Project::getName($phpcsFile);
        if ($moduleName === false) {
            return;
        }

        $tokens = $phpcsFile->getTokens();

        $className = $phpcsFile->findNext(T_STRING, $stackPtr);
        $name      = trim($tokens[$className]['content']);

        // Underscores are omitted in class names. Also convert all characters
        // to lower case to compare them later.
        $classPrefix = strtolower(str_replace('_', '', $moduleName));
        // Views classes might have underscores in the name, which is also fine.
        $viewsPrefix = strtolower($moduleName);
        $name        = strtolower($name);

        if (strpos($name, $classPrefix) !== 0 && strpos($name, $viewsPrefix) !== 0) {
            $warning   = '%s name must be prefixed with the project name "%s"';
            $nameParts = explode('_', $moduleName);
            $camelName = '';
            foreach ($nameParts as &$part) {
                $camelName .= ucfirst($part);
            }

            $errorData = array(
                          ucfirst($tokens[$stackPtr]['content']),
                          $camelName,
                         );
            $phpcsFile->addWarning($warning, $className, 'ClassPrefix', $errorData);
        }

    }//end process()


}//end class
