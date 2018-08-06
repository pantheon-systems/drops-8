Features
--------

## Form Builder

<div class="thumbnail">
<a href="https://www.drupal.org/files/webform-8.x-5.x-builder.png">
<img src="https://www.drupal.org/files/webform-8.x-5.x-builder.png" alt="Form Builder" />
</a>
</div>

The Webform module provides an intuitive webform builder based upon Drupal 8's 
best practices for user interface and user experience. The webform builder allows non-technical users to easily build and maintain webforms.

Form builder features include:

- Drag-n-drop webform element management
- Generation of test submissions
- Duplication of existing webforms, templates, and elements


## Form Settings

<div class="thumbnail">
<a href="https://www.drupal.org/files/webform-8.x-5.x-settings.png">
<img src="https://www.drupal.org/files/webform-8.x-5.x-settings-thumbnail.png" alt="Form Settings" />
</a>
</div>

Form submission handling, messaging, and confirmations are completely 
customizable using global settings and/or form-specific settings.
 
Form settings that can be customized include:

- Messages and button labels
- Confirmation page, messages, and redirects
- Saving drafts
- Previewing submissions
- Confidential submissions
- Prepopulating a webform's elements using query string parameters
- Preventing duplicate submissions 
- Disabling back button
- Warning users about unsaved changes
- Disabling client-side validation
- Limiting number of submission per user, per webform, and/or per node
- Look-n-feel of webform, confirmation page, and buttons
- Injection webform specific CSS and JavaScript


## Elements

<div class="thumbnail">
<a href="https://www.drupal.org/files/webform-8.x-5.x-elements.png">
<img src="https://www.drupal.org/files/webform-8.x-5.x-elements-thumbnail.png" alt="Elements" />
</a>
</div>

