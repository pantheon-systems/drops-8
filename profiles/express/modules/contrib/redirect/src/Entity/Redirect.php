<?php

namespace Drupal\redirect\Entity;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\link\LinkItemInterface;

/**
 * The redirect entity class.
 *
 * @ContentEntityType(
 *   id = "redirect",
 *   label = @Translation("Redirect"),
 *   bundle_label = @Translation("Redirect type"),
 *   handlers = {
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "form" = {
 *       "default" = "Drupal\redirect\Form\RedirectForm",
 *       "delete" = "Drupal\redirect\Form\RedirectDeleteForm",
 *       "edit" = "Drupal\redirect\Form\RedirectForm"
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "storage_schema" = "\Drupal\redirect\RedirectStorageSchema"
 *   },
 *   base_table = "redirect",
 *   translatable = FALSE,
 *   admin_permission = "administer redirects",
 *   entity_keys = {
 *     "id" = "rid",
 *     "label" = "redirect_source",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "langcode" = "language",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/search/redirect/edit/{redirect}",
 *     "delete-form" = "/admin/config/search/redirect/delete/{redirect}",
 *     "edit-form" = "/admin/config/search/redirect/edit/{redirect}",
 *   }
 * )
 */
class Redirect extends ContentEntityBase {

  /**
   * Generates a unique hash for identification purposes.
   *
   * @param string $source_path
   *   Source path of the redirect.
   * @param array $source_query
   *   Source query as an array.
   * @param string $language
   *   Redirect language.
   *
   * @return string
   *   Base 64 hash.
   */
  public static function generateHash($source_path, array $source_query, $language) {
    $hash = array(
      'source' => Unicode::strtolower($source_path),
      'language' => $language,
    );

    if (!empty($source_query)) {
      $hash['source_query'] = $source_query;
    }
    redirect_sort_recursive($hash, 'ksort');
    return Crypt::hashBase64(serialize($hash));
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    $values += array(
      'type' => 'redirect',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage_controller) {
    $this->set('hash', Redirect::generateHash($this->redirect_source->path, (array) $this->redirect_source->query, $this->language()->getId()));
  }

  /**
   * Sets the redirect language.
   *
   * @param string $language
   *   Language code.
   */
  public function setLanguage($language) {
    $this->set('language', $language);
  }

  /**
   * Sets the redirect status code.
   *
   * @param int $status_code
   *   The redirect status code.
   */
  public function setStatusCode($status_code) {
    $this->set('status_code', $status_code);
  }

  /**
   * Gets the redirect status code.
   *
   * @return int
   *   The redirect status code.
   */
  public function getStatusCode() {
    return $this->get('status_code')->value;
  }

  /**
   * Sets the redirect created datetime.
   *
   * @param int $datetime
   *   The redirect created datetime.
   */
  public function setCreated($datetime) {
    $this->set('created', $datetime);
  }

  /**
   * Gets the redirect created datetime.
   *
   * @return int
   *   The redirect created datetime.
   */
  public function getCreated() {
    return $this->get('created')->value;
  }

  /**
   * Sets the source URL data.
   *
   * @param string $path
   *   The base url of the source.
   * @param array $query
   *   Query arguments.
   */
  public function setSource($path, array $query = array()) {
    $this->redirect_source->set(0, ['path' => ltrim($path, '/'), 'query' => $query]);
  }

  /**
   * Gets the source URL data.
   *
   * @return array
   */
  public function getSource() {
    return $this->get('redirect_source')->get(0)->getValue();
  }

  /**
   * Gets the source base URL.
   *
   * @return string
   */
  public function getSourceUrl() {
    return $this->get('redirect_source')->get(0)->getUrl()->toString();
  }

  /**
   * Gets the source URL path with its query.
   *
   * @return string
   *   The source URL path, eventually with its query.
   */
  public function getSourcePathWithQuery() {
    $path = '/' . $this->get('redirect_source')->path;
    if ($this->get('redirect_source')->query) {
      $path .= '?' . UrlHelper::buildQuery($this->get('redirect_source')->query);
    }
    return $path;
  }

  /**
   * Gets the redirect URL data.
   *
   * @return array
   *   The redirect URL data.
   */
  public function getRedirect() {
    return $this->get('redirect_redirect')->get(0)->getValue();
  }

  /**
   * Sets the redirect destination URL data.
   *
   * @param string $url
   *   The base url of the redirect destination.
   * @param array $query
   *   Query arguments.
   * @param array $options
   *   The source url options.
   */
  public function setRedirect($url, array $query = array(), array $options = array()) {
    $uri = $url . ($query ? '?' . UrlHelper::buildQuery($query) : '');
    $this->redirect_redirect->set(0, ['uri' => 'internal:/' . ltrim($uri, '/'), 'options' => $options]);
  }

  /**
   * Gets the redirect URL.
   *
   * @return \Drupal\Core\Url
   *   The redirect URL.
   */
  public function getRedirectUrl() {
    return $this->get('redirect_redirect')->get(0)->getUrl();
  }

  /**
   * Gets the redirect URL options.
   *
   * @return array
   *   The redirect URL options.
   */
  public function getRedirectOptions() {
    return $this->get('redirect_redirect')->options;
  }

  /**
   * Gets a specific redirect URL option.
   *
   * @param string $key
   *   Option key.
   * @param mixed $default
   *   Default value used in case option does not exist.
   *
   * @return mixed
   *   The option value.
   */
  public function getRedirectOption($key, $default = NULL) {
    $options = $this->getRedirectOptions();
    return isset($options[$key]) ? $options[$key] : $default;
  }

  /**
   * Gets the current redirect entity hash.
   *
   * @return string
   *   The hash.
   */
  public function getHash() {
    return $this->get('hash')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['rid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Redirect ID'))
      ->setDescription(t('The redirect ID.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The record UUID.'))
      ->setReadOnly(TRUE);

    $fields['hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Hash'))
      ->setSetting('max_length', 64)
      ->setDescription(t('The redirect hash.'));

    $fields['type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Type'))
      ->setDescription(t('The redirect type.'));

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User ID'))
      ->setDescription(t('The user ID of the node author.'))
      ->setDefaultValueCallback('\Drupal\redirect\Entity\Redirect::getCurrentUserId')
      ->setSettings(array(
        'target_type' => 'user',
      ));

    $fields['redirect_source'] = BaseFieldDefinition::create('redirect_source')
      ->setLabel(t('From'))
      ->setDescription(t("Enter an internal Drupal path or path alias to redirect (e.g. %example1 or %example2). Fragment anchors (e.g. %anchor) are <strong>not</strong> allowed.", array('%example1' => 'node/123', '%example2' => 'taxonomy/term/123', '%anchor' => '#anchor')))
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setDisplayOptions('form', array(
        'type' => 'redirect_link',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['redirect_redirect'] = BaseFieldDefinition::create('link')
      ->setLabel(t('To'))
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setSettings(array(
        'link_type' => LinkItemInterface::LINK_GENERIC,
        'title' => DRUPAL_DISABLED
      ))
      ->setDisplayOptions('form', array(
        'type' => 'link',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['language'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language'))
      ->setDescription(t('The redirect language.'))
      ->setDisplayOptions('form', array(
        'type' => 'language_select',
        'weight' => 2,
      ));

    $fields['status_code'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Status code'))
      ->setDescription(t('The redirect status code.'))
      ->setDefaultValue(0);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The date when the redirect was created.'));
    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return array(\Drupal::currentUser()->id());
  }

}
