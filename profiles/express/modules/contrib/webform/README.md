Webform 8.x-5.x
---------------

### About this Module

The Webform module is a form builder and submission manager for Drupal 8.

The primary use case for this module is to:

- **Build** a new webform or duplicate an existing template
- **Publish** the webform as a page, node, or block
- **Collect** submissions
- **Send** confirmations and notifications
- **Review** submissions online
- **Download** submissions as a CSV


### Goals

- A comprehensive form and survey building solution for Drupal 8. 
- A stable, maintainable, and tested API for building forms and handling submission.
- A pluggable/extensible API for custom form elements and submission handling. 


### Demo

> [Watch a demo](http://youtu.be/sQGsfQ_LZJ4) of the Webform module.

> Evaluate this project online using [simplytest.me](https://simplytest.me/project/webform).


### Blog Posts, Articles, & Presentations

Blog Posts & Articles

- [https://dev.acquia.com/blog/drupal-8-module-of-the-week--webform-formerly-known-as-yaml-form/07/03/2017/17741](https://dev.acquia.com/blog/drupal-8-module-of-the-week--webform-formerly-known-as-yaml-form/07/03/2017/17741)
- [Drupal 8 Module Webform picks up speed](https://internetdevels.com/blog/drupal-8-module-webform)
- [Check Out: The Benefits of Drupal 8 Webform](http://www.capitalnumbers.com/blog/check-out-the-benefits-of-drupal-8-webform/)
- [How to Make a Complex Webform in Drupal 8](https://www.ostraining.com/blog/drupal/how-to-make-a-complex-webform-in-drupal-8/)
- [3 Reasons We Love the Webform Module in Drupal 8](https://www.unleashed-technologies.com/blog/2017/04/07/3-reasons-we-love-webform-module-drupal-8)
- [Getting NYU onto Webform](https://www.fourkitchens.com/blog/article/getting-nyu-yaml-form)
- [Webforms for Drupal 8](https://www.gaiaresources.com.au/yaml-forms-drupal-8/)
- [Creating Webform Handlers in Drupal 8](http://fivemilemedia.co.uk/blog/creating-yaml-form-handlers-drupal-8)
- [Les formulaires en Drupal 8](https://makina-corpus.com/blog/metier/2016/les-formulaires-en-drupal-8)

Videos
- [3 Reasons We Love the Webform Module in Drupal 8</a></li>](https://www.unleashed-technologies.com/blog/2017/04/07/3-reasons-we-love-webform-module-drupal-8)

Presentations

- [Webform and Drupal 8 @ DrupalCamp London 2016](https://www.youtube.com/watch?v=xrWEizVqAR4&t=1333s) \[Video\]
- [Webform and Drupal 8 @ DrupalCamp London 2016](https://www.slideshare.net/philipnorton42/webform-and-drupal-8) \[Slides\]
- [Drupal 8 Webform: When Contact Form Isn't Enough](http://pnwdrupalsummit.org/sites/default/files/slides/PNWDS%202017%20Catherine%20Winters%20webforms.pdf)  \[Slides\]
- [Webform 8.x-5.x @ NJ DrupalCamp](https://www.drupalcampnj.org/program/sessions/building-webforms-drupal-8) \[Video\]

### Installing the Webform Module

1. Copy/upload the webform module to the modules directory of your Drupal
   installation.

2. Enable the 'Webform' module and desired sub-modules in 'Extend'. 
   (/admin/modules)

3. Set up user permissions. (/admin/people/permissions#module-webform)

4. Build a new webform (/admin/structure/webform)
   or duplicate an existing template (/admin/structure/webform/templates).
   
5. Publish your webform as a:

    - **Page:** By linking to the published webform.
      (/webform/contact)  

    - **Node:** By creating a new node that references the webform.
      (/node/add/webform)

    - **Block:** By placing a Webform block on your site.
      (/admin/structure/block)

6. (optional) Install third party libraries(/admin/help/webform).

7. (optional) Install add-on contrib modules](/admin/structure/webform/addons).


### Releases

Even though the Webform module is still under active development with
regular [beta releases](https://www.drupal.org/documentation/version-info/alpha-beta-rc),
all existing configuration and submission data will be maintained and updated 
between releases.  **APIs can and will be changing** while this module moves 
from beta releases to a final release candidate. 

Simply put, if you install and use the Webform module out of the box AS-IS, 
you _should_ be okay.  Once you start extending webforms with plugins, altering 
hooks, and overriding templates, you will need to read each release's 
notes and assume that _things will be changing_.


### Project Status

- [Webform Project Board](https://contribkanban.com/board/webform/8.x-5.x)
- [Webform 4.x features currently missing from the Webform module](https://www.drupal.org/node/2807571)


### Similar Modules


- **[Comparison of Webform Building Modules](https://www.drupal.org/node/2083353)**  
  Drupal has a lot of modules aimed at helping site builders and users add webforms 
  to their sites. The [Comparison of Webform Building Modules](https://www.drupal.org/node/2083353) 
  page includes rough comparisons of three of them for Drupal 8 and five of them
  for Drupal 7. 

---

- **[Contact](https://www.drupal.org/documentation/modules/contact) + 
  [Contact Storage](https://www.drupal.org/project/contact_storage)**    
  The Contact module allows site visitors to send emails to other authenticated 
  users and to the site administrator. The Contact Storage module provides 
  storage for Contact messages which are fully-fledged entities in Drupal 8.
  Many of its features are likely to be moved into Drupal Core.

- **[Eform](https://www.drupal.org/project/eform)**  
  The EForm module enables you to create front-end webforms (fieldable entities), 
  which contain fields that you define! These webforms use the standard Drupal 
  fields.  
  [Is this module still needed?](https://www.drupal.org/node/2809179)
