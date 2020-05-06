<?php

namespace Drupal\webform_options_custom;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformMessage;
use Drupal\webform\Utility\WebformDialogHelper;
use Drupal\webform\Utility\WebformOptionsHelper;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to set webform options custom.
 */
class WebformOptionsCustomForm extends EntityForm {

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructs a WebformOptionsCustomForm object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   */
  public function __construct(ClientInterface $http_client) {
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    if ($this->operation == 'duplicate') {
      $this->setEntity($this->getEntity()->createDuplicate());
    }

    parent::prepareEntity();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform_options_custom\WebformOptionsCustomInterface $webform_options_custom */
    $webform_options_custom = $this->getEntity();

    // Customize title for duplicate and edit operation.
    switch ($this->operation) {
      case 'duplicate':
        $form['#title'] = $this->t("Duplicate '@label' custom options", ['@label' => $webform_options_custom->label()]);
        break;

      case 'edit':
      case 'source':
      case 'preview':
        $form['#title'] = $webform_options_custom->label();
        break;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform_options_custom\WebformOptionsCustomInterface $webform_options_custom */
    $webform_options_custom = $this->entity;

    switch ($this->operation) {
      case 'preview':
        // Build and return a preview of the custom options element.
        $form['preview'] = $webform_options_custom->getPreview();
        return $form;

      case 'source':
        // Build and return a options YAML source element.
        $form['options'] = [
          '#type' => 'details',
          '#title' => $this->t('Options'),
          '#open' => TRUE,
        ];
        $form['options']['options_message'] = [
          '#type' => 'webform_message',
          '#message_type' => 'info',
          '#message_message' => $this->t('Below options are used to enhance and also translate the custom options parsed from the HTML/SVG markup.'),
          '#message_close' => TRUE,
          '#message_storage' => WebformMessage::STORAGE_SESSION,
        ];
        $form['options']['options'] = [
          '#type' => 'webform_codemirror',
          '#mode' => 'yaml',
          '#title' => $this->t('Options (YAML)'),
          '#title_displace' => 'invisible',
          '#attributes' => ['style' => 'min-height: 200px'],
          '#default_value' => $this->getOptions(),
        ];
        return parent::form($form, $form_state);
    }

    /** @var \Drupal\webform_options_custom\WebformOptionsCustomStorageInterface $webform_options_custom_storage */
    $webform_options_custom_storage = $this->entityTypeManager->getStorage('webform_options_custom');

    // General.
    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General'),
      '#open' => TRUE,
    ];
    $form['general']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#attributes' => ($webform_options_custom->isNew()) ? ['autofocus' => 'autofocus'] : [],
      '#default_value' => $webform_options_custom->label(),
    ];
    $form['general']['id'] = [
      '#type' => 'machine_name',
      '#machine_name' => [
        'exists' => '\Drupal\webform_options_custom\Entity\WebformOptionsCustom::load',
        'label' => '<br/>' . $this->t('Machine name'),
        'source' => ['general', 'label'],
      ],
      '#maxlength' => 32,
      '#field_suffix' => ($webform_options_custom->isNew()) ? ' (' . $this->t('Maximum @max characters', ['@max' => 32]) . ')' : '',
      '#required' => TRUE,
      '#disabled' => !$webform_options_custom->isNew(),
      '#default_value' => $webform_options_custom->id(),
    ];
    $form['general']['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#description' => $this->t('A brief description present to the user when adding this element to a webform.'),
      '#required' => TRUE,
      '#rows' => 2,
      '#default_value' => $webform_options_custom->get('description'),
    ];
    $form['general']['help'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Help text'),
      '#description' => $this->t('Instructions to present to the user below this element on the editing form.'),
      '#default_value' => $webform_options_custom->get('help'),
    ];
    $form['general']['category'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('Category'),
      '#options' => $webform_options_custom_storage->getCategories(),
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $webform_options_custom->get('category'),
    ];

