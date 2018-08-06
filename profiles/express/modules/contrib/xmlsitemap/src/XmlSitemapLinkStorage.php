<?php

namespace Drupal\xmlsitemap;

use Drupal\Core\Database\Query\Merge;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AnonymousUserSession;

/**
 * XmlSitemap link storage service class.
 */
class XmlSitemapLinkStorage implements XmlSitemapLinkStorageInterface {

  /**
   * The state store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The anonymous user object.
   *
   * @var \Drupal\Core\Session\AnonymousUserSession
   */
  protected $anonymousUser;

  /**
   * Constructs a XmlSitemapLinkStorage object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state handler.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(StateInterface $state, ModuleHandlerInterface $module_handler) {
    $this->state = $state;
    $this->moduleHandler = $module_handler;
    $this->anonymousUser = new AnonymousUserSession();
  }

  public function create(EntityInterface $entity) {
    if (!isset($entity->xmlsitemap)) {
      $entity->xmlsitemap = array();
      if ($entity->id() && $link = $this->load($entity->getEntityTypeId(), $entity->id())) {
        $entity->xmlsitemap = $link;
      }
    }

    $settings = xmlsitemap_link_bundle_load($entity->getEntityTypeId(), $entity->bundle());
    $uri = $entity->url();
    $entity->xmlsitemap += array(
      'type' => $entity->getEntityTypeId(),
      'id' => (string) $entity->id(),
      'subtype' => $entity->bundle(),
      'status' => $settings['status'],
      'status_default' => $settings['status'],
      'status_override' => 0,
      'priority' => $settings['priority'],
      'priority_default' => $settings['priority'],
      'priority_override' => 0,
      'changefreq' => isset($settings['changefreq']) ? $settings['changefreq'] : 0,
    );

    if (method_exists($entity, 'getChangedTime')) {
      $entity->xmlsitemap['lastmod'] = $entity->getChangedTime();
    }

    $url = $entity->url();
    // The following values must always be checked because they are volatile.
    $entity->xmlsitemap['loc'] = $uri;
    $entity->xmlsitemap['access'] = isset($url) && $entity->access('view', $this->anonymousUser);
    $language = $entity->language();
    $entity->xmlsitemap['language'] = !empty($language) ? $language->getId() : LanguageInterface::LANGCODE_NOT_SPECIFIED;

    return $entity->xmlsitemap;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $link) {
    $link += array(
      'access' => 1,
      'status' => 1,
      'status_override' => 0,
      'lastmod' => 0,
      'priority' => XMLSITEMAP_PRIORITY_DEFAULT,
      'priority_override' => 0,
      'changefreq' => 0,
      'changecount' => 0,
      'language' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    );

    // Allow other modules to alter the link before saving.
    $this->moduleHandler->alter('xmlsitemap_link', $link);

    // Temporary validation checks.
    // @todo Remove in final?
    if ($link['priority'] < 0 || $link['priority'] > 1) {
      trigger_error(t('Invalid sitemap link priority %priority.<br />@link', array('%priority' => $link['priority'], '@link' => var_export($link, TRUE))), E_USER_ERROR);
    }
    if ($link['changecount'] < 0) {
      trigger_error(t('Negative changecount value. Please report this to <a href="@516928">@516928</a>.<br />@link', array('@516928' => 'http://drupal.org/node/516928', '@link' => var_export($link, TRUE))), E_USER_ERROR);
      $link['changecount'] = 0;
    }

    // Check if this is a changed link and set the regenerate flag if necessary.
    if (!$this->state->get('xmlsitemap_regenerate_needed')) {
      $this->checkChangedLink($link, NULL, TRUE);
    }

    $queryStatus = \Drupal::database()->merge('xmlsitemap')
      ->key(array('type' => $link['type'], 'id' => $link['id']))
      ->fields(array(
        'loc' => $link['loc'],
        'subtype' => $link['subtype'],
        'access' => (int) $link['access'],
        'status' => (int) $link['status'],
        'status_override' => $link['status_override'],
        'lastmod' => $link['lastmod'],
        'priority' => $link['priority'],
        'priority_override' => $link['priority_override'],
        'changefreq' => $link['changefreq'],
        'changecount' => $link['changecount'],
        'language' => $link['language'],
      ))
      ->execute();

    switch($queryStatus)
    {
      case Merge::STATUS_INSERT:
        $this->moduleHandler->invokeAll('xmlsitemap_link_insert', array($link));
        break;

      case Merge::STATUS_UPDATE:
        $this->moduleHandler->invokeAll('xmlsitemap_link_update', array($link));
        break;
    }

    return $link;
  }

  /**
   * {@inheritdoc}
   */
  public function checkChangedLink(array $link, $original_link = NULL, $flag = FALSE) {
    $changed = FALSE;

    if ($original_link === NULL) {
      // Load only the fields necessary for data to be changed in the sitemap.
      $original_link = db_query_range("SELECT loc, access, status, lastmod, priority, changefreq, changecount, language FROM {xmlsitemap} WHERE type = :type AND id = :id", 0, 1, array(':type' => $link['type'], ':id' => $link['id']))->fetchAssoc();
    }

    if (!$original_link) {
      if ($link['access'] && $link['status']) {
        // Adding a new visible link.
        $changed = TRUE;
      }
    }
    else {
      if (!($original_link['access'] && $original_link['status']) && $link['access'] && $link['status']) {
        // Changing a non-visible link to a visible link.
        $changed = TRUE;
      }
      elseif ($original_link['access'] && $original_link['status'] && array_diff_assoc($original_link, $link)) {
        // Changing a visible link
        $changed = TRUE;
      }
    }

    if ($changed && $flag) {
      $this->state->set('xmlsitemap_regenerate_needed', TRUE);
    }

    return $changed;
  }

