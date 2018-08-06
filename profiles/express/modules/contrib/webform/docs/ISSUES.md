Known Issues
------------

Below are known Drupal 8 core issues that are affecting the Webform module.

### Configuration Management

**[Issue #2808287: Importing Webform config file via the UI is throwing serialization error](https://www.drupal.org/node/2808287)**

> Importing configuration files using Drush is working fine.

**[Issue #1920902: Unable to tidy the bulk export of Webform and WebformOptions config files 
because Drupal's YAML utility is not a service.](https://www.drupal.org/node/1920902)**

> The Webform module provides drush commands to 'tidy' exported YAML and
> configuration files that so they are easier to read and edit.

### Form Elements
    
**[Drupal core webforms system issues](https://www.drupal.org/project/issues/drupal?status=Open&version=8.x&component=forms+system)**
  
> Any changes, improvements, and bug fixes for Drupal's Form API may directly
> impact the Webform module.
  
- [Issue #1593964: Allow FAPI usage of the datalist element](https://www.drupal.org/node/1593964)

**[Issue #2502195: Regression: Webform throws LogicException when trying to render a webform with object as an element's default value.](https://www.drupal.org/node/2502195)**  

> Impacts previewing entity autocomplete elements.

**[Issue #2207383: Create a tooltip component](https://www.drupal.org/node/2207383)**

> Impacts displaying element description in a tooltip. jQuery UI's tooltip's UX
> is not great.

**[Issue #2741877: Nested modals don't work: when using CKEditor in a modal, then clicking the image button opens another modal, which closes the original modal](https://www.drupal.org/node/2741877)**

> Makes it impossible to display the CKEditor in a dialog.
> Workaround: Use custom download of CKEditor which include a CKEditor specific 
> link dialog.

### \#states API (Conditionals)

#### Button (button & submit)

**[Issue #1671190 by Lucasljj, idebr, Cameron Tod: Use <button /> webform element type instead of <input type="submit" />](https://www.drupal.org/node/1671190)**

#### Date/time (datetime)

**[Issue #2419131: #states attribute does not work on #type datetime](https://www.drupal.org/node/2419131)**

#### Details (details)

**[Issue #2348851: Regression: Allow HTML tags inside detail summary](https://www.drupal.org/node/2348851)**

#### Item (item)

**[Issue #783438: #states doesn't work for #type item](https://www.drupal.org/node/783438)**

#### HTML markup (markup)

**[Issue #2700667: Notice: Undefined index: #type in drupal_process_states()](https://www.drupal.org/node/2700667)**

#### Managed file (managed_file)

**[Issue #2705471: Webform states managed file fields](https://www.drupal.org/node/2705471)**

#### Password confirm (password_confirm)

**[Issue #1427838: password and password_confirm children do not pick up #states or #attributes](https://www.drupal.org/node/1427838)**

#### Select (select)

**[Issue #1426646: "-Select-" option is lost when webform elements uses '#states'](https://www.drupal.org/node/1426646)**

**[Issue #1149078: States API doesn't work with multiple select fields](https://www.drupal.org/node/1149078)**

**[Issue #2791741: FAPI states: fields aren't hidden initially when depending on multi-value selection](https://www.drupal.org/node/2791741)**

#### Radios (radios)

**[Issue #2731991: Setting required on radios marks all options required](https://www.drupal.org/node/2731991)**

**[Issue #994360: #states cannot disable/enable radios and checkboxes](https://www.drupal.org/node/994360)**

#### Text format (text_format)

**[Issue #997826: #states doesn't work correctly with type text_format](https://www.drupal.org/node/997826)**

**[Issue #2625128: Text format selection stays visible when using editor and a hidden webform state](https://www.drupal.org/node/2625128)**

### Submission Display

**[Issue #2484693: Telephone Link field formatter breaks Drupal with 5 digits or less in the number](https://www.drupal.org/node/2720923)**

> Workaround is to manually build a static HTML link.
> See: \Drupal\webform\Plugin\WebformElement\Telephone::formatHtml

### Access Control

**[Issue #2636066: Access control is not applied to config entity queries](https://www.drupal.org/node/2636066)**

> Workaround: Manually check webform access.
> See: Drupal\webform\WebformEntityListBuilder

### User Interface

**[Issue #2235581: Make Token Dialog support inserting in WYSIWYGs (TinyMCE, CKEditor, etc.)](https://www.drupal.org/node/2235581)**

> This blocks tokens from being inserted easily into the CodeMirror widget.
> Workaround: Disable '\#click_insert' functionality from the token dialog.
   
**Config entity does NOT support [Entity Validation API](https://www.drupal.org/node/2015613)**

> Validation constraints are only applicable to content entities and fields.
>
> In D8 all config entity validation is handled via 
  \Drupal\Core\Form\FormInterface::validateForm
>
> Workaround: Created the WebformEntityElementsValidator service.      
  
**[Issue #2585169: Unable to alter local actions prior to rendering](https://www.drupal.org/node/2585169)**

> Makes it impossible to open an action in a dialog.  
> Workaround: Add local action to a controller's response.

### CKEditor

**[Issue #2741877 by jrockowitz: Nested modals don't work: when using CKEditor in a modal, then clicking the image button opens another modal, which closes the original modal](https://www.drupal.org/node/2741877)**

- Prevent core's CKEditor from being used inside modal dialog.

**[Issue #2852346: Switch Enter <p> and Shift+Enter<br>](option https://www.drupal.org/node/2852346)**

> CKEditor does not include basic link and image plugin.
