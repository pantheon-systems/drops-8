<?php

namespace Drupal\webform\Tests;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\FileStorage;
use Drupal\webform\WebformInterface;

/**
 * Trait class for Webform tests.
 *
 * Below are helper methods are shared by SimpleTest and PHPUnit.
 *
 * @see \Drupal\webform\Tests\WebformTestBase
 * @see \Drupal\Tests\webform\FunctionalJavascript\WebformJavaScriptTestBase
 */
trait WebformTestTrait {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->loadWebforms(static::$testWebforms);
  }

  /****************************************************************************/
  // Webform.
  /****************************************************************************/

  /**
   * Lazy load a test webforms.
   *
   * @param array $ids
   *   Webform ids.
   */
  protected function loadWebforms(array $ids) {
    foreach ($ids as $id) {
      $this->loadWebform($id);
    }
    $this->pass(new FormattableMarkup('Loaded webforms: %webforms.', [
      '%webforms' => implode(', ', $ids),
    ]));
  }

  /**
   * Lazy load a test webform.
   *
   * @param string $id
   *   Webform id.
   *
   * @return \Drupal\webform\WebformInterface|null
   *   A webform.
   *
   * @see \Drupal\views\Tests\ViewTestData::createTestViews
   */
  protected function loadWebform($id) {
    $storage = \Drupal::entityTypeManager()->getStorage('webform');
    if ($webform = $storage->load($id)) {
      return $webform;
    }
    else {
      $config_name = 'webform.webform.' . $id;
      if (strpos($id, 'test_') === 0) {
        $config_directory = drupal_get_path('module', 'webform') . '/tests/modules/webform_test/config/install';
      }
      elseif (strpos($id, 'example_') === 0) {
        $config_directory = drupal_get_path('module', 'webform') . '/modules/webform_examples/config/install';
      }
      elseif (strpos($id, 'template_') === 0) {
        $config_directory = drupal_get_path('module', 'webform') . '/modules/webform_templates/config/install';
      }
      else {
        throw new \Exception("Webform $id not valid");
      }

      if (!file_exists("$config_directory/$config_name.yml")) {
        throw new \Exception("Webform $id does not exist in $config_directory");
      }

      $file_storage = new FileStorage($config_directory);
      $values = $file_storage->read($config_name);
      $webform = $storage->create($values);
      $webform->save();
      return $webform;
    }
  }

  /****************************************************************************/
  // Submission.
  /****************************************************************************/

  /**
   * Load the specified webform submission from the storage.
   *
   * @param int $sid
   *   The submission identifier.
   *
   * @return \Drupal\webform\WebformSubmissionInterface
   *   The loaded webform submission.
   */
  protected function loadSubmission($sid) {
    /** @var \Drupal\webform\WebformSubmissionStorage $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage('webform_submission');
    $storage->resetCache([$sid]);
    return $storage->load($sid);
  }

  /**
   * Purge all submission before the webform.module is uninstalled.
   */
  protected function purgeSubmissions() {
    \Drupal::database()->query('DELETE FROM {webform_submission}');
  }

  /**
   * Get a webform's submit button label.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param string $submit
   *   Value of the submit button whose click is to be emulated.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The webform's submit button label.
   */
  protected function getWebformSubmitButtonLabel(WebformInterface $webform, $submit = NULL) {
    if ($submit) {
      return $submit;
    }

    $actions_element = $webform->getElement('actions');
    if ($actions_element && isset($actions_element['#submit__label'])) {
      return $actions_element['#submit__label'];
    }

    return t('Submit');
  }

  /**
   * Get the last submission id.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return int|null
   *   The last submission id. NULL is saving of results is disabled.
   */
  protected function getLastSubmissionId(WebformInterface $webform) {
    if ($webform->getSetting('results_disabled')) {
      return NULL;
    }

    // Get submission sid.
    $url = UrlHelper::parse($this->getUrl());
    if (isset($url['query']['sid'])) {
      return $url['query']['sid'];
    }
    else {
      $entity_ids = \Drupal::entityQuery('webform_submission')
        ->sort('sid', 'DESC')
        ->condition('webform_id', $webform->id())
        ->execute();
      return reset($entity_ids);
    }
  }

}
