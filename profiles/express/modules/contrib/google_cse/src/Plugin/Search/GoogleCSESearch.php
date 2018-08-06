<?php

namespace Drupal\google_cse\Plugin\Search;

use Drupal\search\Plugin\ConfigurableSearchPluginBase;
use Drupal\Core\Access\AccessibleInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\google_cse\GoogleCSEServices;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Handles searching for node entities using the Search module index.
 *
 * @SearchPlugin(
 *   id = "google_cse_search",
 *   title = @Translation("Google CSE Search")
 * )
 */
class GoogleCSESearch extends ConfigurableSearchPluginBase implements AccessibleInterface {

  /**
   * GoogleCSEServices object.
   *
   * @var \Drupal\google_cse\GoogleCSEServices
   */
  protected $googlecseservices;

  /**
   * {@inheritdoc}
   */
  protected $configuration;

  /**
   * RequestStack object for getting requests.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * ModuleHandler services object.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  private $renderer;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, GoogleCSEServices $googleCSEServices, RequestStack $requestStack, ModuleHandlerInterface $moduleHandler, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->googlecseservices = $googleCSEServices;
    $this->requestStack = $requestStack;
    $this->moduleHandler = $moduleHandler;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('google_cse.services'),
      $container->get('request_stack'),
      $container->get('module_handler'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setSearch($keywords, array $parameters, array $attributes) {
    if (empty($parameters['search_conditions'])) {
      $parameters['search_conditions'] = '';
    }
    parent::setSearch($keywords, $parameters, $attributes);
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation = 'view', AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::allowedIf(!empty($account) && $account->hasPermission('search Google CSE'))->cachePerPermissions();
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = [
      'cx' => '',
      'results_tab' => '',
      'results_width' => 600,
      'cof_here' => 'FORID:11',
      'cof_google' => 'FORID:0',
      'results_prefix' => '',
      'results_suffix' => '',
      'results_searchbox_width' => 40,
      'results_display' => 'here',
      'results_display_images' => TRUE,
      'no_results_message' => 'Sorry! there were no results matching your query.',
      'sitesearch' => '',
      'sitesearch_form' => 'radios',
      'sitesearch_option' => '',
      'sitesearch_default' => 0,
      'domain' => 'www.google.com',
      'limit_domain' => '',
      'cr' => '',
      'gl' => '',
      'hl' => '',
      'locale_hl' => '',
      'ie' => 'utf-8',
      'lr' => '',
      'locale_lr' => '',
      'oe' => '',
      'safe' => '',
      'custom_css' => '',
      'custom_results_display' => 'results-only',
      'use_adv' => 0,
    ];
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * Verifies if the given parameters are valid enough to execute a search for.
   *
   * @return bool
   *   TRUE if there are keywords or search conditions in the query.
   */
  public function isSearchExecutable() {
    return (bool) ($this->keywords || !empty($this->searchParameters['search_conditions']));
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $keys = $this->getKeywords();
    // @todo $condition is an unused variable verify and remove it.
    $conditions = $this->searchParameters['search_conditions'];
    if ($this->configuration['use_adv']) {
      $response = $this->googlecseservices->service($keys);
      $results = $this->googlecseservices->responseResults($response[0], $keys, $conditions);

      // Allow other modules to alter the keys.
      $this->moduleHandler->alter('google_cse_searched_keys', $keys);

      // Allow other modules to alter the results.
      $this->moduleHandler->alter('google_cse_searched_results', $results);

      return $results;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildResults() {
    $results = $this->execute();

    // @see https://www.drupal.org/node/2195739
    if (!($this->configuration['use_adv'])) {
      $output[] = ['#theme' => 'google_cse_results'];
      return $output;
    }

    if (!$results) {
      // No results found.
      $output[] = ['#theme' => 'google_cse_search_noresults'];
    }

    if ($this->requestStack->getCurrentRequest()->query->has('page')) {
      $current_page = $this->requestStack->getCurrentRequest()->query->get('page');
      $number_results = t('Results @from to @to of @total matches.', array(
        '@from' => $current_page * 10,
        '@to' => $current_page * 10 + 10,
        '@total' => $GLOBALS['pager_total_items'][0],
      ));
      $output['prefix']['#markup'] = $number_results . '<ol class="search-results">';
    }

    foreach ($results as $entry) {
      $output[] = [
        '#theme' => 'search_result',
        '#result' => $entry,
        '#plugin_id' => $this->getPluginId(),
      ];
    }

    if ($this->requestStack->getCurrentRequest()->query->has('page')) {
      // Important, add the pager.
      $pager = ['#type' => 'pager'];
      $output['suffix']['#markup'] = '</ol>' . $this->renderer->render($pager);
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function searchFormAlter(array &$form, FormStateInterface $form_state) {
    // Adds custom submit handler for search form.
    if ($this->pluginId == 'google_cse_search') {
      $this->googlecseservices->siteSearchForm($form);
      $form['#attributes']['class'][] = 'google-cse';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildSearchUrlQuery(FormStateInterface $form_state) {
    // Read keyword and advanced search information from the form values,
    // and put these into the GET parameters.
    $keys = trim($form_state->getValue('keys'));
    if (!$this->configuration['use_adv']) {
      return ['keys' => $keys];
    }
    // @TODO check usage of $here and $sitesearch
    $sitesearch = NULL;
    $here = FALSE;
    return [
      'keys' => $keys,
      'cx' => $this->configuration['cx'],
      'cof' => $here ? $this->configuration['cof_here'] : $this->configuration['cof_google'],
      'sitesearch' => isset($sitesearch) ? $sitesearch : $this->googlecseservices->sitesearchDefault(),
    ] + $this->googlecseservices->advancedSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['google_cse'] = [
      '#title' => $this->t('Google CSE'),
      '#type' => 'details',
      '#open' => TRUE,
    ];

    $form['google_cse']['cx'] = [
      '#title' => $this->t('Google Custom Search Engine ID'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['cx'],
      '#description' => $this->t('Enter your @google (click on control panel).', [
        '@google' => Link::fromTextAndUrl('Google CSE unique ID', Url::fromUri('http://www.google.com/cse/manage/all'))->toString(),
      ]),
    ];

    $form['google_cse']['results_tab'] = [
      '#title' => $this->t('Search results tab name'),
      '#type' => 'textfield',
      '#maxlength' => 50,
      '#size' => 60,
      '#description' => $this->t('Enter a custom name of the tab where search results are displayed (defaults to %google).', [
        '%google' => $this->t('Google'),
      ]),
      '#default_value' => $this->configuration['results_tab'],
    ];

    $form['google_cse']['results_width'] = [
      '#title' => $this->t('Search results frame width'),
      '#type' => 'textfield',
      '#maxlength' => 4,
      '#size' => 6,
      '#description' => $this->t('Enter the desired width, in pixels, of the search frame.'),
      '#default_value' => $this->configuration['results_width'],
    ];

    $form['google_cse']['cof_here'] = [
      '#title' => $this->t('Ad format on this site'),
      '#type' => 'radios',
      '#default_value' => $this->configuration['cof_here'],
      '#options' => [
        'FORID:9' => $this->t('Right'),
        'FORID:10' => $this->t('Top and right'),
        'FORID:11' => $this->t('Top and bottom'),
      ],
      '#description' => $this->t('Ads on the right increase the width of the iframe. Non-profit organizations can disable ads in the Google CSE control panel.'),
    ];

    $form['google_cse']['cof_google'] = [
      '#title' => $this->t('Ad format on Google'),
      '#type' => 'radios',
      '#default_value' => $this->configuration['cof_google'],
      '#options' => [
        'FORID:0' => $this->t('Right'),
        'FORID:1' => $this->t('Top and bottom'),
      ],
      '#description' => $this->t('AdSense ads are also displayed when the CSE links or redirects to Google.'),
    ];

    $form['google_cse']['results_prefix'] = [
      '#title' => $this->t('Search results prefix text'),
      '#type' => 'textarea',
      '#cols' => 50,
      '#rows' => 4,
      '#description' => $this->t('Enter text to appear on the search page before the search form.'),
      '#default_value' => $this->configuration['results_prefix'],
    ];

    $form['google_cse']['results_suffix'] = [
      '#title' => $this->t('Search results suffix text'),
      '#type' => 'textarea',
      '#cols' => 50,
      '#rows' => 4,
      '#description' => $this->t('Enter text to appear on the search page after the search form and results.'),
      '#default_value' => $this->configuration['results_suffix'],
    ];

    $form['google_cse']['results_searchbox_width'] = [
      '#title' => $this->t('Google CSE block searchbox width'),
      '#type' => 'textfield',
      '#maxlength' => 4,
      '#size' => 6,
      '#description' => $this->t('Enter the desired width, in characters, of the searchbox on the Google CSE block.'),
      '#default_value' => $this->configuration['results_searchbox_width'],
    ];

    $form['google_cse']['results_display'] = [
      '#title' => $this->t('Display search results'),
      '#type' => 'radios',
      '#default_value' => $this->configuration['results_display'],
      '#options' => [
        'here' => $this->t('On this site (requires JavaScript)'),
        'google' => $this->t('On Google'),
      ],
      '#description' => $this->t('Search results for the Google CSE block can be displayed on this site, using JavaScript, or on Google, which does not require JavaScript.'),
    ];

    $form['google_cse']['results_display_images'] = [
      '#title' => $this->t('Display thumbnail images in the search results'),
      '#type' => 'checkbox',
      '#description' => $this->t('If set, search result snippets will contain a thumbnail image'),
      '#default_value' => $this->configuration['results_display_images'],
    ];

    $form['google_cse']['no_results_message'] = [
      '#title' => $this->t('No Results message.'),
      '#type' => 'textarea',
      '#cols' => 50,
      '#rows' => 4,
      '#description' => $this->t('Enter the message to be displayed when search yield no results.'),
      '#default_value' => $this->configuration['no_results_message'],
    ];

    $form['google_cse']['sitesearch'] = [
      '#title' => $this->t('SiteSearch settings'),
      '#type' => 'details',
      '#open' => FALSE,
    ];

    $form['google_cse']['sitesearch']['sitesearch'] = [
      '#title' => $this->t('SiteSearch domain'),
      '#type' => 'textarea',
      '#cols' => 50,
      '#rows' => 4,
      '#description' => $this->t('If set, users will be presented with the option of searching only on the domain(s) specified rather than using the CSE. Enter one domain or URL path followed by a description (e.g. %example) on each line.', [
        '%example' => 'example.com/user Search users',
      ]),
      '#default_value' => $this->configuration['sitesearch'],
    ];

    $form['google_cse']['sitesearch']['sitesearch_form'] = [
      '#title' => $this->t('SiteSearch form element'),
      '#type' => 'radios',
      '#options' => [
        'radios' => $this->t('Radio buttons'),
        'select' => $this->t('Select'),
      ],
      '#description' => $this->t('Select the type of form element used to present the SiteSearch option(s).'),
      '#default_value' => $this->configuration['sitesearch_form'],
    ];

    $form['google_cse']['sitesearch']['sitesearch_option'] = [
      '#title' => $this->t('CSE search option label'),
      '#type' => 'textfield',
      '#maxlength' => 50,
      '#size' => 60,
      '#description' => $this->t('Customize the label for CSE search if SiteSearch is enabled (defaults to %search-web).', [
        '%search-web' => 'Search the web',
      ]),
      '#default_value' => $this->configuration['sitesearch_option'],
    ];

    $form['google_cse']['sitesearch']['sitesearch_default'] = [
      '#title' => $this->t('Default to using the SiteSearch domain'),
      '#type' => 'checkbox',
      '#description' => $this->t('If set, searches will default to using the first listed SiteSearch domain rather than the CSE.'),
      '#default_value' => $this->configuration['sitesearch_default'],
    ];

    $form['google_cse']['advanced'] = [
      '#title' => $this->t('Advanced settings'),
      '#type' => 'details',
      '#open' => FALSE,
    ];

    $form['google_cse']['advanced']['domain'] = [
      '#title' => $this->t('Search domain'),
      '#type' => 'textfield',
      '#maxlength' => 64,
      '#description' => $this->t('Enter the Google domain to use for search results, e.g. %google.', [
        '%google' => 'www.google.com',
      ]),
      '#default_value' => $this->configuration['domain'],
    ];

    $form['google_cse']['advanced']['limit_domain'] = [
      '#title' => $this->t('Limit results to this domain'),
      '#type' => 'textfield',
      '#maxlength' => 64,
      '#description' => $this->t('Enter the domain to limit results on (only display results for this domain) %google.', [
        '%google' => 'www.google.com',
      ]),
      '#default_value' => $this->configuration['limit_domain'],
    ];

    $form['google_cse']['advanced']['cr'] = [
      '#title' => $this->t('Country restriction'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['cr'],
      '#description' => $this->t('Enter a 9-letter country code, e.g. %countryNZ, and optional boolean operators, to restrict search results to documents (not) originating in particular countries. See the @crparameter.', [
        '%countryNZ' => 'countryNZ',
        '@crparameter' => Link::fromTextAndUrl($this->t('%cr parameter', ['%cr' => 'cr']), Url::fromUri('https://developers.google.com/custom-search/docs/xml_results#crsp'))->toString(),
      ]),
    ];

    $form['google_cse']['advanced']['gl'] = [
      '#title' => $this->t('Country boost'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['gl'],
      '#description' => $this->t('Enter a 2-letter country code, e.g. %uk, to boost documents written in a particular country. See the @glparameter.', [
        '%uk' => 'uk',
        '@glparameter' => Link::fromTextAndUrl($this->t('%gl parameter', ['%gl' => 'gl']), Url::fromUri('https://developers.google.com/custom-search/docs/xml_results#glsp'))->toString(),
      ]),
    ];

    $form['google_cse']['advanced']['hl'] = [
      '#title' => $this->t('Interface language'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['hl'],
      '#description' => $this->t('Enter a supported 2- or 5-character language code, e.g. %fr, to set the language of the user interface. See the @hlparameter.', [
        '%fr' => 'fr',
        '@hlparameter' => Link::fromTextAndUrl($this->t('%hl parameter', ['%hl' => 'hl']), Url::fromUri('https://developers.google.com/custom-search/docs/xml_results#hlsp'))->toString(),
      ]),
    ];

    $form['google_cse']['advanced']['locale_hl'] = [
      '#title' => $this->t('Set interface language dynamically'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['locale_hl'],
      '#description' => $this->t('The language restriction can be set dynamically if the locale module is enabled. Note the locale language code must match one of the @google.', [
        '@google' => Link::fromTextAndUrl('supported language codes', Url::fromUri('https://developers.google.com/custom-search/docs/xml_results#interfaceLanguages'))->toString(),
      ]),
    ];

    $form['google_cse']['advanced']['ie'] = [
      '#title' => $this->t('Input encoding'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['ie'],
      '#description' => $this->t('The default %utf8 is recommended. See the @ieparameter.', [
        '%utf8' => 'utf-8',
        '@ieparameter' => Link::fromTextAndUrl($this->t('%ie parameter', ['%ie' => 'ie']), Url::fromUri('https://developers.google.com/custom-search/docs/xml_results#iesp'))->toString(),
      ]),
    ];

    $form['google_cse']['advanced']['lr'] = [
      '#title' => $this->t('Language restriction'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['lr'],
      '#description' => $this->t('Enter a supported 7- or 10-character language code, e.g. %lang_en, and optional boolean operators, to restrict search results to documents (not) written in particular languages. See the @lrparameter.', [
        '%langen' => 'lang_en',
        '@lrparameter' => Link::fromTextAndUrl($this->t('%lr parameter', ['%lr' => 'lr']), Url::fromUri('https://developers.google.com/custom-search/docs/xml_results#lrsp'))->toString(),
      ]),
    ];

    $form['google_cse']['advanced']['locale_lr'] = [
      '#title' => $this->t('Set language restriction dynamically'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['locale_lr'],
      '#description' => $this->t('The language restriction can be set dynamically if the locale module is enabled. Note the locale language code must match one of the @supported.', [
        '@supported' => Link::fromTextAndUrl('supported language codes', Url::fromUri('https://developers.google.com/custom-search/docs/xml_results#languageCollections'))->toString(),
      ]),
    ];

    $form['google_cse']['advanced']['oe'] = [
      '#title' => $this->t('Output encoding'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['oe'],
      '#description' => $this->t('The default %utf is recommended. See the @oeparameter.', [
        '%utf' => 'utf-8',
        '@oeparameter' => Link::fromTextAndUrl($this->t('%oe parameter', ['%oe' => 'oe']), Url::fromUri('https://developers.google.com/custom-search/docs/xml_results#oesp'))->toString(),
      ]),
    ];

    $form['google_cse']['advanced']['safe'] = [
      '#title' => $this->t('SafeSearch filter'),
      '#type' => 'select',
      '#options' => [
        '' => '',
        'off' => $this->t('Off'),
        'medium' => $this->t('Medium'),
        'high' => $this->t('High'),
      ],
      '#default_value' => $this->configuration['safe'],
      '#description' => $this->t('SafeSearch filters search results for adult content. See the @safeparameter.', [
        '@safeparameter' => Link::fromTextAndUrl('safe parameter', Url::fromUri('https://developers.google.com/custom-search/docs/xml_results#safesp'))->toString(),
      ]),
    ];

    $form['google_cse']['advanced']['custom_css'] = [
      '#title' => t('Stylesheet Override'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['custom_css'],
      '#description' => $this->t('Set a custom stylesheet to override or add any styles not allowed in the CSE settings (such as "background-color: none;"). Include <span style="color:red; font-weight:bold;">!important</span> for overrides.<br/>Example: %replace', [
        '%replace' => '//replacewithrealsite.com/sites/all/modules/google_cse/default.css',
      ]),
    ];

    $form['google_cse']['advanced']['custom_results_display'] = [
      '#title' => $this->t('Layout of Search Engine'),
      '#type' => 'radios',
      '#default_value' => $this->configuration['custom_results_display'],
      '#options' => [
        'overlay' => $this->t('Overlay'),
        'two-page' => $this->t('Two page'),
        'full-width' => $this->t('Full width'),
        'two-column' => $this->t('Two column'),
        'compact' => $this->t('Compact'),
        'results-only' => $this->t('Results only'),
        'google-hosted' => $this->t('Google hosted'),
      ],
      '#description' => $this->t('Set the search engine layout, as found in the Layout tab of @url.', [
        '@url' => Link::fromTextAndUrl('Custom Search settings', Url::fromUri('https://www.google.com/cse/lookandfeel/layout?cx=' . $this->configuration['cx']))->toString(),
      ]),
    ];

    $form['google_cse_adv'] = [
      '#title' => $this->t('Google CSE Advanced'),
      '#type' => 'details',
      '#open' => TRUE,
    ];

    $form['google_cse_adv']['use_adv'] = [
      '#title' => t('Use advanced, ad-free version, search engine (You will need a paid account with Google)'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['use_adv'],
      '#description' => $this->t('If enabled, search results will be fetch using Adv engine.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->configuration['cx'] = $values['cx'];
    $this->configuration['results_tab'] = $values['results_tab'];
    $this->configuration['results_width'] = $values['results_width'];
    $this->configuration['cof_here'] = $values['cof_here'];
    $this->configuration['cof_google'] = $values['cof_google'];
    $this->configuration['results_prefix'] = $values['results_prefix'];
    $this->configuration['results_suffix'] = $values['results_suffix'];
    $this->configuration['results_searchbox_width'] = $values['results_searchbox_width'];
    $this->configuration['results_display'] = $values['results_display'];
    $this->configuration['results_display_images'] = $values['results_display_images'];
    $this->configuration['no_results_message'] = $values['no_results_message'];
    $this->configuration['sitesearch'] = $values['sitesearch'];
    $this->configuration['sitesearch_form'] = $values['sitesearch_form'];
    $this->configuration['sitesearch_option'] = $values['sitesearch_option'];
    $this->configuration['sitesearch_default'] = $values['sitesearch_default'];
    $this->configuration['domain'] = $values['domain'];
    $this->configuration['limit_domain'] = $values['limit_domain'];
    $this->configuration['cr'] = $values['cr'];
    $this->configuration['gl'] = $values['gl'];
    $this->configuration['hl'] = $values['hl'];
    $this->configuration['locale_hl'] = $values['locale_hl'];
    $this->configuration['ie'] = $values['ie'];
    $this->configuration['lr'] = $values['lr'];
    $this->configuration['locale_lr'] = $values['locale_lr'];
    $this->configuration['oe'] = $values['oe'];
    $this->configuration['safe'] = $values['safe'];
    $this->configuration['custom_css'] = $values['custom_css'];
    $this->configuration['custom_results_display'] = $values['custom_results_display'];
    $this->configuration['use_adv'] = $values['use_adv'];

  }

}
