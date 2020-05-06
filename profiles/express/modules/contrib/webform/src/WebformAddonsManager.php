<?php

namespace Drupal\webform;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Webform add-ons manager.
 */
class WebformAddonsManager implements WebformAddonsManagerInterface {

  use StringTranslationTrait;

  /**
   * Projects that provides additional functionality to the Webform module.
   *
   * @var array
   */
  protected $projects;

  /**
   * {@inheritdoc}
   */
  public function getProject($name) {
    $this->initProjects();
    return $this->projects[$name];
  }

  /**
   * {@inheritdoc}
   */
  public function getProjects($category = NULL) {
    $this->initProjects();
    $projects = $this->projects;
    if ($category) {
      foreach ($projects as $project_name => $project) {
        if ($project['category'] != $category) {
          unset($projects[$project_name]);
        }
      }
    }
    return $projects;
  }

  /**
   * {@inheritdoc}
   */
  public function getThirdPartySettings() {
    $projects = $this->getProjects();
    foreach ($projects as $project_name => $project) {
      if (empty($project['third_party_settings'])) {
        unset($projects[$project_name]);
      }
    }
    return $projects;
  }

  /**
   * {@inheritdoc}
   */
  public function getCategories() {
    $categories = [];
    $categories['element'] = [
      'title' => $this->t('Elements'),
    ];
    $categories['enhancement'] = [
      'title' => $this->t('Enhancements'),
    ];
    $categories['integration'] = [
      'title' => $this->t('Integrations'),
    ];
    $categories['mail'] = [
      'title' => $this->t('Mail'),
    ];
    $categories['migrate'] = [
      'title' => $this->t('Migrate'),
    ];
    $categories['multilingual'] = [
      'title' => $this->t('Multilingual'),
    ];
    $categories['spam'] = [
      'title' => $this->t('SPAM Protection'),
    ];
    $categories['submission'] = [
      'title' => $this->t('Submissions'),
    ];
    $categories['validation'] = [
      'title' => $this->t('Validation'),
    ];
    $categories['utility'] = [
      'title' => $this->t('Utility'),
    ];
    $categories['web_services'] = [
      'title' => $this->t('Web services'),
    ];
    $categories['workflow'] = [
      'title' => $this->t('Workflow'),
    ];
    $categories['development'] = [
      'title' => $this->t('Development'),
    ];
    return $categories;
  }

