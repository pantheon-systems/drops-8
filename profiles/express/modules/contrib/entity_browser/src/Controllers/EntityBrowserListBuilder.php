<?php

namespace Drupal\entity_browser\Controllers;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for entity browser.
 *
 * @ingroup entity_browser
 */
class EntityBrowserListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the entity browser list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\entity_browser\Entity\EntityBrowser */
    $row['id'] = $entity->id();
    $row['name'] = $entity->label();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    // Destination parameter messes up with the entity form wizard redirects.
    $options = $operations['edit']['url']->getOptions();
    if (!empty($options['query']['destination'])) {
      unset($options['query']['destination']);
    }
    $operations['edit']['url']->setOptions($options);

    return $operations;
  }

}
