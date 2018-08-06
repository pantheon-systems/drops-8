<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Preprocess\BootstrapDropdown.
 */

namespace Drupal\bootstrap\Plugin\Preprocess;

use Drupal\bootstrap\Annotation\BootstrapPreprocess;
use Drupal\bootstrap\Utility\Element;
use Drupal\bootstrap\Utility\Unicode;
use Drupal\bootstrap\Utility\Variables;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Url;

/**
 * Pre-processes variables for the "bootstrap_dropdown" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("bootstrap_dropdown")
 */
class BootstrapDropdown extends PreprocessBase implements PreprocessInterface {

  /**
   * {@inheritdoc}
   */
  protected function preprocessVariables(Variables $variables) {
    $this->preprocessLinks($variables);

    $toggle = Element::create($variables->toggle);
    $toggle->setProperty('split', $variables->split);

    // Convert the items into a proper item list.
    $variables->items = [
      '#theme' => 'item_list__dropdown',
      '#alignment' => $variables->alignment,
      '#items' => $variables->items,
    ];

    // Ensure all attributes are proper objects.
    $this->preprocessAttributes();
  }

  /**
   * Preprocess links in the variables array to convert them from dropbuttons.
   *
   * @param \Drupal\bootstrap\Utility\Variables $variables
   *   A variables object.
   */
  protected function preprocessLinks(Variables $variables) {
    // Convert "dropbutton" theme suggestion variables.
    if (Unicode::strpos($variables->theme_hook_original, 'links__dropbutton') !== FALSE && !empty($variables->links)) {
      $operations = !!Unicode::strpos($variables->theme_hook_original, 'operations');

      // Normal dropbutton links are not actually render arrays, convert them.
      foreach ($variables->links as &$element) {
        if (isset($element['title']) && $element['url']) {
          // Preserve query parameters (if any)
          if (!empty($element['query'])) {
            $url_query = $element['url']->getOption('query') ?: [];
            $element['url']->setOption('query', NestedArray::mergeDeep($url_query , $element['query']));
          }

          // Build render array.
          $element = [
            '#type' => 'link',
            '#title' => $element['title'],
            '#url' => $element['url'],
          ];
        }
      }

      $items = Element::createStandalone();

      $primary_action = NULL;
      $links = Element::create($variables->links);

      // Iterate over all provided "links". The array may be associative, so
      // this cannot rely on the key to be numeric, it must be tracked manually.
      $i = -1;
      foreach ($links->children(TRUE) as $key => $child) {
        $i++;

        // The first item is always the "primary link".
        if ($i === 0) {
          // Must generate an ID for this child because the toggle will use it.
          $child->getProperty('id', $child->getAttribute('id', Html::getUniqueId('dropdown-item')));
          $primary_action = $child->addClass('hidden');
        }

        // If actually a "link", add it to the items array directly.
        if ($child->isType('link')) {
          $items->$key->link = $child->getArrayCopy();
        }
        // Otherwise, convert into a proper link.
        else {
          // Hide the original element
          $items->$key->element = $child->addClass('hidden')->getArrayCopy();

          // Retrieve any set HTML identifier for the link, generating a new
          // one if necessary.
          $id = $child->getProperty('id', $child->getAttribute('id', Html::getUniqueId('dropdown-item')));
          $items->$key->link = Element::createStandalone([
            '#type' => 'link',
            '#title' => $child->getProperty('value', $child->getProperty('title', $child->getProperty('text'))),
            '#url' => Url::fromUserInput('#'),
            '#attributes' => ['data-dropdown-target' => "#$id"],
          ]);

          // Also hide the real link if it's the primary action.
          if ($i === 0) {
            $items->$key->link->addClass('hidden');
          }
        }
      }

      // Create a toggle button, extracting relevant info from primary action.
      $toggle = Element::createStandalone([
        '#type' => 'button',
        '#attributes' => $primary_action->getAttributes()->getArrayCopy(),
        '#value' => $primary_action->getProperty('value', $primary_action->getProperty('title', $primary_action->getProperty('text'))),
      ]);

      // Remove the "hidden" class that was added to the primary action.
      $toggle->removeClass('hidden')->removeAttribute('id')->setAttribute('data-dropdown-target', '#' . $primary_action->getAttribute('id'));

      // Make operations smaller.
      if ($operations) {
        $toggle->setButtonSize('btn-xs', FALSE);
      }

      // Add the toggle render array to the variables.
      $variables->toggle = $toggle->getArrayCopy();

      // Determine if toggle should be a split button.
      $variables->split = count($items) > 1;

      // Add the items variable for "bootstrap_dropdown".
      $variables->items = $items->getArrayCopy();

      // Remove the unnecessary "links" variable now.
      unset($variables->links);
    }
  }

}