The Webform module is built directly on top of Drupal 8's Form API. Every
[form element](https://api.drupal.org/api/drupal/developer!topics!forms_api_reference.html/8) 
available in Drupal 8 is supported by the Webform module.

Form elements include:

- **HTML:** Textfield, Textareas, Checkboxes, Radios, Select menu, 
  Password, and more...
- **HTML5:** Email, Url, Number, Telephone, Date, Number, Range, 
  and more...
- **Drupal specific** File uploads, Entity References, Table select, Date list, 
  and more...
- **Custom:** [Likert scale](https://en.wikipedia.org/wiki/Likert_scale), 
  Star rating, Toggle, Buttons, Credit card number, Geolocation, 
  Select/Checkboxes/Radios with other, and more...
- **Markups** Inline dismissable messages, HTML Markup, Details, and Fieldsets.   
- **Composite elements:** Name, Address, Contact, and Credit Card 


## Element Settings

<div class="thumbnail">
<a href="https://www.drupal.org/files/webform-8.x-5.x-element-settings.png">
<img src="https://www.drupal.org/files/webform-8.x-5.x-element-settings-thumbnail.png" alt="Element Settings" />
</a>
</div>

All of Drupal 8's default webform element properties and behaviors are supported. 
There are also several custom webform element properties and settings
available to enhance a webform element's behavior.
 
Standard and custom properties allow for:

- **Customizable required error messages**
- **Conditional logic** using [FAPI States API](https://api.drupal.org/api/examples/form_example%21form_example_states.inc/function/form_example_states_form/7)
- **Input masks** (using [jquery.inputmask](https://github.com/RobinHerbots/jquery.inputmask))
- **[Select2](https://select2.github.io/)** replacement of select boxes 
- **Word and character counting** for text elements
- **Help popup** (using [jQuery UI Tooltip](https://jqueryui.com/tooltip/))
- **Regular expression pattern validation**
- **Private** elements, visible only to administrators
- **Unique** values per element


## Viewing Source

<div class="thumbnail">
<a href="https://www.drupal.org/files/webform-8.x-5.x-source.png">
<img src="https://www.drupal.org/files/webform-8.x-5.x-source-thumbnail.png" alt="Viewing Source" />
</a>
</div>

At the heart of a Webform module's webform elements is a Drupal render array,
which can be edited and managed by developers. The Drupal render array gives developers
complete control over a webform's elements, layout, and look-and-feel by
allowing developers to make bulk updates to a webform's label, descriptions, and 
behaviors.


## States/Conditional Logic

<div class="thumbnail">
<a href="https://www.drupal.org/files/webform-8.x-5.x-states.png">
<img src="https://www.drupal.org/files/webform-8.x-5.x-states.png" alt="States/Conditional Logic" />
</a>
</div>

Drupal's State API can be used by developers to provide conditional logic to 
hide and show webform elements.

Drupal's State API supports:

- Show/Hide
- Open/Close
- Enable/Disable


## Multistep Forms

<div class="thumbnail">
<a href="https://www.drupal.org/files/webform-8.x-5.x-wizard.png">
<img src="https://www.drupal.org/files/webform-8.x-5.x-wizard.png" alt="Multistep Forms" />
</a>
</div>

Forms can be broken up into multiple pages using a progress bar. Authenticated
users can save drafts and/or have their changes automatically saved as they 
progress through a long webform.

Multistep webform features include:

- Customizable progress bar
- Customizable previous and next button labels and styles
- Saving drafts between steps


## Email & Remote Post Handlers

<div class="thumbnail">
<a href="https://www.drupal.org/files/webform-8.x-5.x-handlers.png">
<img src="https://www.drupal.org/files/webform-8.x-5.x-handlers-thumbnail.png" alt="Email/Handlers" />
</a>
</div>

Upon webform submission, customizable email notifications and confirmations can
be sent to users and administrators. 

An extendable plugin that allows developers to push submitted data 
to external or internal systems and/or applications is provided. 

Email support features include:

- Previewing and resending emails
- Sending HTML emails
- File attachments (requires the [Mail System](https://www.drupal.org/project/mailsystem) and [Swift Mailer](https://www.drupal.org/project/swiftmailer) module.) 
- HTML and plain-text email-friendly Twig templates
- Customizable display formats for individual webform elements

Remote post features include:

- Posting selected elements to remote server
- Adding custom parameters to remote post requests


## Results Management

<div class="thumbnail">
<a href="https://www.drupal.org/files/webform-8.x-5.x-results.png">
<img src="https://www.drupal.org/files/webform-8.x-5.x-results-thumbnail.png" alt="Results Management" />
</a>
</div>

Form submissions can optionally be stored in the database, reviewed, and
downloaded.  

Submissions can also be flagged with administrative notes.

Results management features include:

- Flagging
- Administrative notes 
- Viewing submissions as HTML, plain text, and YAML
- Customizable reports
- Downloading results as a CSV to Google Sheets or MS Excel
- Saving of download preferences per form
- Automatically purging old submissions based on certain criteria


## Access Controls

<div class="thumbnail">
<a href="https://www.drupal.org/files/webform-8.x-5.x-access.png">
<img src="https://www.drupal.org/files/webform-8.x-5.x-access-thumbnail.png" alt="Access Controls" />
</a>
</div>

The Webform module provides full access controls for managing who can create
forms, post submissions, and access a webform's results.  
Access controls can be applied to roles and/or specific users.

Access controls allow users to:

- Create new forms
- Update forms
- Delete forms
- View submissions
- Update submissions
- Delete submissions
- View selected elements
- Update selected elements


## Reusable Templates

<div class="thumbnail">
<a href="https://www.drupal.org/files/webform-8.x-5.x-templates.png">
<img src="https://www.drupal.org/files/webform-8.x-5.x-templates-thumbnail.png" alt="Reusable Templates" />
</a>
</div>

The Webform module provides a few starter templates and multiple example forms
which webform administrators can update or use to create new reusable templates
for their organization.

Starter templates include:

- Contact Us	
- Donation
- Employee Evaluation
- Issue
- Job Application	
- Job Seeker Profile
- Registration
- Session Evaluation
- Subscribe
- User Profile

Example webforms include:

- Elements
- Basic layout
- Flexbox layout
- Input masks
- Options
- Wizard


## Reusable Options

<div class="thumbnail">
<a href="https://www.drupal.org/files/webform-8.x-5.x-options.png">
<img src="https://www.drupal.org/files/webform-8.x-5.x-options-thumbnail.png" alt="Reusable Options" />
</a>
</div>

Administrators can define reusable global options for select menus, checkboxes, 
and radio buttons. The Webform module includes default options for states,
countries, [likert](https://en.wikipedia.org/wiki/Likert_scale) answers, 
and more.   

Reusable options include:

- Country codes & names	
- Credit card codes
- Days, Months, Time zones
- Education, Employment status, Ethnicity, Industry, Languages, Marital status, Relationship, Size, and Titles
- Likert agreement, comparison, importance, quality, satisfaction, ten scale, and
  would you
- State/province codes & names	
- State codes	& names		


## Internationalization
    
<div class="thumbnail">
<a href="https://www.drupal.org/files/webform-8.x-5.x-internalization.png">
<img src="https://www.drupal.org/files/webform-8.x-5.x-internalization-thumbnail.png" alt="Internationalization" />
</a>
</div>

Forms and configuration can be translated into multiple languages using Drupal's
configuration translation system.    


## Drupal Integration

<div class="thumbnail">
<a href="https://www.drupal.org/files/webform-8.x-5.x-integration.png">
<img src="https://www.drupal.org/files/webform-8.x-5.x-integration-thumbnail.png" alt="Drupal Integration" />
</a>
</div>

Forms can be attached to nodes or displayed as blocks. Webforms can also have
dedicated SEO-friendly URLs. Webform elements are simply render arrays that can
easily be altered using custom hooks and/or plugins.


## Add-ons & Third Party Settings

<div class="thumbnail">
<a href="https://www.drupal.org/files/webform-8.x-5.x-add-ons.png">
<img src="https://www.drupal.org/files/webform-8.x-5.x-add-ons-thumbnail.png" alt="Add-ons & Third Party Settings" />
</a>
</div>

Includes a list of modules and projects that extend and/or provide additional 
functionality to the Webform module and Drupal's Form API.


## Extendable Plugins

<div class="thumbnail">
<a href="https://www.drupal.org/files/webform-8.x-5.x-plugin.png">
<img src="https://www.drupal.org/files/webform-8.x-5.x-plugin.png" alt="Extendable Plugins" />
</a>
</div>

The Webform module provides [plugins](https://www.drupal.org/developing/api/8/plugins)
and hooks that allow contrib and custom modules to extend and enhance webform 
elements and submission handling.

**WebformElement plugin** is used to integrate and enhance webform elements so 
that they can be properly integrated into the Webform module.

**WebformHandler plugin** allows developers to extend a webform's submission 
handling. 

**WebformExporter plugin** allows developers to export results using custom
formats and file types. 


## Help & Video Tutorials

<div class="thumbnail">
<a href="https://www.drupal.org/files/webform-8.x-5.x-help.png">
<img src="https://www.drupal.org/files/webform-8.x-5.x-help-thumbnail.png" alt="Help & Video Tutorials" />
</a>
</div>

The Webform module provides examples, inline help, and screencast walk throughs.

Screencasts include:
 
- [Welcome to the Webform module](https://youtu.be/sQGsfQ_LZJ4)
- [Installing the Webform module and third party libraries](https://youtu.be/IMfFTrsjg5k)
- [Managing Webforms, Templates, and Examples](https://youtu.be/T5MVGa_3jOQ)
- [Adding Elements, Composites, and Containers](https://youtu.be/LspF9mAvRcY)
- [Configuring Webform Settings and Behaviors](https://youtu.be/UJ0y09ZS9Uc)
- [Controlling Access to Webforms and Elements](https://youtu.be/SFm76DAVjbE)
- [Collecting Submissions, Sending Emails, and Posting Results](https://youtu.be/OdfVm5LMH9A)
- [Placing Webforms in Blocks and Creating Webform Nodes](https://youtu.be/xYBW2g0osd4)
- [Administering and Extending the Webform module](https://youtu.be/bkScAX_Qbt4)
- [Using the Source](https://youtu.be/2pWkJiYeR6E)
- [Getting Help](https://youtu.be/sRXUR2c2brA) 


## Drush Integration

<div class="thumbnail">
<a href="https://www.drupal.org/files/webform-8.x-5.x-drush.png">
<img src="https://www.drupal.org/files/webform-8.x-5.x-drush.png" alt="Drush Integration" />
</a>
</div>

Drush commands are provided to:

- Generate multiple webform submissions
- Export webform submissions
- Purge webform submissions
- Download and manage third party libraries
- Tidy YAML configuration files
