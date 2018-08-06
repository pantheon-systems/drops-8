Metatag
-------
This module allows a site's builder to automatically provide structured
metadata, aka "meta tags", about the site and individual pages.

In the context of search engine optimization, providing an extensive set of
meta tags may help improve the site's and pages' rankings, thus may aid with
achieving a more prominent display of the content within search engine results.
They can also be used to tailor how content is displayed when shared on social
networks.

For additional information, see the online documentation:
  https://www.drupal.org/docs/8/modules/metatag

This version should work with all Drupal 8 releases, though it is always
recommended to keep Drupal core installations up to date.


Requirements
--------------------------------------------------------------------------------
Metatag for Drupal 8 requires the following:

* Token
  https://www.drupal.org/project/token
  Provides a popup browser to see the available tokens for use in meta tag
  fields.


Features
--------------------------------------------------------------------------------
The primary features include:

* An administration interface to manage default meta tags.

* Use of standard fields for entity support, allowing for translation and
  revisioning of meta tag values added for individual entities.

* A large volume of meta tags available, covering commonly used tags, Open
  Graph tags, Twitter Cards tags, Dublin Core tags, Google+ tags, App Links
  tags, site verification tags and more; all but the basic meta tags are kept
  in separate submodules.

* The fifteen Dublin Core Basic Element Set 1.1 meta tags may be added by
  enabling the "Metatag: Dublin Core" submodule.

* The Open Graph Protocol meta tags, as used by Facebook, Pinterest, LinkedIn
  and other sites, may be added by enabling the "Metatag: Open Graph" submodule.

* The Twitter Cards meta tags may be added by enabling the "Metatag: Twitter
  Cards" submodule.

* Certain meta tags used by Google+ may be added by enabling the "Metatag:
  Google+" submodule.

* Facebook's fb:app_id, fb:admins and fb:pages meta tags may be added by
  enabling the "Metatag: Facebook" submodule. These are useful for sites which
  are using Facebook widgets or are building custom integration with Facebook's
  APIs, but they are not needed by most sites and have no bearing on the
  Open Graph meta tags.

* Site verification meta tags can be added, e.g. as used by the Google search
  engine to confirm ownership of the site; see the "Metatag: Verification"
  submodule.

* The Metatag: Mobile & UI Adjustments submodule adds the MobileOptimized,
  HandheldFriendly, viewport, cleartype, theme-color, format-detection,
  apple-mobile-web-app-capable, apple-mobile-web-app-status-bar-style, the
  android-app and ios-app alternative link meta tags, and the Android manifest
  tag.

* The hreflang meta tags are available via the Metatag:hreflang submodule.

* The App Links meta tags may be added by enabling the Metatag: App Links
  submodule.

* Support for meta tags specific to Google Custom Search Appliance are available
  in the "Metatag: Google Custom Search Engine (CSE)" submodule.

* Meta tags specific to Facebook are included in the "Metatag: Facebook"
  submodule.

* A plugin interface allowing for additional meta tags to be easily added via
  custom modules.

* Integration with DrupalConsole [1] to provide a quick method of generating new
  meta tags.


Standard usage scenario
--------------------------------------------------------------------------------
1. Install the module.
2. Open admin/config/search/metatag.
3. Adjust global and entity defaults. Fill in reasonable default values for any
   of the meta tags that need to be customized. Tokens may be used to
   automatically assign values.
4. Additional bundle defaults may be added by clicking on "Add metatag
   defaults" and filling out the form.
5. To adjust metatags for a specific entity, the Metatag field must be added
   first. Follow these steps:

   5.1 Go to the "Manage fields" of the bundle where the Metatag field is to
       appear.
   5.2 Select "Meta tags" from the "Add a new field" selector.
   5.3 Fill in a label for the field, e.g. "Meta tags", and set an appropriate
       machine name, e.g. "meta_tags".
   5.4 Click the "Save and continue" button.
   5.5 If the site supports multiple languages, and translations have been
       enabled for this entity, select "Users may translate this field" to use
       Drupal's translation system.


Programmatically assign meta tags to an entity
--------------------------------------------------------------------------------
There are two ways to assign an entity's meta tags in custom module. Both
scenarios require a "Metatag" field be added to the entity's field settings, the
field name "field_meta_tags" is used but this is completely arbitrary.

