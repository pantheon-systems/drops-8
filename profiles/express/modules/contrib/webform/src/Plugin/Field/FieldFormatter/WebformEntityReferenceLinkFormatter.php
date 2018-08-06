<?php

namespace Drupal\webform\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Utility\Token;
use Drupal\webform\WebformMessageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'Link to webform' formatter.
 *
 * @FieldFormatter(
 *   id = "webform_entity_reference_link",
 *   label = @Translation("Link to form"),
 *   description = @Translation("Display link to the referenced webform."),
 *   field_types = {
 *     "webform"
 *   }
 * )
 */
class WebformEntityReferenceLinkFormatter extends WebformEntityReferenceFormatterBase {

  /**
   * The webform message manager.
   *
   * @var \Drupal\webform\WebformMessageManagerInterface
   */
  protected $messageManager;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * WebformEntityReferenceLinkFormatter constructor.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\webform\WebformMessageManagerInterface $message_manager
   *   The webform message manager.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, WebformMessageManagerInterface $message_manager, Token $token) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $message_manager);

    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('webform.message_manager'),
      $container->get('token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'label' => 'Go to [webform:title] webform',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Label: @label', ['@label' => $this->getSetting('label')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['label'] = [
      '#title' => $this->t('Label'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('label'),
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $source_entity = $items->getEntity();
    $this->messageManager->setSourceEntity($source_entity);

    $elements = [];

    /** @var \Drupal\webform\WebformInterface[] $entities */
    $entities = $this->getEntitiesToView($items, $langcode);
    foreach ($entities as $delta => $entity) {
      // Do not display the webform if the current user can't create submissions.
      if ($entity->id() && !$entity->access('submission_create')) {
        continue;
      }

      if ($entity->isOpen()) {
        $link_options = [
          'query' => [
            'source_entity_type' => $source_entity->getEntityTypeId(),
            'source_entity_id' => $source_entity->id(),
          ],
        ];
        $elements[$delta] = [
          '#type' => 'link',
          '#title' => $this->t($this->token->replace($this->getSetting('label'), [
            'webform' => $entity,
          ])),
          '#url' => $entity->toUrl('canonical', $link_options),
        ];
      }
      else {
        $this->messageManager->setWebform($entity);
        $message_type = $entity->isOpening() ? WebformMessageManagerInterface::FORM_OPEN_MESSAGE : WebformMessageManagerInterface::FORM_CLOSE_MESSAGE;
        $elements[$delta] = $this->messageManager->build($message_type);
      }

      $this->setCacheContext($elements[$delta], $entity, $items[$delta]);
    }

    return $elements;
  }

}
