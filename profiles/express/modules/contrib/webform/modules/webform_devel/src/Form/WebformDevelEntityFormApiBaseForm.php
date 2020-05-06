<?php

namespace Drupal\webform_devel\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Render\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Export a webform's element to Form API (FAPI).
 */
abstract class WebformDevelEntityFormApiBaseForm extends EntityForm {

  /**
   * The archiver manager.
   *
   * @var \Drupal\Core\Archiver\ArchiverManager
   */
  protected $archiverManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The webform submission generator service.
   *
   * @var \Drupal\webform\WebformSubmissionGenerateInterface
   */
  protected $generate;

  /**
   * The webform token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * The webform element (plugin) manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * An array of translatable properties.
   *
   * @var array
   */
  protected $translatableProperties;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->archiverManager = $container->get('plugin.manager.archiver');
    $instance->renderer = $container->get('renderer');
    $instance->generate = $container->get('webform_submission.generate');
    $instance->tokenManager = $container->get('webform.token_manager');
    $instance->elementManager = $container->get('plugin.manager.webform.element');
    $instance->initialize();
    return $instance;
  }

  /**
   * Initialize WebformDevelEntityFormApiBaseForm object.
   */
  protected function initialize() {
    $translatable_properties = $this->elementManager->getTranslatableProperties();
    $translatable_properties = array_combine($translatable_properties, $translatable_properties);
    unset($translatable_properties['default_value']);
    $this->translatableProperties = $translatable_properties;
  }

  /****************************************************************************/
  // Helper functions.
  /****************************************************************************/

  /**
   * Cleanup webform elements.
   *
   * @param array $elements
   *   An render array representing elements.
   */
  protected function cleanupElements(array &$elements) {
    foreach ($elements as $element_key => $element) {
      if (isset($element['#type'])) {
        switch ($element['#type']) {
          // Remove unsupported element types.
          case 'webform_actions':
            unset($elements[$element_key]);
            break;

          // Convert wizard pages to fieldset.
          case 'webform_wizard':
            $element['#type'] = 'fieldset';
            break;
        }
      }

      // Recursively cleanup child elements.
      if (Element::child($element_key) && is_array($element)) {
        $this->cleanupElements($element);
      }
    }
  }

  /**
   * Export a PHP render array.
   *
   * @param array $form
   *   A form.
   * @param string $prefix
   *   The render arrays prefix.
   *
   * @return string
   *   Returns the variable representation of the render array.
   */
  protected function renderExport(array $form, $prefix = '$form') {
    $output = '';
    foreach ($form as $element_key => $element) {
      $element_prefix = $prefix . "['" . $element_key . "']";

      if (!is_array($element)) {
        $output .= $element_prefix . '[' . $element_key . '] = ' . var_export($element, TRUE) . ';' . PHP_EOL;
      }
      elseif ($prefix === '$form' && !Element::child($element_key)) {
        $output .= $element_prefix . ' = ' . $this->varExport($element, TRUE) . ';' . PHP_EOL;
      }
      else {
        $element_plugin = (is_array($element)) ? $this->elementManager->getElementInstance($element) : NULL;
        $element_children = [];
        $element_export = [];
        foreach ($element as $property => $value) {
          if (Element::child($property)) {
            $element_children[$property] = $value;
          }
          elseif ($this->isPropertyTranslatable($property)) {
            $element_export[$property] = $this->wrapTranslatableValue($value);
          }
          else {
            $element_export[$property] = $value;
          }
        }

        // Add comment for main container element.
        if ($prefix === '$form' && $element_plugin && $element_plugin->isContainer($element)) {
          $output .= PHP_EOL . '// ' . $element_plugin->getAdminLabel($element) . '.' . PHP_EOL;
        }
        $output .= $element_prefix . ' = ' . $this->varExport($element_export, TRUE) . ';' . PHP_EOL;
        $output .= $this->renderExport($element_children, $element_prefix);
      }
    }

    $output = str_replace("'<T>", "\$this->t('", $output);
    $output = str_replace("</T>'", "')", $output);
    return $output;
  }

  /**
   * Wrap translatable value in <T> tags.
   *
   * @param mixed $value
   *   A translatable value.
   *
   * @return array|string
   *   A translatable value in <T> tags.
   */
  protected function wrapTranslatableValue($value) {
    if (is_array($value)) {
      foreach ($value as $key => $item) {
        $value[$key] = $this->wrapTranslatableValue($item);
      }
      return $value;
    }
    else {
       return '<T>' . $value . '</T>';
    }
  }

  /**
   * Determine if an element property is translatable.
   *
   * @param string $property
   *   An element property.
   *
   * @return bool
   *   TRUE if an element property is translatable.
   */
  protected function isPropertyTranslatable($property) {
    $property = str_replace('#', '', $property);
    if (strpos($property, '__') !== FALSE) {
      list(, $child_property) = explode('__', $property);
      return isset($this->translatableProperties[$child_property]);
    }
    else {
      return isset($this->translatableProperties[$property]);
    }
  }

  /**
   * Outputs string representation of a variable using array short syntax.
   *
   * @param mixed $expression
   *   The variable you want to export.
   * @param bool $return
   *   If used and set to TRUE, var_export() will return the variable
   *   representation instead of outputting it.
   *
   * @return string
   *   Returns the variable representation when the return parameter is used and
   *   evaluates to TRUE. Otherwise, this function will return NULL.
   */
  protected function varExport($expression, $return = FALSE) {
    // Export variable using array short syntax.
    // @see https://gist.github.com/Bogdaan/ffa287f77568fcbb4cffa0082e954022
    $export = var_export($expression, TRUE);
    $export = preg_replace("/^([ ]*)(.*)/m", '$1$1$2', $export);
    $array = preg_split("/\r\n|\n|\r/", $export);
    $array = preg_replace(
      ["/\s*array\s\($/", "/\)(,)?$/", "/\s=>\s$/"],
      [NULL, ']$1', ' => ['],
      $array
    );
    $export = implode(PHP_EOL, array_filter(["["] + $array));

    // Clean up output to match Drupal coding guidelines.
    $export = str_replace('    ', '  ', $export);
    $export = str_replace('=> true,', '=> TRUE,', $export);
    $export = str_replace('=> false,', '=> FALSE,', $export);
    $export = preg_replace('/\d+ => /', '', $export);

    if ($return) {
      return $export;
    }
    else {
      echo $export;
    }
  }

}