    // Template.
    $form['template'] = [
      '#type' => 'details',
      '#title' => $this->t('Template'),
      '#open' => TRUE,
    ];
    $form['template']['type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Type'),
      '#options' => [
        WebformOptionsCustomInterface::TYPE_URL => $this->t('URL'),
        WebformOptionsCustomInterface::TYPE_TEMPLATE => $this->t('Template'),
      ],
      '#options_display' => 'side_by_side',
      '#default_value' => $webform_options_custom->get('type'),
      '#required' => TRUE,
    ];
    $form['template']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('HTML/SVG file URL or path'),
      '#description' => $this->t('Enter the absolute URL or root-relative path to the HTML/SVG file. The HTML/SVG file must be publicly accessible using http:// or https://.'),
      '#default_value' => $webform_options_custom->get('url'),
      '#states' => [
        'visible' => [
          ':input[name="type"]' => ['value' => WebformOptionsCustomInterface::TYPE_URL],
        ],
        'required' => [
          ':input[name="type"]' => ['value' => WebformOptionsCustomInterface::TYPE_URL],
        ],
      ],
    ];
    if (function_exists('imce_process_url_element')) {
      imce_process_url_element($form['template']['url'], 'link');
      $form['#attached']['library'][] = 'webform/imce.input';
    }
    $form['template']['template'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'twig',
      '#title' => $this->t('HTML/SVG markup template (Twig)'),
      '#description' => $this->t('The entire element with descriptions without the hash (#) prefixes is passed as variables to the Twig template.'),
      '#default_value' => $webform_options_custom->get('template'),
      '#states' => [
        'visible' => [
          ':input[name="type"]' => ['value' => WebformOptionsCustomInterface::TYPE_TEMPLATE],
        ],
        'required' => [
          ':input[name="type"]' => ['value' => WebformOptionsCustomInterface::TYPE_TEMPLATE],
        ],
      ],
    ];
    $form['template']['twig_help'] = [
      '#type' => 'details',
      '#title' => $this->t('Help using Twig'),
      'description' => $this->buildTwigHelp(),
      '#states' => [
        'visible' => [
          ':input[name="type"]' => ['value' => WebformOptionsCustomInterface::TYPE_TEMPLATE],
        ],
        'required' => [
          ':input[name="type"]' => ['value' => WebformOptionsCustomInterface::TYPE_TEMPLATE],
        ],
      ],
    ];
    $form['template']['value_attributes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Option value attribute names'),
      '#description' => $this->t('Enter a comma-delimited list of attribute names. The first matched value attribute will be used for all custom option values .Leave blank if options values is populated using Twig.'),
      '#pattern' => '^([-_,]|[a-z])+$',
      '#default_value' => $webform_options_custom->get('value_attributes'),
    ];
    $form['template']['text_attributes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Option text attribute names'),
      '#description' => $this->t('Enter a comma-delimited list of attribute names. The first matched text attribute will be used for all custom option text. Leave blank if options text is populated using Twig.'),
      '#pattern' => '^([-_,]|[a-z])+$',
      '#default_value' => $webform_options_custom->get('text_attributes'),
    ];
    $form['template']['fill'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow SVG option elements to be filled using CSS'),
      '#description' => $this->t('If checked, inline fill styles will be removed and replaced using CSS.'),
      '#return_value' => TRUE,
      '#default_value' => $webform_options_custom->get('fill'),
    ];
    $form['template']['zoom'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable SVG panning and zooming'),
      '#description' => $this->t('If checked, SVG graphic can be panned and zooming using the <a href=":href">svg-pan-zoom</a> library', [':href' => 'https://github.com/ariutta/svg-pan-zoom']),
      '#return_value' => TRUE,
      '#default_value' => $webform_options_custom->get('zoom'),
    ];
    $form['assets'] = [
      '#type' => 'details',
      '#title' => $this->t('CSS/JS'),
      '#open' => TRUE,
    ];
    $form['assets']['css'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'css',
      '#title' => $this->t('Custom CSS'),
      '#default_value' => $webform_options_custom->get('css'),
    ];
    $form['assets']['javascript'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'javascript',
      '#title' => $this->t('Custom JavaScript'),
      '#default_value' => $webform_options_custom->get('javascript'),
    ];
    // Options.
    $form['options'] = [
      '#type' => 'details',
      '#title' => $this->t('Options'),
      '#open' => TRUE,
    ];
    $form['options']['options_message'] = [
      '#type' => 'webform_message',
      '#message_type' => 'info',
      '#message_message' => $this->t('Below options are used to enhance and also translate the custom options parsed from the HTML/SVG markup.'),
      '#message_close' => TRUE,
      '#message_storage' => WebformMessage::STORAGE_SESSION,
    ];
    $form['options']['options'] = [
      '#type' => 'webform_options',
      '#title' => $this->t('Options'),
      '#descriptions' => $this->t('Option descriptions are displayed by tooltips.'),
      '#title_displace' => 'invisible',
      '#options_description' => TRUE,
      '#empty_options' => 10,
      '#add_more_items' => 10,
      '#default_value' => $this->getOptions(),
    ];
    $form['options']['show_select'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show select menu associated with the custom options element'),
      '#description' => $this->t('If checked, the select menu associated with the custom options element will be visible. Displaying the standard HTML select menu assists mobile users and users with disabilities when thy are selecting custom options.'),
      '#return_value' => TRUE,
      '#default_value' => $webform_options_custom->get('show_select'),
    ];
    $form['options']['tooltip'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display option text and description in a tooltip'),
      '#description' => $this->t('If checked, option text and description will be displayed using a tooltip.'),
      '#return_value' => TRUE,
      '#default_value' => $webform_options_custom->get('tooltip'),
    ];

    // Integration.
    $form['integration'] = [
      '#type' => 'details',
      '#title' => $this->t('Integration'),
      '#open' => TRUE,
    ];
    $form['integration']['element'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use as a basic select element'),
      '#description' => $this->t('If checked, this custom options element will be available when a user is adding elements to a webform.'),
      '#return_value' => TRUE,
      '#default_value' => $webform_options_custom->get('element'),
    ];
    $form['integration']['entity_reference'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use as an entity reference element'),
      '#description' => $this->t('If checked, this custom options element will be available for entity references when a user is adding elements to a webform.'),
      '#return_value' => TRUE,
      '#default_value' => $webform_options_custom->get('entity_reference'),
    ];

    return parent::form($form, $form_state);
  }

  /**
   * Build Twig help.
   *
   * @return array
   *   A renderable array container Twig help.
   */
  protected function buildTwigHelp() {
    // Build custom options specific Twig help.
    // @see \Drupal\webform\Twig\WebformTwigExtension::buildTwigHelp
    $t_args = [
      ':twig_href' => 'https://twig.sensiolabs.org/',
      ':drupal_href' => 'https://www.drupal.org/docs/8/theming/twig',
    ];
    $build = [];
    $build[] = [
      '#markup' => '<p>' . $this->t('Learn about <a href=":twig_href">Twig</a> and how it is used in <a href=":drupal_href">Drupal</a>.', $t_args) . '</p>',
    ];
    $build[] = [
      '#markup' => '<p>' . $this->t("The following variables are available:") . '</p>',
    ];
    $build[] = [
      '#theme' => 'item_list',
      '#items' => [
        '{{ type }}',
        '{{ title }}',
        '{{ description }}',
        '{{ help }}',
        '{{ options }}',
        '{{ options_custom }}',
        '{{ descriptions }}',
        '{{ default_value }}',
        '{{ multiple }}',
        '{{ attributes }}',
        '{{ empty_option }}',
        '{{ empty_value }}',
      ],
    ];
    $build[] = [
      '#markup' => '<p>' . $this->t("You can debug data using the <code>webform_debug()</code> function.") . '</p>',
    ];
    $build[] = [
      '#markup' => "<pre>{{ webform_debug(data) }}</pre>",
    ];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    if ($this->operation === 'preview') {
      return [];
    }

    $actions = parent::actions($form, $form_state);

    // Remove delete button from source edit form.
    if ($this->operation === 'source') {
      unset($actions['delete']);
    }

    // Open delete button in a modal dialog.
    if (isset($actions['delete'])) {
      $actions['delete']['#attributes'] = WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NARROW, $actions['delete']['#attributes']['class']);
      WebformDialogHelper::attachLibraries($actions['delete']);
    }

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\webform_options_custom\WebformOptionsCustomInterface $webform_options_custom */
    $webform_options_custom = $this->getEntity();

    $this->copyFormValuesToEntity($webform_options_custom, $form, $form_state);

    // Make sure the URL exists.
    $url = $webform_options_custom->getUrl();
    if ($url) {
      $file_exists = FALSE;
      try {
        $response = $this->httpClient->get($url);
        $file_exists = ($response->getStatusCode() === 200);
      }
      catch (\RangeException $exception) {
        // Do nothing.
      }
      if (!$file_exists) {
        $t_args = ['%url' => $form_state->getValue('url')];
        $form_state->setErrorByName('url', $this->t('HTML/SVG file URL or path %url not found.', $t_args));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform_options_custom\WebformOptionsCustomInterface $webform_options_custom */
    $webform_options_custom = $this->getEntity();
    $webform_options_custom->save();

    $context = [
      '@label' => $webform_options_custom->label(),
      'link' => $webform_options_custom->toLink($this->t('Edit'), 'edit-form')->toString(),
    ];
    $this->logger('webform_options_custom')->notice('Custom options @label saved.', $context);

    $this->messenger()->addStatus($this->t('Custom options %label saved.', [
      '%label' => $webform_options_custom->label(),
    ]));

    $form_state->setRedirect('entity.webform_options_custom.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function afterBuild(array $element, FormStateInterface $form_state) {
    // Overriding after \Drupal\Core\Entity\EntityForm::afterBuild because
    // it calls ::buildEntity(), which calls ::copyFormValuesToEntity, which
    // attempts to populate the entity even though the 'options' have not been
    // validated and set.
    // @see \Drupal\Core\Entity\EntityForm::afterBuild
    // @eee \Drupal\webform_options_custom\WebformOptionsCustomForm::copyFormValuesToEntity
    // @see \Drupal\webform\Element\WebformOptions
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform_options_custom\WebformOptionsCustomInterface $entity */
    $values = $form_state->getValues();
    if (is_array($values['options'])) {
      $entity->setOptions($values['options']);
      unset($values['options']);
    }

    foreach ($values as $key => $value) {
      $entity->set($key, $value);
    }
  }

  /**
   * Get options.
   *
   * @return array
   *   An associative array of options.
   */
  protected function getOptions() {
    /** @var \Drupal\webform_options_custom\WebformOptionsCustomInterface $webform_options_custom */
    $webform_options_custom = $this->getEntity();
    $options = $webform_options_custom->getOptions();
    return WebformOptionsHelper::convertOptionsToString($options);
  }

}
