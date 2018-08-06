<?php

namespace Drupal\Tests\redirect\Kernel;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\redirect\Entity\Redirect;
use Drupal\Core\Language\Language;
use Drupal\redirect\Exception\RedirectLoopException;
use Drupal\KernelTests\KernelTestBase;

/**
 * Redirect entity and redirect API test coverage.
 *
 * @group redirect
 */
class RedirectAPITest extends KernelTestBase {

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $controller;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('redirect', 'link', 'field', 'system', 'user', 'language', 'views');

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installEntitySchema('redirect');
    $this->installEntitySchema('user');
    $this->installSchema('system', ['router']);
    $this->installConfig(array('redirect'));

    $language = ConfigurableLanguage::createFromLangcode('de');
    $language->save();

    $this->controller = $this->container->get('entity.manager')->getStorage('redirect');
  }

  /**
   * Test redirect entity logic.
   */
  public function testRedirectEntity() {
    // Create a redirect and test if hash has been generated correctly.
    /** @var \Drupal\redirect\Entity\Redirect $redirect */
    $redirect = $this->controller->create();
    $redirect->setSource('some-url', array('key' => 'val'));
    $redirect->setRedirect('node');

    $redirect->save();
    $this->assertEquals(Redirect::generateHash('some-url', array('key' => 'val'), Language::LANGCODE_NOT_SPECIFIED), $redirect->getHash());
    // Update the redirect source query and check if hash has been updated as
    // expected.
    $redirect->setSource('some-url', array('key1' => 'val1'));
    $redirect->save();
    $this->assertEqual(Redirect::generateHash('some-url', array('key1' => 'val1'), Language::LANGCODE_NOT_SPECIFIED), $redirect->getHash());
    // Update the redirect source path and check if hash has been updated as
    // expected.
    $redirect->setSource('another-url', array('key1' => 'val1'));
    $redirect->save();
    $this->assertEqual(Redirect::generateHash('another-url', array('key1' => 'val1'), Language::LANGCODE_NOT_SPECIFIED), $redirect->getHash());
    // Update the redirect language and check if hash has been updated as
    // expected.
    $redirect->setLanguage('de');
    $redirect->save();
    $this->assertEqual(Redirect::generateHash('another-url', array('key1' => 'val1'), 'de'), $redirect->getHash());
    // Create a few more redirects to test the select.
    for ($i = 0; $i < 5; $i++) {
      $redirect = $this->controller->create();
      $redirect->setSource($this->randomMachineName());
      $redirect->save();
    }
    /** @var \Drupal\redirect\RedirectRepository $repository */
    $repository = \Drupal::service('redirect.repository');
    $redirect = $repository->findMatchingRedirect('another-url', array('key1' => 'val1'), 'de');
    if (!empty($redirect)) {
      $this->assertEqual($redirect->getSourceUrl(), '/another-url?key1=val1');
    }
    else {
      $this->fail(t('Failed to find matching redirect.'));
    }

    // Load the redirect based on url.
    $redirects = $repository->findBySourcePath('another-url');
    $redirect = array_shift($redirects);
    if (!empty($redirect)) {
      $this->assertEqual($redirect->getSourceUrl(), '/another-url?key1=val1');
    }
    else {
      $this->fail(t('Failed to find redirect by source path.'));
    }

    // Test passthrough_querystring.
    $redirect = $this->controller->create();
    $redirect->setSource('a-different-url');
    $redirect->setRedirect('node');
    $redirect->save();
    $redirect = $repository->findMatchingRedirect('a-different-url', ['key1' => 'val1'], 'de');
    if (!empty($redirect)) {
      $this->assertEqual($redirect->getSourceUrl(), '/a-different-url');
    }
    else {
      $this->fail('Failed to find redirect by source path with query string.');
    }

    // Add another redirect to the same path, with a query. This should always
    // be found before the source without a query set.
    /** @var \Drupal\redirect\Entity\Redirect $new_redirect */
    $new_redirect = $this->controller->create();
    $new_redirect->setSource('a-different-url', ['foo' => 'bar']);
    $new_redirect->setRedirect('node');
    $new_redirect->save();
    $found = $repository->findMatchingRedirect('a-different-url', ['foo' => 'bar'], 'de');
    if (!empty($found)) {
      $this->assertEqual($found->getSourceUrl(), '/a-different-url?foo=bar');
    }
    else {
      $this->fail('Failed to find a redirect by source path with query string.');
    }

    // Hashes should be case-insensitive since the source paths are.
    /** @var \Drupal\redirect\Entity\Redirect $redirect */
    $redirect = $this->controller->create();
    $redirect->setSource('Case-Sensitive-Path');
    $redirect->setRedirect('node');
    $redirect->save();
    $found = $repository->findBySourcePath('case-sensitive-path');
    if (!empty($found)) {
      $found = reset($found);
      $this->assertEqual($found->getSourceUrl(), '/Case-Sensitive-Path');
    }
    else {
      $this->fail('findBySourcePath is case sensitive');
    }
    $found = $repository->findMatchingRedirect('case-sensitive-path');
    if (!empty($found)) {
      $this->assertEqual($found->getSourceUrl(), '/Case-Sensitive-Path');
    }
    else {
      $this->fail('findMatchingRedirect is case sensitive.');
    }
  }

  /**
   * Test redirect_sort_recursive().
   */
  public function testSortRecursive() {
    $test_cases = array(
      array(
        'input' => array('b' => 'aa', 'c' => array('c2' => 'aa', 'c1' => 'aa'), 'a' => 'aa'),
        'expected' => array('a' => 'aa', 'b' => 'aa', 'c' => array('c1' => 'aa', 'c2' => 'aa')),
        'callback' => 'ksort',
      ),
    );
    foreach ($test_cases as $index => $test_case) {
      $output = $test_case['input'];
      redirect_sort_recursive($output, $test_case['callback']);
      $this->assertIdentical($output, $test_case['expected']);
    }
  }

  /**
   * Test loop detection.
   */
  public function testLoopDetection() {
    // Add a chained redirect that isn't a loop.
    /** @var \Drupal\redirect\Entity\Redirect $one */
    $one = $this->controller->create();
    $one->setSource('my-path');
    $one->setRedirect('node');
    $one->save();
    /** @var \Drupal\redirect\Entity\Redirect $two */
    $two = $this->controller->create();
    $two->setSource('second-path');
    $two->setRedirect('my-path');
    $two->save();
    /** @var \Drupal\redirect\Entity\Redirect $three */
    $three = $this->controller->create();
    $three->setSource('third-path');
    $three->setRedirect('second-path');
    $three->save();

    /** @var \Drupal\redirect\RedirectRepository $repository */
    $repository = \Drupal::service('redirect.repository');
    $found = $repository->findMatchingRedirect('third-path');
    if (!empty($found)) {
      $this->assertEqual($found->getRedirectUrl()->toString(), '/node', 'Chained redirects properly resolved in findMatchingRedirect.');
    }
    else {
      $this->fail('Failed to resolve a chained redirect.');
    }

    // Create a loop.
    $one->setRedirect('third-path');
    $one->save();
    try {
      $repository->findMatchingRedirect('third-path');
      $this->fail('Failed to detect a redirect loop.');
    }
    catch (RedirectLoopException $e) {
      $this->pass('Properly detected a redirect loop.');
    }
  }

  /**
   * Test redirect_parse_url().
   */
  public function testParseURL() {
    //$test_cases = array(
    //  array(
    //    'input' => array('b' => 'aa', 'c' => array('c2' => 'aa', 'c1' => 'aa'), 'a' => 'aa'),
    //    'expected' => array('a' => 'aa', 'b' => 'aa', 'c' => array('c1' => 'aa', 'c2' => 'aa')),
    //  ),
    //);
    //foreach ($test_cases as $index => $test_case) {
    //  $output = redirect_parse_url($test_case['input']);
    //  $this->assertIdentical($output, $test_case['expected']);
    //}
  }

  /**
   * Test multilingual redirects.
   */
  public function testMultilanguageCases() {
    // Add a redirect for english.
    /** @var \Drupal\redirect\Entity\Redirect $en_redirect */
    $en_redirect = $this->controller->create();
    $en_redirect->setSource('langpath');
    $en_redirect->setRedirect('/about');
    $en_redirect->setLanguage('en');
    $en_redirect->save();

    // Add a redirect for germany.
    /** @var \Drupal\redirect\Entity\Redirect $en_redirect */
    $en_redirect = $this->controller->create();
    $en_redirect->setSource('langpath');
    $en_redirect->setRedirect('node');
    $en_redirect->setLanguage('de');
    $en_redirect->save();

    // Check redirect for english.
    /** @var \Drupal\redirect\RedirectRepository $repository */
    $repository = \Drupal::service('redirect.repository');

    $found = $repository->findBySourcePath('langpath');
    if (!empty($found)) {
      $this->assertEqual($found[1]->getRedirectUrl()->toString(), '/about', 'Multilingual redirect resolved properly.');
      $this->assertEqual($found[1]->get('language')[0]->value, 'en', 'Multilingual redirect resolved properly.');
    }
    else {
      $this->fail('Failed to resolve the multilingual redirect.');
    }

    // Check redirect for germany.
    \Drupal::configFactory()->getEditable('system.site')->set('default_langcode', 'de')->save();
    /** @var \Drupal\redirect\RedirectRepository $repository */
    $repository = \Drupal::service('redirect.repository');
    $found = $repository->findBySourcePath('langpath');
    if (!empty($found)) {
      $this->assertEqual($found[2]->getRedirectUrl()->toString(), '/node', 'Multilingual redirect resolved properly.');
      $this->assertEqual($found[2]->get('language')[0]->value, 'de', 'Multilingual redirect resolved properly.');
    }
    else {
      $this->fail('Failed to resolve the multilingual redirect.');
    }
  }

}
