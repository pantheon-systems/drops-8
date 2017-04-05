<?php
/**
 * Drupal_Sniffs_Methods_MethodDeclarationSniff.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Checks that the method declaration is correct.
 *
 * Extending PSR2_Sniffs_Methods_MethodDeclarationSniff to also support traits.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class Drupal_Sniffs_Methods_MethodDeclarationSniff extends PSR2_Sniffs_Methods_MethodDeclarationSniff
{


    /**
     * Constructor.
     */
    public function __construct()
    {
        PHP_CodeSniffer_Standards_AbstractScopeSniff::__construct(array(T_CLASS, T_INTERFACE, T_TRAIT), array(T_FUNCTION));

    }//end __construct()


}//end class