Option 1:

  $entity_type = 'node';
  $values = [
    'nid' => NULL,
    'type' => 'article',
    'title' => 'Testing metatag creation',
    'uid' => 1,
    'status' => TRUE,
    'field_meta_tags' => serialize([
      'title' => 'Some title',
      'description' => 'Some description.',
      'keywords' => 'Some,Keywords',
    ]),
  ];
  $node = \Drupal::entityTypeManager()->getStorage($entity_type)->create($values);
  $node->save();

Option 2:

  $node = Node::create([
    'type' => article,
    'langcode' => 'en',
    'status' => 1,
    'uid' => 1,
  ]);
  $node->set('title', 'Testing metatag creation');
  $node->set('field_meta_tags', serialize([
    'title' => 'Some title',
    'description' => 'Some description.',
    'keywords' => 'Some,Keywords',
  ]));
  $node->save();

In both examples, the custom meta tag values will still be merged with the
values defined via the global defaults prior to being output - it is not
necessary to copy each value to the new record.


DrupalConsole integration
--------------------------------------------------------------------------------
Using the DrupalConsole, it is possible to generate new meta tags, either for
use in new custom modules that require custom meta tags, or to create patches
for extending Metatag's options.

To generate a new tag, install DrupalConsole and then use the following command:

  drupal generate:plugin:metatag:tag

This will guide the site builder through the necessary steps to create a new
meta tag plugin and add it to a module.

There is also a command for generating meta tag groups:

  drupal generate:plugin:metatag:group

Again, this provides a guided process to create a new group.


Related modules
--------------------------------------------------------------------------------
Some modules are available that extend Metatag with additional or complimentary
functionality:

* Schema Metatag
  https://www.drupal.org/project/schema_metatag
  Extensive solution for adding schema.org support to Metatag.

* Context Metadata
  https://www.drupal.org/project/context_metadata
  Allow assignment of meta tags based upon different system contexts, e.g. per
  path.

* Real-time SEO for Drupal
  https://www.drupal.org/project/yoast_seo
  Uses the YoastSEO.js library and service (https://yoast.com/) to provide
  realtime feedback on the meta tags.

* Metatag Cxense
  https://www.drupal.org/project/metatag_cxense
  Adds support for the Cxense meta tags used by their DMP and Insight services.

* Metatag Google Scholar
  https://www.drupal.org/project/metatag_google_scholar
  Adds support for a number of meta tags used with the Google Scholar system.


Known issues
--------------------------------------------------------------------------------
* In order to uninstall the module any "Metatag" fields must first be removed
  from all entities. In order to see whether there are fields blocking the
  module from being uninstalled, load the module uninstall page
  (admin/modules/uninstall) and see if any are listed, it will look something
  like the following:
    The Meta tags field type is used in the following field:
    node.field_meta_tags
  In order to uninstall the module, go to the appropriate field settings pages
  and remove the Metatag field listed in the message. Once this is done it will
  be possible to uninstall the module.


Credits / contact
--------------------------------------------------------------------------------
Currently maintained by Damien McKenna [2] and Dave Reid [3]. Drupal 7 module
originally written by Dave Reid. Early work on Drupal 8 port by Damien McKenna
and Michelle Cox [4], and sponsored by Mediacurrent [5]; key improvements by
Juampy Novillo Requena [6] with insights from Dave Reid and sponsorship by
Lullabot [7] and Acquia [8]. Additional contributions to the 8.x-1.0 release
from cilefen [9], Daniel Wehner [10], Jesus Manuel Olivas [11], Lee Rowlands
[12], Michael Kandelaars [13], Ivo Van Geertruyen [14], Nikhilesh Gupta B [15],
Rakesh James [16], and many others.

Ongoing development is sponsored by Mediacurrent.

The best way to contact the authors is to submit an issue, be it a support
request, a feature request or a bug report, in the project issue queue:
  https://www.drupal.org/project/issues/metatag


References
--------------------------------------------------------------------------------
1: https://www.drupal.org/project/console
2: https://www.drupal.org/u/damienmckenna
3: https://www.drupal.org/u/dave-reid
4: https://www.drupal.org/u/michelle
5: https://www.mediacurrent.com/
6: https://www.drupal.org/u/juampynr
7: https://www.lullabot.com/
8: https://www.acquia.com/
9: https://www.drupal.org/u/cilefen
10: https://www.drupal.org/u/dawehner
11: https://www.drupal.org/u/jmolivas
12: https://www.drupal.org/u/larowlan
13: https://www.drupal.org/u/mikeyk
14: https://www.drupal.org/u/mr.baileys
15: https://www.drupal.org/u/nikhilesh-gupta
16: https://www.drupal.org/u/rakeshgectcr
