<?php

/**
 * @file
 * Contains \Drupal\pathologic\PathologicSettingsCommon.
 *
 * This is a hack (I think… not sure if this is a sane way to do this) in order
 * to get common field elements to show up on both the global and local filter
 * instance config forms without repeating code.
 */

namespace Drupal\pathologic;

use Drupal\Core\StringTranslation\StringTranslationTrait;

class PathologicSettingsCommon {

  use StringTranslationTrait;

  /**
   * Common elements for the Pathologic configuration form.
   *
   * This reduces redundancy in code for form elements that will appear on both
   * the global settings form and the per-format filter settings form.
   *
   * @param array $defaults
   *   An array of default values for the configuration form fields.
   * @return
   *   The common form elements
   */
  public function commonSettingsForm(array $defaults) {
    return array(
      'protocol_style' => array(
        '#type' => 'radios',
        '#title' => $this->t('Processed URL format'),
        '#default_value' => $defaults['protocol_style'],
        '#options' => array(
          'full' => $this->t('Full URL (<code>http://example.com/foo/bar</code>)'),
          'proto-rel' => $this->t('Protocol relative URL (<code>//example.com/foo/bar</code>)'),
          'path' => $this->t('Path relative to server root (<code>/foo/bar</code>)'),
        ),
        '#description' => t('The <em>Full URL</em> option is best for stopping broken images and links in syndicated content (such as in RSS feeds), but will likely lead to problems if your site is accessible by both HTTP and HTTPS. Paths output with the <em>Protocol relative URL</em> option will avoid such problems, but feed readers and other software not using up-to-date standards may be confused by the paths. The <em>Path relative to server root</em> option will avoid problems with sites accessible by both HTTP and HTTPS with no compatibility concerns, but will absolutely not fix broken images and links in syndicated content.'),
        '#weight' => 10,
      ),
      'local_paths' => array(
        '#type' => 'textarea',
        '#title' =>  $this->t('All base paths for this site'),
        '#default_value' => $defaults['local_paths'],
          '#description' => $this->t('If this site is or was available at more than one base path or URL, enter them here, separated by line breaks. For example, if this site is live at <code>http://example.com/</code> but has a staging version at <code>http://dev.example.org/staging/</code>, you would enter both those URLs here. If confused, please read <a href=":docs" target="_blank">Pathologic&rsquo;s documentation</a> for more information about this option and what it affects.', array(':docs' => 'https://www.drupal.org/node/257026')),
        '#weight' => 20,
      ),
    );
  }
  
}
