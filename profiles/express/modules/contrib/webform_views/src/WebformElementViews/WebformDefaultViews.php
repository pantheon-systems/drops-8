<?php

namespace Drupal\webform_views\WebformElementViews;

use Drupal\Component\Utility\Html;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webform\Plugin\WebformElementInterface;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default webform views handler for webform elements.
 */
class WebformDefaultViews extends WebformElementViewsAbstract {

  /**
   * {@inheritdoc}
   */
  public function getElementViewsData(WebformElementInterface $element_plugin, array $element) {
    $views_data = parent::getElementViewsData($element_plugin, $element);

    if ($element_plugin->isInput($element) && !$element_plugin->hasMultipleValues($element)) {
      $views_data['sort'] = [
        'id' => 'webform_submission_field_sort',
        'real field' => 'value',
      ];
    }

    if ($element_plugin->isInput($element) && !$element_plugin->hasMultipleValues($element)) {
      $views_data['filter'] = [
        'id' => 'webform_submission_field_filter',
        'real field' => 'value',
      ];
    }

    return $views_data;
  }

}
