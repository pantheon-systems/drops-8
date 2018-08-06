<?php

namespace Drupal\google_cse;

use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Utility\Html;

/**
 * Additional functions as services for Google CSE.
 */
class GoogleCSEServices implements ContainerFactoryPluginInterface {

  /**
   * Maximum number of results from a Google search.
   */
  const GOOGLE_MAX_SEARCH_RESULTS = 1000;

  /**
   * RequestStack object for getting requests.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * The config object for 'search.page.google_cse_search'.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $CSEconfig;

  /**
   * The language manager service object.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  private $languageManager;

  /**
   * Renderer service object.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  private $renderer;

  /**
   * ModuleHandler service object.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  private $moduleHandler;

  /**
   * GoogleCSEServices constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   RequestStack object.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config object.
   * @param \Drupal\Core\Language\LanguageManager $languageManager
   *   Langauge manager service object.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   Renderer service object.
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
   *   ModuleHandler service object.
   */
  public function __construct(RequestStack $requestStack, ConfigFactoryInterface $configFactory, LanguageManager $languageManager, Renderer $renderer, ModuleHandler $moduleHandler) {
    $this->requestStack = $requestStack;
    $this->CSEconfig = $configFactory->get('search.page.google_cse_search');
    $this->languageManager = $languageManager;
    $this->renderer = $renderer;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack'),
      $container->get('config.factory'),
      $container->get('language_manager'),
      $container->get('renderer'),
      $container->get('module_handler')
    );
  }

  /**
   * Sends query to Google's Custom Search Engine and returns response.
   *
   * @param string $keys
   *   The search terms.
   * @param int $offset
   *   The result number to start at.
   *
   * @return string
   *   XML response string.
   */
  public function service($keys, $offset = 0) {
    $page = 0;
    $response = [];

    if ($this->requestStack->getCurrentRequest()->query->has('page')) {
      $page = $this->requestStack->getCurrentRequest()->query->get('page');
    }
    if (isset($response[$keys])) {
      return $response[$keys];
    }

    // Number of results per page. 10 is the default for Google CSE.
    // @TODO Confirm input in UI
    $rows = (int) $this->CSEconfig->get('configuration')['google_cse_adv_results_per_page'];

    $query = array(
      'cx' => $this->CSEconfig->get('configuration')['cx'],
      'client' => 'google-csbe',
      'output' => 'xml_no_dtd',
      'filter' => '1',
      'hl' => $this->paramhl(),
      'lr' => $this->paramlr(),
      'q' => $keys,
      'num' => $rows,
      'start' => ($offset) ? $offset : ($page * $rows),
      'as_sitesearch' => $this->CSEconfig->get('configuration')['limit_domain'],
    );

    if ($this->requestStack->getCurrentRequest()->query->has('more')) {
      $query['+more:'] = urlencode($this->requestStack->getCurrentRequest()->query->get('more'));
    }

    $url = Url::fromUri('http://www.google.com/cse', ['query' => $query]);

    // Get the google response.
    $response = $this->getResponse($url->toString());

    return $response;
  }

  /**
   * Returns "hl" language param for search request.
   *
   * @return string
   *   The language code.
   */
  public function paramhl() {

    $language = $this->CSEconfig->get('configuration')['google_cse_adv_language'];
    switch ($language) {
      case 'active':
        $language = $this->languageManager->getCurrentLanguage();
        return $language->getId();

      default:
        return '';
    }
  }

  /**
   * Returns "lr" language param for search request.
   *
   * @return string
   *   The language code.
   */
  public function paramlr() {
    switch ($this->CSEconfig->get('configuration')['google_cse_adv_language']) {
      case 'active':
        $language = $this->languageManager->getCurrentLanguage();
        return 'lang_' . $language->getId();

      default:
        return '';
    }
  }

  /**
   * Given the url with the search we try to do, get response from Google.
   *
   * @param string $url
   *   The Google URL to query.
   *
   * @return string
   *   The response from Google.
   */
  public function getResponse($url) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    // Return into a variable.
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);

    if ($this->moduleHandler->moduleExists('proxy_settings') && $proxy_host = proxy_settings_host('google_cse_adv')) {

      if ($proxy_port = proxy_settings_port('google_cse_adv')) {
        curl_setopt($curl, CURLOPT_PROXY, $proxy_host . ':' . $proxy_port);
      }
      else {
        curl_setopt($curl, CURLOPT_PROXY, $proxy_host);
      }
      if ($user = proxy_settings_username('google_cse_adv') && $password = proxy_settings_password('google_cse_adv')) {
        curl_setopt($curl, CURLOPT_PROXYUSERPWD, $user . ':' . $password);
      }
    }

    $response[] = curl_exec($curl);
    curl_close($curl);

    return $response;
  }

  /**
   * Returns the thumbnail properly themed if configured to do so.
   *
   * @param string $title
   *   Image Title.
   * @param array $image_att
   *   Image array.
   *
   * @return string||empty
   *   The HTML for the image.
   */
  protected function thumbnail($title, array $image_att) {
    if ($this->CSEconfig->get('configuration')['results_display_images']) {
      $image = [
        'type' => 'image',
        'path' => isset($image_att['value']) ? $image_att['value'] : '',
        'alt' => $title,
        'title' => $title,
        'attributes' => array('width' => '100px'),
        'getsize' => FALSE,
      ];
      return $this->renderer->render($image);
    }
    return '';
  }

  /**
   * Function to fetch the results xml from Google.
   *
   * @param string $response
   *   The XML response from Google.
   * @param string $keys
   *   The search keys.
   * @param string $conditions
   *   Search conditions.
   *
   * @return string
   *   Results.
   *
   * @TODO $condition is an used variable verify and remove it.
   */
  public function responseResults($response, $keys, $conditions) {
    $xml = simplexml_load_string($response);
    $results = array();
    // Number of results.
    $total = 0;

    if (isset($xml->RES->R)) {

      // Cap the result total if necessary.
      // Google will not return more than 1000 results, but RES->M may
      // be higher than this, which messes up our paging. Retain a copy
      // of the original so that themers can still display it.
      // Also, any result beyond pages 8x and 99 tends to repeat themselves, so
      // they are not relevant. Limited then to 150 pages (1500)
      // @TODO Confirm input in UI
      $max_results = $this->CSEconfig->get('configuration')['google_cse_adv_maximum_results'];

      $total = (int) $xml->RES->M;
      $xml->RES->M_ORIGINAL = $total;

      // Is the result accurate?
      if (!$this->isAccurateResult($response)) {
        $total = $this->getAccurateResultsCount($keys, $total);
      }

      if ($total > $max_results) {
        $xml->RES->M = $total = $max_results;
      }

      foreach ($xml->RES->R as $result) {

        // Clean the text and remove tags.
        $title = $this->cleanString((string) $result->T);

        if ($result->PageMap) {
          $att = $result->PageMap->DataObject->attributes();
          switch ($att['type']) {
            case "cse_image":
              $image_att = $result->PageMap->DataObject->Attribute->attributes();

              // Clean the text.
              $text_snippet = $this->cleanString((string) $result->S);

              // Add a search result image.
              $snippet = $this->thumbnail($title, $image_att) . $text_snippet;

              // Clean the text.
              $extra = $this->cleanString((string) $result->U);
              $extra = parse_url($extra);
              $extra = $extra['host'];
              break;

            case "metatags":
              // Clean the string.
              $snippet = $this->cleanString((string) $result->S);

              // Clean the string.
              $extra = $this->cleanString(Html::escape((string) $result->U));

              $extra = parse_url($extra);
              $extra = $extra['host'] . " | Document";
              break;
          }
        }
        else {
          if ($result->SL_RESULTS) {
            $snippet = strip_tags((string) $result->SL_RESULTS->SL_MAIN->BODY_LINE->BLOCK->T);
          }
          else {
            $snippet = (string) $result->S;
          }
          // Clean the text.
          $snippet = $this->cleanString($snippet);

          // Clean the text.
          $extra = $this->cleanString(Html::escape((string) $result->U));

          $extra = parse_url($extra);
          $extra = $extra['host'];
        }

        // Results in a Drupal themed way for search.
        $results[] = array(
          'link' => (string) $result->U,
          'title' => $title,
          'snippet' => $snippet,
          'keys' => Html::escape($keys),
          'extra' => array($extra),
          'date' => NULL,
        );
      }

      // No pager query was executed - we have to set the pager manually.
      // @TODO Confirm input in UI.
      $limit = $this->CSEconfig->get('configuration')['google_cse_adv_results_per_page'];
      pager_default_initialize($total, $limit);

    }

    // Allow other modules to alter the number of results.
    $this->moduleHandler->alter('google_cse_num_results', $total);

    return $results;
  }

  /**
   * Check Return if the response from Google is accurate.
   *
   * Google initially estimates the exact number of results
   * that the search should have.
   *
   * @param string $response
   *   The XML response from Google.
   *
   * @return bool
   *   TRUE if the results are considered accurate.
   */
  public function isAccurateResult($response) {
    $accurate = FALSE;
    // Time to get the response.
    $xml = simplexml_load_string($response);

    // And to check the "accurate" Google variable, if the XT flag exists
    // the search is accurate.
    if (isset($xml->RES->XT)) {
      $accurate = TRUE;
    }

    return $accurate;
  }

  /**
   * Get the exact (accurate) number of search results to be used in the pager.
   *
   * Google will never return more than 1000 results for any given search. If a
   * request for the maximum results is made, Google will return the last page
   * of the search results with the start and end position as attributes of the
   * results.
   * The <RES> tag encapsulates the set of individual search results and
   * details about those results. The tag attributes are SN (the 1-based index
   * of the first search result returned in this result set) and EN (the
   * 1-based index of the last search result).
   *
   * @param string $keys
   *   The search keys.
   * @param int $total
   *   The initial estimated total.
   *
   * @return int
   *   The accurate total number of results.
   */
  public function getAccurateResultsCount($keys, $total) {
    $total_num_results = 0;
    // Allow other modules to alter the keys.
    $this->moduleHandler->alter('google_cse_searched_keys', $keys);
    $offset = self::GOOGLE_MAX_SEARCH_RESULTS - $this->CSEconfig->get('configuration')['google_cse_adv_results_per_page'];
    $response = $this->service($keys, $offset);
    $xml = simplexml_load_string($response[0]);
    if (isset($xml->RES)) {
      // Get the 1-based index of the last search result item from the result
      // end attribute (EN) of the search result tag (RES).
      $attributes = $xml->RES->attributes();
      $total_num_results += (int) $attributes['EN'];
    }

    // If we do not find an accurate result we will use the initial estimate.
    if (!$total_num_results) {
      $total_num_results = $total;
    }
    return $total_num_results;
  }

  /**
   * Clean string of html, tags, etc...
   *
   * @param string $input_str
   *   The original string.
   *
   * @return string
   *   The cleaned output.
   */
  public function cleanString($input_str) {
    $cleaned_str = $input_str;

    if (function_exists('htmlspecialchars_decode')) {
      $cleaned_str = htmlspecialchars_decode($input_str, ENT_QUOTES);
    }

    // Remove possible tags.
    $cleaned_str = strip_tags($cleaned_str);

    return $cleaned_str;
  }

  /**
   * Returns SiteSearch options form item.
   */
  public function siteSearchForm(&$form) {
    if ($options = $this->sitesearchOptions()) {
      $form['sitesearch'] = array(
        '#type' => $this->CSEconfig->get('sitesearch_form'),
        '#options' => $options,
        '#default_value' => $this->sitesearchDefault(),
      );
      if ($form['sitesearch']['#type'] == 'select' && isset($form['sa'])) {
        $form['sa']['#weight'] = 10;
      }
    }
  }

  /**
   * Returns SiteSearch options.
   */
  public function sitesearchOptions() {
    static $options;
    if (!isset($options)) {
      $options = array();
      if ($sites = preg_split('/[\n\r]+/', $this->CSEconfig->get('configuration')['sitesearch'], -1, PREG_SPLIT_NO_EMPTY)) {
        $options[''] = ($var = $this->CSEconfig->get('configuration')['sitesearch_option']) ? $var : t('Search the web');
        foreach ($sites as $site) {
          $site = preg_split('/[\s]+/', trim($site), 2, PREG_SPLIT_NO_EMPTY);
          // Select options will be HTML-escaped.
          // Radio options will be XSS-filtered.
          $options[$site[0]] = isset($site[1]) ? $site[1] : t('Search %sitesearch', array('%sitesearch' => $site[0]));
        }
      }
    }
    return $options;
  }

  /**
   * Returns SiteSearch default value.
   */
  public function sitesearchDefault() {
    $options = $this->sitesearchOptions();
    if ($this->requestStack->getCurrentRequest()->query->has('sitesearch') && isset($options[$this->requestStack->getCurrentRequest()->query->get('sitesearch')])) {
      return $this->requestStack->getCurrentRequest()->query->get('sitesearch');
    }
    elseif ($this->CSEconfig->get('configuration')['sitesearch_default']) {
      // Return the key of the second element in the array.
      return key(array_slice($options, 1, 1));
    }
    return '';
  }

  /**
   * Returns an array of any advanced settings which have been set.
   */
  public function advancedSettings() {
    $language = $this->languageManager->getCurrentLanguage()->getId();
    $settings = array();
    foreach (array('cr', 'gl', 'hl', 'ie', 'lr', 'oe', 'safe') as $parameter) {
      if ($setting = $this->CSEconfig->get('configuration')[$parameter]) {
        $settings[$parameter] = $setting;
      }
    }
    if ($this->CSEconfig->get('configuration')['locale_hl']) {
      $settings['hl'] = $language;
    }
    if ($this->CSEconfig->get('configuration')['locale_lr']) {
      $settings['lr'] = 'lang_' . $language;
    }
    return $settings;
  }

}