  /**
   * Initialize add-on projects.
   */
  protected function initProjects() {
    if (!empty($this->projects)) {
      return;
    }

    $projects = [];

    /**************************************************************************/
    // Element.
    /**************************************************************************/

    // Element: Address.
    $projects['address'] = [
      'title' => $this->t('Address'),
      'description' => $this->t('Provides functionality for storing, validating and displaying international postal addresses.'),
      'url' => Url::fromUri('https://www.drupal.org/project/address'),
      'category' => 'element',
      'recommended' => TRUE,
    ];

    // Element: Loqate.
    $projects['loqate'] = [
      'title' => $this->t('Loqate'),
      'description' => $this->t('Provides the webform element called Address Loqate which integration with Loqate (previously PCA/Addressy) address lookup.'),
      'url' => Url::fromUri('https://www.drupal.org/project/loqate'),
      'category' => 'element',
    ];

    // Element: Range Slider.
    $projects['range_slider'] = [
      'title' => $this->t('Range Slider'),
      'description' => $this->t('Integration with http://rangeslider.js.org.'),
      'url' => Url::fromUri('https://github.com/baikho/RangeSlider'),
      'category' => 'element',
    ];

    // Element: Webform Alias Container.
    $projects['webform_alias_container'] = [
      'title' => $this->t('Webform Alias Container'),
      'description' => $this->t('Provides a Webform container designed to contain multiple composite elements.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_alias_container'),
      'category' => 'element',
    ];

    // Element: Webform Belgian National Insurance Number.
    $projects['webform_rrn_nrn'] = [
      'title' => $this->t('Webform Belgian National Insurance Number'),
      'description' => $this->t('Provides webform fieldtype for the Belgian National Insurance Number.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_rrn_nrn'),
      'category' => 'element',
    ];

    // Element: Webform Composite Tools.
    $projects['webform_composite'] = [
      'title' => $this->t('Webform Composite Tools'),
      'description' => $this->t('Provides a reusable composite element for use on webforms.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_composite'),
      'category' => 'element',
    ];

    // Element: Webform Checkboxes Table.
    $projects['webform_checkboxes_table'] = [
      'title' => $this->t('Webform Checkboxes Table'),
      'description' => $this->t('Displays checkboxes element in a table grid.'),
      'url' => Url::fromUri('https://github.com/minnur/webform_checkboxes_table'),
      'category' => 'element',
    ];

    // Element: Webform Crafty Clicks.
    $projects['webform_craftyclicks'] = [
      'title' => $this->t('Webform Crafty Clicks'),
      'description' => $this->t('Adds Crafty Clicks UK postcode lookup to the Webform Address composite element.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_craftyclicks'),
      'category' => 'element',
    ];

    // Element: Webform DropzoneJS.
    $projects['webform_dropzonejs'] = [
      'title' => $this->t('Webform DropzoneJS'),
      'description' => $this->t('Creates a new DropzoneJS element that you can add to webforms.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_dropzonejs'),
      'category' => 'element',
    ];

    // Element: Webform Attachment Gated Download.
    $projects['webform_attachment_gated_download'] = [
      'title' => $this->t('Webform Attachment Gated Download'),
      'description' => $this->t('Provides a field formatter for file, image, and media types which links to a webform.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_attachment_gated_download'),
      'category' => 'element',
    ];

    // Element: Webform Handsontable.
    $projects['handsontable_yml_webform'] = [
      'title' => $this->t('Webform Handsontable'),
      'description' => $this->t('Allows both the Drupal Form API and the Drupal 8 Webforms module to use the Excel-like Handsontable library.'),
      'url' => Url::fromUri('https://www.drupal.org/project/handsontable_yml_webform'),
      'category' => 'element',
    ];

    // Element: Webform Hierarchy.
    $projects['webform_hierarchy'] = [
      'title' => $this->t('Webform Hierarchy'),
      'description' => $this->t('Provides hierarchical widget for webforms.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_hierarchy'),
      'category' => 'element',
    ];

    // Element: Webform IBAN field .
    $projects['webform_iban_field'] = [
      'title' => $this->t('Webform IBAN field'),
      'description' => $this->t('Provides an IBAN Field to collect a valid IBAN number.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_iban_field'),
      'category' => 'element',
    ];

    // Element: Webform Layout Container.
    $projects['webform_layout_container'] = [
      'title' => $this->t('Webform Layout Container'),
      'description' => $this->t("Provides a layout container element to add to a webform, which uses old fashion floats to support legacy browsers that don't support CSS Flexbox (IE9 and IE10)."),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_layout_container'),
      'category' => 'element',
    ];

    // Element: Webform Node Element.
    $projects['webform_node_element'] = [
      'title' => $this->t('Webform Node Element'),
      'description' => $this->t("Provides a 'Node' element to display node content as an element on a webform. Can be modified dynamically using an event handler."),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_node_element'),
      'category' => 'element',
    ];

    // Element: Webform Promotion Code.
    $projects['webform_promotion_code'] = [
      'title' => $this->t('Webform Promotion Code'),
      'description' => $this->t('Provides a promotion code Webform element.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_promotion_code'),
      'category' => 'element',
    ];

    // Element: Webform Select Collection.
    $projects['webform_select_collection'] = [
      'title' => $this->t('Webform Select Collection'),
      'description' => $this->t('Provides a webform element that groups multiple select elements into single collection.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_select_collection'),
      'category' => 'element',
    ];

    // Element: Webform RUT.
    $projects['webform_rut'] = [
      'title' => $this->t('Webform RUT'),
      'description' => $this->t("Provides a RUT (A unique identification number assigned to natural or legal persons of Chile) element."),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_rut'),
      'category' => 'element',
    ];

    // Element: Webform Score.
    $projects['webform_score'] = [
      'title' => $this->t('Webform Score'),
      'description' => $this->t("Lets you score an individual user's answers, then store and display the scores."),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_score'),
      'category' => 'element',
    ];

    // Element: Webform Select Collection.
    $projects['webform_select_collection'] = [
      'title' => $this->t('Webform Select Collection'),
      'description' => $this->t('Provides a webform element that groups multiple select elements into single collection.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_select_collection'),
      'category' => 'element',
    ];

    // Element: Webform Simple Hierarchical Select.
    $projects['webform_shs'] = [
      'title' => $this->t('Webform Simple Hierarchical Select'),
      'description' => $this->t('Integrates Simple Hierarchical Select module with Webform.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_shs'),
      'category' => 'element',
    ];

    // Element: Webform Summation Field.
    $projects['webform_summation_field'] = [
      'title' => $this->t('Webform Summation Field'),
      'description' => $this->t('Provides a webform summation field to collect the values of other fields.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_summation_field'),
      'category' => 'element',
    ];

    /**************************************************************************/
    // Enhancement.
    /**************************************************************************/

    // Enhancement: Formset.
    $projects['formset'] = [
      'title' => $this->t('Formset'),
      'description' => $this->t('Enables the creation of webform sets.'),
      'url' => Url::fromUri('https://github.com/simesy/formset'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Calculation.
    $projects['webform_calculation'] = [
      'title' => $this->t('Webform Calculation'),
      'description' => $this->t('Provides ability to make dynamic calculations using Webform.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_calculation'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Confirmation File.
    $projects['webform_confirmation_file'] = [
      'title' => $this->t('Webform Confirmation File'),
      'description' => $this->t('Provides a webform handler that streams the contents of a file to a user after completing a webform.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_confirmation_file'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Counter.
    $projects['webform_counter'] = [
      'title' => $this->t('Webform Counter'),
      'description' => $this->t('Provides Submissions Counter feature for webforms.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_counter'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Deter.
    $projects['webform_deter'] = [
      'title' => $this->t('Webform Deter'),
      'description' => $this->t('Applies clientside validation checks to webform fields and warns the user when sensitive information may be contained in data being submitted.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_deter'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Email Reply.
    $projects['webform_email_reply_d8'] = [
      'title' => $this->t('Webform Email Reply'),
      'description' => $this->t('Allows users to send an email reply to submissions.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_email_reply'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Extra Field.
    $projects['webform_extra_field'] = [
      'title' => $this->t('Webform Extra Field'),
      'description' => $this->t('Provides an extra field for placing a webform in any entity display mode.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_extra_field'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Extra Field Validation.
    $projects['webform_extra_field_validation'] = [
      'title' => $this->t('Webform Extra Field Validation'),
      'description' => $this->t('Provides extra validation to webform, allowing you to specify validation rules for your Webform components.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_extra_field_validation'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Feedback.
    $projects['webform_feedback'] = [
      'title' => $this->t('Webform Feedback'),
      'description' => $this->t('Adds a lightbox like pop-up for a contact/feedback form based on webform.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_feedback'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Navigation.
    $projects['webformnavigation'] = [
      'title' => $this->t('Webform Navigation'),
      'description' => $this->t('Creates a navigation setting for webform that allows users to navigate forwards and backwards through wizard pages when the wizard navigation progress bar is enabled.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webformnavigation'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform OCR.
    $projects['webform_ocr'] = [
      'title' => $this->t('Webform OCR'),
      'description' => $this->t('OCR images as new Webforms.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_ocr'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Pre-populate.
    $projects['webform_prepopulate'] = [
      'title' => $this->t('Webform Pre-populate'),
      'description' => $this->t('Pre-populate a Webform with an external data source without disclosing information via the URL.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_prepopulate'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Protected Downloads.
    $projects['webform_protected_downloads'] = [
      'title' => $this->t('Webform Protected Downloads'),
      'description' => $this->t('Provides protected file downloads using webforms.'),
      'url' => Url::fromUri('https://github.com/timlovrecic/Webform-Protected-Downloads'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Timeout.
    $projects['webform_timeout'] = [
      'title' => $this->t('Webform Timeout'),
      'description' => $this->t('Provides functionality to limit user time during which he is able to make webform submission.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_timeout'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Validation.
    $projects['webform_validation'] = [
      'title' => $this->t('Webform Validation'),
      'description' => $this->t('Add validation rules to Webforms.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_validation'),
      'category' => 'enhancement',
    ];

    // Enhancement: Webform Wizard Full Title.
    $projects['webform_wizard_full_title'] = [
      'title' => $this->t('Webform Wizard Full Title'),
      'description' => $this->t('Extends functionality of Webform so on wizard forms, the title of the wizard page can override the form title.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_wizard_full_title'),
      'category' => 'enhancement',
    ];

    /**************************************************************************/
    // Integrations.
    /**************************************************************************/

    // Integrations: Webform CiviCRM Integration.
    $projects['webform_civicrm'] = [
      'title' => $this->t('Webform CiviCRM Integration'),
      'description' => $this->t('A powerful, flexible, user-friendly form builder for CiviCRM.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_civicrm'),
      'category' => 'integration',
      'recommended' => TRUE,
    ];

    // Integrations: Webform Content Creator.
    $projects['webform_content_creator'] = [
      'title' => $this->t('Webform Content Creator'),
      'description' => $this->t('Provides the ability to create nodes after submitting webforms, and do mappings between the fields of the created node and webform submission values.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_content_creator'),
      'category' => 'integration',
      'recommended' => TRUE,
    ];

    // Integrations: Webform Entity Handler.
    $projects['webform_entity_handler'] = [
      'title' => $this->t('Webform Entity Handler'),
      'description' => $this->t('Provides the ability to create or update entities with the webform submission values.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_entity_handler'),
      'category' => 'integration',
      'recommended' => TRUE,
    ];

    /**************************************************************************/

    // Integrations: Webform AddressFinder.
    $projects['academic_applications'] = [
      'title' => $this->t('Academic Applications'),
      'description' => $this->t('Provides a simple Webform-based system for applying to academic programs.'),
      'url' => Url::fromUri('https://www.drupal.org/project/academic_applications'),
      'category' => 'integration',
    ];

    // Integrations: Ansible.
    $projects['ansible'] = [
      'title' => $this->t('Ansible'),
      'description' => $this->t('Run Ansible playbooks using a Webform handler.'),
      'url' => Url::fromUri('https://www.drupal.org/project/ansible'),
      'category' => 'integration',
    ];

    // Integrations: Commerce Webform Order.
    $projects['commerce_webform_order'] = [
      'title' => $this->t('Commerce Webform Order'),
      'description' => $this->t('Integrates Webform with Drupal Commerce and it allows creating orders with the submission data of a Webform via a Webform handler.'),
      'url' => Url::fromUri('https://www.drupal.org/project/commerce_webform_order'),
      'category' => 'integration',
    ];

    // Integrations: Domain Webform.
    $projects['domain_webform'] = [
      'title' => $this->t('Domain Webform'),
      'description' => $this->t('Domain intergration for the Webform module.'),
      'url' => Url::fromUri('https://github.com/h3rj4n/domain_webform'),
      'category' => 'integration',
    ];

    // Integrations: Druminate Webforms.
    $projects['druminate'] = [
      'title' => $this->t('Druminate Webforms'),
      'description' => $this->t('Allows editors to send webform submissions to Luminate Online Surveys.'),
      'url' => Url::fromUri('https://www.drupal.org/project/druminate'),
      'category' => 'integration',
    ];

    // Integrations: Ecomail webform.
    $projects['ecomail_webform'] = [
      'title' => $this->t('Ecomail webform'),
      'description' => $this->t('Provides a Webform handler to add contact to the list of direct e-mailing service Ecomail.cz.'),
      'url' => Url::fromUri('https://www.drupal.org/project/ecomail_webform'),
      'category' => 'integration',
    ];

    // Integrations: Flashpoint Course Content: Webform.
    $projects['flashpoint_course_webform'] = [
      'title' => $this->t('Flashpoint Course Content: Webform'),
      'description' => $this->t('Integrates Webforms into Flashpoint Courses.'),
      'url' => Url::fromUri('https://www.drupal.org/project/flashpoint_course_webform'),
      'category' => 'integration',
    ];

    // Integrations: Gatsby Webform Backend.
    $projects['react_webform_backend'] = [
      'title' => $this->t('Gatsby Drupal Webform'),
      'description' => $this->t('The goal of this project is to have a react component that generates bootstrap like HTML from webform YAML configuration.'),
      'url' => Url::fromUri('https://www.drupal.org/project/react_webform_backend'),
      'category' => 'integration',
    ];

    // Integrations: GitLab API with Library.
    $projects['gitlab_api'] = [
      'title' => $this->t('GitLab API with Library'),
      'description' => $this->t(' Integrates your Drupal site into GitLab using the GitLab API.'),
      'url' => Url::fromUri('https://www.drupal.org/project/gitlab_api'),
      'category' => 'integration',
    ];

    // Integrations: Group Webform.
    $projects['group_webform'] = [
      'title' => $this->t('Group Webform'),
      'description' => $this->t('Designed to associate group specific webforms with a group when using the Group module.'),
      'url' => Url::fromUri('https://www.drupal.org/project/group_webform'),
      'category' => 'integration',
    ];

    // Integrations: GraphQL Webform.
    $projects['graphql_webform'] = [
      'title' => $this->t('GraphQL Webform'),
      'description' => $this->t('Provides GraphQL integration with the Webform module.'),
      'url' => Url::fromUri('https://www.drupal.org/project/graphql_webform'),
      'category' => 'integration',
    ];

    // Integrations: Headless Ninja React Webform.
    $projects['hn_react_webform'] = [
      'title' => $this->t('Headless Ninja React Webform'),
      'description' => $this->t('With this awesome React component, you can render complete Drupal Webforms in React. With validation, easy custom styling and a modern, clean interface.'),
      'url' => Url::fromUri('https://github.com/headless-ninja/hn-react-webform'),
      'category' => 'integration',
    ];

    // Integrations: Micro Webform.
    $projects['micro_webform'] = [
      'title' => $this->t('Micro Webform'),
      'description' => $this->t('Integrate webform module with a micro site.'),
      'url' => Url::fromUri('https://www.drupal.org/project/micro_webform'),
      'category' => 'integration',
    ];

    // Integrations: OpenInbound for Drupal.
    $projects['openinbound'] = [
      'title' => $this->t('OpenInbound for Drupal'),
      'description' => $this->t('OpenInbound tracks contacts and their interactions on websites.'),
      'url' => Url::fromUri('https://www.drupal.org/project/openinbound'),
      'category' => 'integration',
    ];

    // Integrations: Rules Webform.
    $projects['rules_webform'] = [
      'title' => $this->t('Rules Webform'),
      'description' => $this->t("Provides integration of 'Rules' and 'Webform' modules. It enables to get access to webform submission data from rules. Also it provides possibility of altering and removing webform submission data from rules."),
      'url' => Url::fromUri('https://www.drupal.org/project/rules_webform'),
      'category' => 'integration',
    ];

    // Integrations: Sherpa Webform .
    $projects['sherpa_webform'] = [
      'title' => $this->t('Sherpa Webform'),
      'description' => $this->t('Captures Webform submissions, convert them to JSON, and send them to Sherpa.'),
      'url' => Url::fromUri('https://www.drupal.org/project/sherpa_webform'),
      'category' => 'integration',
    ];

    // Integrations: Watson/Silverpop Webform Parser.
    $projects['watson_form_parser'] = [
      'title' => $this->t('Watson/Silverpop Webform Parser'),
      'description' => $this->t('Allows site-builders to import a form that is exported from the Watson Customer Engagement (WCE) WYSIWYG into a Drupal 8 site and parse it into a Webform.'),
      'url' => Url::fromUri('https://www.drupal.org/project/watson_form_parser'),
      'category' => 'integration',
    ];

    // Integrations: Webform AddressFinder.
    $projects['webform_location_addressfinder'] = [
      'title' => $this->t('Webform AddressFinder'),
      'description' => $this->t('Implements integration between Webform and the AddressFinder service (https://addressfinder.com.au/), providing autocompletion and validation for addresses in Australia and New Zealand.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_location_addressfinder'),
      'category' => 'integration',
    ];

    // Integrations: Webform API Handler.
    $projects['webform_api_handler'] = [
      'title' => $this->t('Webform API Handler'),
      'description' => $this->t("Extends Webform's built it Remote Post handler to enable the creation of custom plugins for pre-processing the request Webform makes to an API endpoint, and for processing and displaying the result of the API request."),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_api_handler'),
      'category' => 'integration',
    ];

    // Integrations: Webform Authorize.Net.
    $projects['authorizenetwebform'] = [
      'title' => $this->t('Webform Authorize.Net'),
      'description' => $this->t('Integrates Webform with Authorize.Net.'),
      'url' => Url::fromUri('https://github.com/ivan-trokhanenko/authorizenetwebform'),
      'category' => 'integration',
    ];

    // Integrations: Webform Copper CRM.
    $projects['webform_copper'] = [
      'title' => $this->t('Webform Copper'),
      'description' => $this->t('Provides a Webform handler that integrates with Copper CRM.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_copper'),
      'category' => 'integration',
    ];

    // Integrations: Webform Emfluence.
    $projects['emfluence_webform'] = [
      'title' => $this->t('Webform Emfluence'),
      'description' => $this->t("Integrates Emfluence Marketing Platform's contacts/save endpoint and Webform 8.x."),
      'url' => Url::fromUri('https://www.drupal.org/sandbox/huskyninja/3074135'),
      'experimental' => TRUE,
      'category' => 'integration',
    ];

    // Integrations: Webform Entity Builder.
    $projects['webform_entity_builder'] = [
      'title' => $this->t('Webform Entity Builder'),
      'description' => $this->t('Provides support code for the generation and management of entities through webforms.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_entity_builder'),
      'category' => 'integration',
    ];

    // Integrations: Webform E-petition.
    $projects['webform_epetition'] = [
      'title' => $this->t('Webform E-petition'),
      'description' => $this->t('Provides a postcode lookup field to find details and emails on your local parliamentary representatives.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_epetition'),
      'category' => 'integration',
    ];

    // Integration: Webform iContact.
    $projects['webform_icontact'] = [
      'title' => $this->t('Webform iContact'),
      'description' => $this->t('Send Webform submissions to iContact list.'),
      'url' => Url::fromUri('https://www.drupal.org/sandbox/ibakayoko/2853326'),
      'experimental' => TRUE,
      'category' => 'integration',
    ];

    // Integration: Webform Cart.
    $projects['webform_cart'] = [
      'title' => $this->t('Webform Cart'),
      'description' => $this->t('Allows you to add products to a webform submission.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_cart'),
      'category' => 'integration',
    ];

    // Integration: Webform Donate.
    $projects['webform_donate'] = [
      'title' => $this->t('Webform Donate'),
      'description' => $this->t('Provides components and integration to receive donations with webforms using the Payments module.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_donate'),
      'category' => 'integration',
    ];

    // Integrations: Webform Eloqua.
    $projects['webform_eloqua'] = [
      'title' => $this->t('Webform Eloqua'),
      'description' => $this->t('Integrates Drupal 8 Webforms with Oracle Eloqua.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_eloqua'),
      'category' => 'integration',
    ];

    // Integrations: Webform GoogleSheets.
    $projects['webform_googlesheets'] = [
      'title' => $this->t('Webform GoogleSheets'),
      'description' => $this->t('Allows to append Webform submissions to Google Sheets.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_googlesheets'),
      'category' => 'integration',
    ];

    // Integration: Webform HubSpot.
    $projects['hubspot'] = [
      'title' => $this->t('Webform HubSpot'),
      'description' => $this->t('Provides HubSpot leads API integration with Drupal.'),
      'url' => Url::fromUri('https://www.drupal.org/project/hubspot'),
      'category' => 'integration',
    ];

    // Integration: Webform Jira Integration.
    $projects['webform_jira'] = [
      'title' => $this->t('Webform Jira Integration'),
      'description' => $this->t('Provides integration for webform submission with Jira.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_jira'),
      'category' => 'integration',
    ];

    // Integrations: Webform MailChimp.
    $projects['webform_mailchimp'] = [
      'title' => $this->t('Webform MailChimp'),
      'description' => $this->t('Posts form submissions to MailChimp list.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_mailchimp'),
      'category' => 'integration',
    ];

    // Integrations: Webform Mattermost.
    $projects['webform_mattermost'] = [
      'title' => $this->t('Webform Mattermost'),
      'description' => $this->t('Adds a handler for sending webform submissions to Mattermost'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_mattermost'),
      'category' => 'integration',
    ];

    // Integrations: Webform Mautic.
    $projects['webform_mautic'] = [
      'title' => $this->t('Webform Mautic'),
      'description' => $this->t('Integrates your Webform submissions with Mautic form submissions.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_mautic'),
      'category' => 'integration',
    ];

    // Integrations: Webform MyEmma.
    $projects['webform_myemma'] = [
      'title' => $this->t('Webform MyEmma'),
      'description' => $this->t('Provides MyEmma subscription field to webforms.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_myemma'),
      'category' => 'integration',
    ];

    // Integrations: Webform Newsletter2Go.
    $projects['webform_newsletter2go'] = [
      'title' => $this->t('Webform Newsletter2Go'),
      'description' => $this->t('Provides Newsletter2Go Webform Integration.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_newsletter2go'),
      'category' => 'integration',
    ];

    // Integrations: Webform Pardot.
    $projects['webform_pardot'] = [
      'title' => $this->t('Webform Pardot'),
      'description' => $this->t('Links commerce products to webform elements.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_pardot'),
      'category' => 'integration',
    ];

    // Integrations: Webform Product.
    $projects['webform_product'] = [
      'title' => $this->t('Webform Product'),
      'description' => $this->t('Provides a webform handler for posting submissions to Pardot.'),
      'url' => Url::fromUri('https://github.com/chx/webform_product'),
      'category' => 'integration',
    ];

    // Integrations: Webform Simplenews Handler.
    $projects['webform_simplenews_handler'] = [
      'title' => $this->t('Webform Simplenews Handler'),
      'description' => $this->t('Provides a Webform Handler called "Submission Newsletter" that allows to link webform submission to one or more Simplenews newsletter subscriptions.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_simplenews_handler'),
      'category' => 'integration',
    ];

    // Integrations: Webform Slack integration.
    $projects['webform_slack'] = [
      'title' => $this->t('Webform Slack'),
      'description' => $this->t('Provides a Webform handler for posting a message to a slack channel when a submission is saved.'),
      'url' => Url::fromUri('https://www.drupal.org/sandbox/smaz/2833275'),
      'experimental' => TRUE,
      'category' => 'integration',
    ];

    // Integrations: Webform Stripe integration.
    $projects['stripe_webform'] = [
      'title' => $this->t('Webform Stripe'),
      'description' => $this->t('Provides a stripe webform element and default handlers.'),
      'url' => Url::fromUri('https://www.drupal.org/project/stripe_webform'),
      'category' => 'integration',
    ];

    // Integrations: Webform SugarCRM Integration.
    $projects['webform_sugarcrm'] = [
      'title' => $this->t('Webform SugarCRM Integration'),
      'description' => $this->t('Provides integration for webform submission with SugarCRM.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_sugarcrm'),
      'category' => 'integration',
    ];

    // Integrations: Webform to Paypal.
    $projects['webform_to_paypal'] = [
      'title' => $this->t('Webform to Paypal'),
      'description' => $this->t('Adds extra fields and settings to webforms to integrate with Paypal.'),
      'url' => Url::fromUri('https://github.com/IE-Digital/webform_to_paypal'),
      'category' => 'integration',
    ];

    // Integrations: Webform User Registration.
    $projects['webform_user_registration'] = [
      'title' => $this->t('Webform User Registration'),
      'description' => $this->t('Create a new user upon form submission.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_user_registration'),
      'category' => 'integration',
    ];

    // Integrations: Webform Zendesk.
    $projects['zendesk_webform'] = [
      'title' => $this->t('Webform Zendesk'),
      'description' => $this->t('Adds a webform handler to create Zendesk tickets from Drupal webform submissions.'),
      'url' => Url::fromUri('https://github.com/strakers/zendesk-drupal-webform'),
      'category' => 'integration',
    ];

    /**************************************************************************/

    // Integrations: Salesforce Web-to-Lead Webform Data Integration.
    $projects['sfweb2lead_webform'] = [
      'title' => $this->t('Salesforce Web-to-Lead Webform Data Integration'),
      'description' => $this->t('Integrates Salesforce Web-to-Lead Form feature with various webforms.'),
      'url' => Url::fromUri('https://www.drupal.org/project/sfweb2lead_webform'),
      'category' => 'integration',
    ];

    // Integrations: Salesforce Marketing Cloud API Integration.
    $projects['marketing_cloud'] = [
      'title' => $this->t('Salesforce Marketing Cloud API Integration'),
      'description' => $this->t('Gives Drupal the ability to communicate with Marketing Cloud.'),
      'url' => Url::fromUri('https://www.drupal.org/project/marketing_cloud'),
      'category' => 'integration',
    ];

    // Integrations: Salesforce: Webform to Salesforce Leads.
    $projects['webform_to_leads'] = [
      'title' => $this->t('Salesforce: Webform to Salesforce Leads'),
      'description' => $this->t('Extends the Webform module to allow the creation of a webform that feeds to your Salesforce.com Account'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_to_leads'),
      'category' => 'integration',
    ];

    /**************************************************************************/
    // Mail.
    /**************************************************************************/

    // Mail: Mail System.
    $projects['mailsystem'] = [
      'title' => $this->t('Mail System'),
      'description' => $this->t('Provides a user interface for per-module and site-wide mail system selection.'),
      'url' => Url::fromUri('https://www.drupal.org/project/mailsystem'),
      'category' => 'mail',
    ];

    // Mail: Mail System: SendGrid Integration.
    $projects['sendgrid_integration'] = [
      'title' => $this->t('SendGrid Integration <em>(requires Mail System)</em>'),
      'description' => $this->t('Provides SendGrid Integration for the Drupal Mail System.'),
      'url' => Url::fromUri('https://www.drupal.org/project/sendgrid_integration'),
      'category' => 'mail',
    ];

    // Mail: Queue Mail.
    $projects['queue_mail'] = [
      'title' => $this->t('Queue Mail'),
      'description' => $this->t('Queues webform email sending so that instead of being sent immediately it is sent on cron or via some other queue processor.'),
      'url' => Url::fromUri('https://www.drupal.org/project/queue_mail'),
      'category' => 'mail',
    ];

    // Mail: SMTP Authentication Support.
    $projects['smtp'] = [
      'title' => $this->t('SMTP Authentication Support'),
      'description' => $this->t('Allows for site emails to be sent through an SMTP server of your choice.'),
      'url' => Url::fromUri('https://www.drupal.org/project/smtp'),
      'category' => 'mail',
    ];

    // Mail: Mail System: Swift Mailer.
    $projects['swiftmailer'] = [
      'title' => $this->t('Swift Mailer <em>(requires Mail System)</em>'),
      'description' => $this->t('Installs Swift Mailer as a mail system.'),
      'url' => Url::fromUri('https://www.drupal.org/project/swiftmailer'),
      'category' => 'mail',
    ];

    // Mail: Webform Email Reply.
    $projects['webform_email_reply'] = [
      'title' => $this->t('Webform Email Reply'),
      'description' => $this->t('A webform helper module that allows users to send an email reply to submissions.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_email_reply'),
      'category' => 'mail',
    ];

    // Mail: Webform Embed.
    $projects['webform_embed'] = [
      'title' => $this->t('Webform Embed'),
      'description' => $this->t('Allows you to embed webforms within an iframe on another site.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_embed'),
      'category' => 'mail',
    ];

    // Mail: Webform Mass Email.
    $projects['webform_mass_email'] = [
      'title' => $this->t('Webform Mass Email'),
      'description' => $this->t('Provides a functionality to send mass email for the subscribers of a webform.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_mass_email'),
      'category' => 'mail',
    ];

    // Mail: Webform Send Multiple Emails.
    $projects['webform_send_multiple_emails'] = [
      'title' => $this->t('Webform Send Multiple Emails'),
      'description' => $this->t('Extends the Webform module Email Handler to send individual emails when multiple recipients are added to the email "to" field.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_send_multiple_emails'),
      'category' => 'mail',
    ];

    /**************************************************************************/
    // Multilingual.
    /**************************************************************************/

    // Multilingual: Lingotek Translation.
    $projects['lingotek'] = [
      'title' => $this->t('Lingotek Translation.'),
      'description' => $this->t('Translates content, configuration, and interface using the Lingotek Translation Management System.'),
      'url' => Url::fromUri('https://www.drupal.org/project/lingotek'),
      'category' => 'multilingual',
    ];

    // Multilingual: Webform Translation Permissions.
    $projects['webform_translation_permissions'] = [
      'title' => $this->t('Webform Translation Permissions'),
      'description' => $this->t("Defines the following permissions to enable a user to translate a webform's configuration without granting them the 'translate configuration' permission needlessly."),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_translation_permissions'),
      'category' => 'multilingual',
    ];

    /**************************************************************************/
    // Migrate.
    /**************************************************************************/

    // Migrate: Webform Migrate.
    $projects['webform_migrate'] = [
      'title' => $this->t('Webform Migrate'),
      'description' => $this->t('Provides migration routines from d6, d7 webform to d8 webform.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_migrate'),
      'category' => 'migrate',
      'recommended' => TRUE,
    ];

    /**************************************************************************/
    // Spam.
    /**************************************************************************/

    // Spam: Antibot.
    $projects['antibot'] = [
      'title' => $this->t('Antibot'),
      'description' => $this->t('Prevent forms from being submitted without JavaScript enabled.'),
      'url' => Url::fromUri('https://www.drupal.org/project/antibot'),
      'category' => 'spam',
      'third_party_settings' => TRUE,
      'recommended' => TRUE,
    ];

    // Spam: CAPTCHA.
    $projects['captcha'] = [
      'title' => $this->t('CAPTCHA'),
      'description' => $this->t('Provides CAPTCHA for adding challenges to arbitrary forms.'),
      'url' => Url::fromUri('https://www.drupal.org/project/captcha'),
      'category' => 'spam',
      'recommended' => TRUE,
    ];

    // Spam: Honeypot.
    $projects['honeypot'] = [
      'title' => $this->t('Honeypot'),
      'description' => $this->t('Mitigates spam form submissions using the honeypot method.'),
      'url' => Url::fromUri('https://www.drupal.org/project/honeypot'),
      'category' => 'spam',
      'third_party_settings' => TRUE,
      'recommended' => TRUE,
    ];

    /**************************************************************************/

    // Spam: CleanTalk.
    $projects['cleantalk'] = [
      'title' => $this->t('CleanTalk'),
      'description' => $this->t('Antispam service from CleanTalk to protect your site.'),
      'url' => Url::fromUri('https://www.drupal.org/project/cleantalk'),
      'category' => 'spam',
    ];

    // Spam: Human Presence Form Protection.
    $projects['hp'] = [
      'title' => $this->t('Human Presence Form Protection'),
      'description' => $this->t('Human Presence is a fraud prevention and form protection service that uses multiple overlapping strategies to fight form spam.'),
      'url' => Url::fromUri('https://www.drupal.org/project/hp'),
      'category' => 'spam',
    ];

    // Spam: Protected Submissions.
    $projects['protected_submissions'] = [
      'title' => $this->t('Protected Submissions'),
      'description' => $this->t('A light-weight, non-intrusive spam protection module that enables rejection of webform submissions which contain preset patterns.'),
      'url' => Url::fromUri('https://www.drupal.org/project/protected_submissions'),
      'category' => 'spam',
    ];

    /**************************************************************************/
    // Submissions.
    /**************************************************************************/

    // Submissions: Webform Analysis.
    $projects['webform_analysis'] = [
      'title' => $this->t('Webform Analysis'),
      'description' => $this->t('Used to obtain statistics on the results of form submissions.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_analysis'),
      'category' => 'submission',
      'recommended' => TRUE,
    ];

    // Submissions: Webform Query.
    $projects['webform_query'] = [
      'title' => $this->t('Webform Query'),
      'description' => $this->t('Query webform submission data.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_query'),
      'category' => 'submission',
      'recommended' => TRUE,
    ];

    // Submissions: Webform Views Integration.
    $projects['webform_views'] = [
      'title' => $this->t('Webform Views'),
      'description' => $this->t('Integrates Webform 8.x-5.x and Views modules.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_views'),
      'category' => 'submission',
      'recommended' => TRUE,
    ];

    /**************************************************************************/

    // Submissions: Protected Submissions.
    $projects['protected_submissions'] = [
      'title' => $this->t('Protected Submissions'),
      'description' => $this->t('Prevents submissions that contain undesired patterns.'),
      'url' => Url::fromUri('https://www.drupal.org/project/protected_submissions'),
      'category' => 'submission',
    ];

    // Submissions: Webform Auto Exports.
    $projects['coc_forms_auto_export'] = [
      'title' => $this->t('Webform Auto Exports'),
      'description' => $this->t('Automatic export for Drupal Webform results.'),
      'url' => Url::fromUri('https://www.drupal.org/project/coc_forms_auto_export'),
      'category' => 'submission',
    ];

    // Submissions: Webform double opt-in.
    $projects['webform_double_opt_in'] = [
      'title' => $this->t('Webform double opt-in'),
      'description' => $this->t('Provides e-mail double opt-in functionality.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_double_opt_in'),
      'category' => 'submission',
    ];

    // Submissions: Webform Invitation.
    $projects['webform_invitation'] = [
      'title' => $this->t('Webform Invitation'),
      'description' => $this->t('Allows you to restrict submissions to a webform by generating codes (which may then be distributed e.g. by email to participants).'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_invitation'),
      'category' => 'submission',
    ];

    // Submissions: Webform Permissions By Term.
    $projects['webform_permissions_by_term'] = [
      'title' => $this->t('Webform Permissions By Term'),
      'description' => $this->t('Extends the functionality of Permissions By Term to be able to limit the webform submissions access by users or roles.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_permissions_by_term'),
      'category' => 'submission',
    ];

    // Submissions: Webform Queue.
    $projects['webform_queue'] = [
      'title' => $this->t('Webform Queue'),
      'description' => $this->t('Posts form submissions into a Drupal queue.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_queue'),
      'category' => 'submission',
    ];

    // Submissions: Webform Sanitize.
    $projects['webform_sanitize'] = [
      'title' => $this->t('Webform Sanitize'),
      'description' => $this->t('Sanitizes submissions to remove potentially sensitive data.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_sanitize'),
      'category' => 'submission',
    ];

    // Submissions: Webform Scheduled Tasks.
    $projects['webform_scheduled_tasks'] = [
      'title' => $this->t('Webform Scheduled Tasks'),
      'description' => $this->t('Allows the regular cleansing/sanitization of sensitive fields in Webform.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_scheduled_tasks'),
      'category' => 'submission',
    ];

    // Submissions: Webform Submission Change History.
    $projects['webform_submission_change_history'] = [
      'title' => $this->t('Webform Submission Change History'),
      'description' => $this->t('Allows administrators to track notes on webform submissions.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_submission_change_history'),
      'category' => 'submission',
    ];

    // Submissions: Webform Submissions Delete.
    $projects['webform_submissions_delete'] = [
      'title' => $this->t('Webform Submissions Delete'),
      'description' => $this->t('Used to delete webform submissions using start date, end date all at once.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_submissions_delete'),
      'category' => 'submission',
    ];

    // Submissions: Webform Submissions Notification.
    $projects['webform_digests'] = [
      'title' => $this->t('Webform Submissions Notification'),
      'description' => $this->t('Adds a daily digest email for webform submissions.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_digests'),
      'category' => 'submission',
    ];

    // Submissions: Webform Submission Files Download.
    $projects['webform_submission_files_download'] = [
      'title' => $this->t('Webform Submission Files Download'),
      'description' => $this->t('Allows you to download files attached to a single submission'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_submission_files_download'),
      'category' => 'submission',
    ];

    // Submissions: Webform Views Extras.
    $projects['webform_views_extras'] = [
      'title' => $this->t('Webform Views Extras'),
      'description' => $this->t('Extends Webform views and supports relationships in views with all content entities not only node.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_views_extras'),
      'category' => 'submission',
    ];

    // Submissions: Webform XLSX Export.
    $projects['webform_xlsx_export'] = [
      'title' => $this->t('Webform XLSX Export'),
      'description' => $this->t('Exports Webform submissions in the Office Open XML format.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_xlsx_export'),
      'category' => 'submission',
    ];

    /**************************************************************************/
    // Utility.
    /**************************************************************************/

    // Utility: IMCE.
    $projects['imce'] = [
      'title' => $this->t('IMCE'),
      'description' => $this->t('IMCE is an image/file uploader and browser that supports personal directories and quota.'),
      'url' => Url::fromUri('https://www.drupal.org/project/imce'),
      'category' => 'utility',
      'install' => $this->t('The IMCE module makes it easier to update images to webforms and elements.'),
      'recommended' => TRUE,
    ];

    // Utility: Token.
    $projects['token'] = [
      'title' => $this->t('Token'),
      'description' => $this->t('Provides a user interface for the Token API and some missing core tokens.'),
      'url' => Url::fromUri('https://www.drupal.org/project/token'),
      'category' => 'utility',
      'install' => $this->t('The Token module allows site builders to browser available webform-related tokens.'),
      'recommended' => TRUE,
    ];

    /**************************************************************************/

    // Utility: Googalytics Webform.
    $projects['ga_webform'] = [
      'title' => $this->t('Googalytics Webform'),
      'description' => $this->t('Provides integration for Webform into Googalytics module.'),
      'url' => Url::fromUri('https://www.drupal.org/project/ga_webform'),
      'category' => 'utility',
    ];

    // Utility: General Data Protection Regulation Compliance.
    $projects['gdpr_compliance'] = [
      'title' => $this->t('General Data Protection Regulation Compliance'),
      'description' => $this->t('Provides Basic GDPR Compliance use cases via form checkboxes, pop-up alert, and a policy page.'),
      'url' => Url::fromUri('https://www.drupal.org/project/gdpr_compliance'),
      'category' => 'utility',
    ];

    // Utility: EU Cookie Compliance.
    $projects['eu_cookie_compliance'] = [
      'title' => $this->t('EU Cookie Compliance'),
      'description' => $this->t('This module aims at making the website compliant with the new EU cookie regulation.'),
      'url' => Url::fromUri('https://www.drupal.org/project/eu_cookie_compliance'),
      'category' => 'utility',
    ];

    // Utility: Formdazzle!
    $projects['formdazzle'] = [
      'title' => $this->t('Formdazzle!'),
      'description' => $this->t('Provides a set of utilities that make form theming easier.'),
      'url' => Url::fromUri('https://www.drupal.org/project/formdazzle'),
      'category' => 'utility',
    ];

    // Utility: Webform Encrypt.
    $projects['wf_encrypt'] = [
      'title' => $this->t('Webform Encrypt'),
      'description' => $this->t('Provides encryption for webform elements.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_encrypt'),
      'category' => 'utility',
    ];

    // Utility: Webform Ip Track.
    $projects['webform_ip_track'] = [
      'title' => $this->t('Webform Ip Track'),
      'description' => $this->t('Ip Location details as custom tokens to use in webform submission values.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_ip_track'),
      'category' => 'utility',
    ];

    // Utility: Webform Config Key Value.
    $projects['	webform_config_key_value'] = [
      'title' => $this->t('Webform Config Key Value'),
      'description' => $this->t('Use the KeyValueStorage to save webform config instead of yaml config storage, allowing webforms to be treated more like content than configuration and are excluded from the configuration imports/exports.'),
      'url' => Url::fromUri('https://www.drupal.org/sandbox/thtas/2994250'),
      'experimental' => TRUE,
      'category' => 'utility',
    ];

    /**************************************************************************/
    // Validation.
    /**************************************************************************/

    // Validation: Clientside Validation.
    $projects['clientside_validation'] = [
      'title' => $this->t('Clientside Validation'),
      'description' => $this->t('Adds clientside validation to forms.'),
      'url' => Url::fromUri('https://www.drupal.org/project/clientside_validation'),
      'category' => 'validation',
      'recommended' => TRUE,
    ];

    // Validation: Telephone Validation.
    $projects['telephone_validation'] = [
      'title' => $this->t('Telephone Validation'),
      'description' => $this->t('Provides validation for tel form element.'),
      'url' => Url::fromUri('https://www.drupal.org/project/telephone_validation'),
      'category' => 'validation',
    ];

    // Validation: Validators.
    $projects['validators'] = [
      'title' => $this->t('Validators'),
      'description' => $this->t('Provides Symfony (form) Validators for Drupal 8.'),
      'url' => Url::fromUri('https://www.drupal.org/project/validators'),
      'category' => 'validation',
    ];

    // Webform Handler: Compare Fields.
    $projects['webform_handler_compare_fields'] = [
      'title' => $this->t('Webform Handler: Compare Fields'),
      'description' => $this->t('Validation handler to compare two fields on a webform.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_handler_compare_fields'),
      'category' => 'validation',
    ];

    /**************************************************************************/
    // Web services.
    /**************************************************************************/

    // Web services: Gatsby Drupal Webform.
    $projects['gatsby_drupal_webform'] = [
      'title' => $this->t('Gatsby Drupal Webform'),
      'description' => $this->t('React component and graphql fragments for webforms. Goal of this project is to have a react component that generates bootstrap like HTML from webform YAML configuration.'),
      'url' => Url::fromUri('https://github.com/oikeuttaelaimille/gatsby-drupal-webform'),
      'category' => 'web_services',
    ];

    // Web services: Webform REST.
    $projects['webform_rest'] = [
      'title' => $this->t('Webform REST'),
      'description' => $this->t('Retrieve and submit webforms via REST.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_rest'),
      'category' => 'web_services',
    ];

    // Web services: Webform JSON Schema.
    $projects['webform_jsonschema'] = [
      'title' => $this->t('Webform JSON Schema'),
      'description' => $this->t('Expose webforms as JSON Schema, UI Schema, and Form Data. Make webforms work with react-jsonschema-form.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_jsonschema'),
      'category' => 'web_services',
    ];

    /**************************************************************************/
    // Workflow.
    /**************************************************************************/

    // Workflow: Config Entity Revisions.
    $projects['config_entity_revisions'] = [
      'title' => $this->t('Config Entity Revisions'),
      'description' => $this->t('Provide revisions and moderation for Webforms.'),
      'url' => Url::fromUri('https://www.drupal.org/project/config_entity_revisions'),
      'category' => 'workflow',
      'recommended' => TRUE,
    ];

    // Workflow: Maestro.
    $projects['maestro'] = [
      'title' => $this->t('Maestro Workflow Engine'),
      'description' => $this->t('A business process workflow solution that allows you to create and automate a sequence of tasks representing any business, document approval or collaboration process.'),
      'url' => Url::fromUri('https://www.drupal.org/project/maestro'),
      'category' => 'workflow',
      'recommended' => TRUE,
    ];

    /**************************************************************************/
    // Development.
    /**************************************************************************/

    // Devel: Maillog / Mail Developer.
    $projects['maillog'] = [
      'title' => $this->t('Maillog / Mail Developer'),
      'description' => $this->t('Utility to log all Mails for debugging purposes. It is possible to suppress mail delivery for e.g. dev or staging systems.'),
      'url' => Url::fromUri('https://www.drupal.org/project/maillog'),
      'category' => 'development',
      'recommended' => TRUE,
    ];

    // Devel: Webform Submissions List Decorator.
    $projects['maillog'] = [
      'title' => $this->t('Webform Submissions List Decorator'),
      'description' => $this->t('Override submissions list and allows user hide columns of webform submissions in submissions list.'),
      'url' => Url::fromUri('https://www.drupal.org/sandbox/antonkerbel/3098999'),
      'category' => 'development',
    ];

    // Add logos.
    global $base_url;
    $addon_paths = drupal_get_path('module', 'webform') . '/images/addons';
    $addon_extensions = ['png', 'svg'];
    foreach ($projects as $project_name => $project) {
      foreach ($addon_extensions as $addon_extension) {
        if (file_exists("$addon_paths/$project_name.$addon_extension")) {
          $projects[$project_name]['logo'] = Url::fromUri("$base_url/$addon_paths/$project_name.$addon_extension");
        }
      }
    }

    $this->projects = $projects;
  }

}
