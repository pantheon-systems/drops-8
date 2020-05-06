<?php

namespace Drupal\webform_devel\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webform\Form\WebformEntityAjaxFormTrait;
use Drupal\webform\Utility\WebformDialogHelper;
use Drupal\webform_devel\WebformDevelSchemaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Get webform schema.
 */
class WebformDevelEntitySchemaForm extends EntityForm {

  use WebformEntityAjaxFormTrait;

  /**
   * The webform devel scheme service.
   *
   * @var \Drupal\webform_devel\WebformDevelSchemaInterface
   */
  protected $scheme;

  /**
   * Constructs a WebformDevelEntitySchemaForm.
   *
   * @param \Drupal\webform_devel\WebformDevelSchemaInterface $webform_devel_scheme
   *   The webform devel scheme service.
   */
  public function __construct(WebformDevelSchemaInterface $webform_devel_scheme) {
    $this->scheme = $webform_devel_scheme;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('webform_devel.schema')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $webform_ui_exists = $this->moduleHandler->moduleExists('webform_ui');

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();

    // Header.
    $header = $this->scheme->getColumns();
    if ($webform_ui_exists) {
      $header['operations'] = $this->t('Operations');
    }

    // Rows.
    $rows = [];
    $elements = $this->scheme->getElements($webform);
    foreach ($elements as $element_key => $element) {
      $rows[$element_key] = [];

      foreach ($element as $key => $value) {
        if ($key === 'options') {
          $value = implode('; ', array_slice($value, 0, 12)) . (count($value) > 12 ? '; …' : '');
        }
        $rows[$element_key][$key] = ['#markup' => $value];
      }

      if ($element['datatype'] == 'Composite') {
        $rows[$element_key]['#attributes']['class'][] = 'webform-devel-schema-composite';
      }

      if ($webform_ui_exists) {
        // Only add 'Edit' link to main element and not composite sub-elements.
        if (strpos($element_key, '.') === FALSE) {
          $element_url = new Url(
            'entity.webform_ui.element.edit_form',
            ['webform' => $webform->id(), 'key' => $element_key],
            // Get destination without any Ajax wrapper parameters.
            ['query' => ['destination' => Url::fromRoute('<current>')->toString()]]
          );
          $rows[$element_key]['name'] = [
            '#type' => 'link',
            '#title' => $element_key,
            '#url' => $element_url,
            '#attributes' => WebformDialogHelper::getModalDialogAttributes(),
          ];
          $rows[$element_key]['operations'] = [
            '#type' => 'link',
            '#title' => $this->t('Edit'),
            '#url' => $element_url,
            '#attributes' => WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NORMAL, ['button', 'button--small']),
          ];
        }
        else {
          $rows[$element_key]['operations'] = ['#markup' => ''];
        }

        // Add webform key used by Ajax callback.
        $rows[$element_key]['#attributes']['data-webform-key'] = explode('.', $element_key)[0];
      }
    }

    // Table.
    $form['schema'] = [
      '#type' => 'table',
      '#header' => $header,
      '#attributes' => ['class' => ['webform-devel-schema-table']],
    ] + $rows;

    WebformDialogHelper::attachLibraries($form);

    $form['#attached']['library'][] = 'webform_devel/webform_devel';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actionsElement(array $form, FormStateInterface $form_state) {
    $actions = parent::actionsElement($form, $form_state);
    unset($actions['delete']);
    $actions['submit']['#value'] = $this->t('Export');
    $actions['reset']['#attributes']['style'] = 'display: none';
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('webform_devel.schema.export', ['webform' => $this->getEntity()->id()]);
  }

}
