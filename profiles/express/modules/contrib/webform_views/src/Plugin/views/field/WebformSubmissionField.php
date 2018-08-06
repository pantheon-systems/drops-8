<?php

namespace Drupal\webform_views\Plugin\views\field;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform submission field.
 *
 * @ViewsField("webform_submission_field")
 */
class WebformSubmissionField extends FieldPluginBase {

  /**
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var WebformElementManagerInterface
   */
  protected $webformElementManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.webform.element')
    );
  }

  /**
   * WebformSubmissionField constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, WebformElementManagerInterface $webform_element_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->webformElementManager = $webform_element_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['webform_element_format'] = ['default' => ''];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['webform_element_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Format'),
      '#description' => $this->t('Specify how to format this value.'),
      '#options' => $this->getWebformElementPlugin()->getItemFormats(),
      '#default_value' => $this->options['webform_element_format'] ?: $this->getWebformElementPlugin()->getItemDefaultFormat(),
    ];

    $form['webform_element_format']['#access'] = !empty($form['webform_element_format']['#options']);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    if ($values->_entity->access('view')) {
      /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
      $element_manager = \Drupal::service('plugin.manager.webform.element');

      /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
      $webform_submission = $values->_entity;
      $webform = $webform_submission->getWebform();

      // Get format and element key.
      $format = $this->options['webform_element_format'];
      $element_key = $this->definition['webform_submission_field'];

      // Get element and element handler plugin.
      $element = $webform->getElement($element_key,TRUE);
      if (!$element) {
        return [];
      }

      // Set the format.
      $element['#format'] = $format;

      // Get element handler and get the element's HTML render array.
      $element_handler = $element_manager->getElementInstance($element);
      return $element_handler->formatHtml($element, $webform_submission);
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Since we will render the field off webform_submission entity, there is
    // no need to join any table nor include any fields in the select.
  }

  /**
   * {@inheritdoc}
   */
  public function clickSort($order) {
    $webform_submission_data_alias = $this->ensureMyTable();
    $params = $this->options['group_type'] != 'group' ? ['function' => $this->options['group_type']] : [];
    $this->query->addOrderBy($webform_submission_data_alias, 'value', $order, '', $params);
  }

  /**
   * Retrieve webform element plugin instance.
   *
   * @return \Drupal\webform\Plugin\WebformElementInterface
   *   Webform element plugin instance that corresponds to the webform element
   *   of this view field
   */
  protected function getWebformElementPlugin() {
    $webform = $this->entityTypeManager->getStorage('webform')->load($this->definition['webform_id']);
    $element = $webform->getElement($this->definition['webform_submission_field']);
    return $this->webformElementManager->getElementInstance($element);
  }

}