  /**
   * {@inheritdoc}
   */
  public function checkChangedLinks(array $conditions = array(), array $updates = array(), $flag = FALSE) {
    // If we are changing status or access, check for negative current values.
    $conditions['status'] = (!empty($updates['status']) && empty($conditions['status'])) ? 0 : 1;
    $conditions['access'] = (!empty($updates['access']) && empty($conditions['access'])) ? 0 : 1;

    $query = db_select('xmlsitemap');
    $query->addExpression('1');
    foreach ($conditions as $field => $value) {
      $query->condition($field, $value);
    }
    $query->range(0, 1);
    $changed = $query->execute()->fetchField();

    if ($changed && $flag) {
      $this->state->set('xmlsitemap_regenerate_needed', TRUE);
    }

    return $changed;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($entity_type, $entity_id) {
    $conditions = array('type' => $entity_type, 'id' => $entity_id);
    return $this->deleteMultiple($conditions);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $conditions) {
    if (!$this->state->get('xmlsitemap_regenerate_needed')) {
      $this->checkChangedLinks($conditions, array(), TRUE);
    }

    // @todo Add a hook_xmlsitemap_link_delete() hook invoked here.
    $query = db_delete('xmlsitemap');
    foreach ($conditions as $field => $value) {
      $query->condition($field, $value);
    }

    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function updateMultiple($updates = array(), $conditions = array(), $check_flag = TRUE) {
    // If we are going to modify a visible sitemap link, we will need to set
    // the regenerate needed flag.
    if ($check_flag && !$this->state->get('xmlsitemap_regenerate_needed')) {
      $this->checkChangedLinks($conditions, $updates, TRUE);
    }

    // Process updates.
    $query = db_update('xmlsitemap');
    $query->fields($updates);
    foreach ($conditions as $field => $value) {
      $query->condition($field, $value);
    }

    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function load($entity_type, $entity_id) {
    $link = $this->loadMultiple(array('type' => $entity_type, 'id' => $entity_id));
    return $link ? reset($link) : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $conditions = array()) {
    $query = db_select('xmlsitemap');
    $query->fields('xmlsitemap');

    foreach ($conditions as $field => $value) {
      $query->condition($field, $value);
    }

    $links = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);

    return $links;
  }

}
