<?php

namespace Drupal\views_slideshow_cycle\Plugin\ViewsSlideshowType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views_slideshow\ViewsSlideshowTypeBase;
use Drupal\Core\Link;

/**
 * Provides a slideshow type based on jquery cycle.
 *
 * @ViewsSlideshowType(
 *   id = "views_slideshow_cycle",
 *   label = @Translation("Cycle"),
 *   accepts = {
 *     "goToSlide",
 *     "nextSlide",
 *     "pause",
 *     "play",
 *     "previousSlide"
 *   },
 *   calls = {
 *     "transitionBegin",
 *     "transitionEnd",
 *     "goToSlide",
 *     "pause",
 *     "play",
 *     "nextSlide",
 *     "previousSlide"
 *   }
 * )
 */
class Cycle extends ViewsSlideshowTypeBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'contains' => [
        // Transition.
        'effect' => ['default' => 'fade'],
        'transition_advanced' => ['default' => 0],
        'timeout' => ['default' => 5000],
        'speed' => ['default' => 700],
        'delay' => ['default' => 0],
        'sync' => ['default' => 1],
        'random' => ['default' => 0],
        // Action.
        'pause' => ['default' => 1],
        'pause_on_click' => ['default' => 0],
        'action_advanced' => ['default' => 0],
        'start_paused' => ['default' => 0],
        'remember_slide' => ['default' => 0],
        'remember_slide_days' => ['default' => 1],
        'pause_in_middle' => ['default' => 0],
        'pause_when_hidden' => ['default' => 0],
        'pause_when_hidden_type' => ['default' => 'full'],
        'amount_allowed_visible' => ['default' => ''],
        'nowrap' => ['default' => 0],
        'fixed_height' => ['default' => 1],
        'items_per_slide' => ['default' => 1],
        'items_per_slide_first' => array('default' => FALSE),
        'items_per_slide_first_number' => array('default' => 1),
        'wait_for_image_load' => ['default' => 1],
        'wait_for_image_load_timeout' => ['default' => 3000],

        // Internet Explorer Tweaks.
        'cleartype' => ['default' => 'true'],
        'cleartypenobg' => ['default' => 'false'],

        // Advanced.
        'advanced_options' => ['default' => '{}'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $cycle = \Drupal::service('library.discovery')->getLibraryByName('views_slideshow_cycle', 'jquery_cycle');
    if (!isset($cycle['js'][0]['data']) || !file_exists($cycle['js'][0]['data'])) {
      $form['views_slideshow_cycle']['no_cycle_js'] = [
        '#markup' => '<div style="color: red">' . $this->t('You need to install the jQuery cycle plugin. Create a directory in libraries (which should be in your Drupal root folder, if not create the same) called jquery.cycle, and then copy jquery.cycle.all.js into it. You can find the plugin at @url.',
          [
            '@url' => Link::fromTextAndUrl('http://malsup.com/jquery/cycle', Url::FromUri('http://malsup.com/jquery/cycle'), [
              'attributes' => ['target' => '_blank'],
            ])->toString(),
          ]) . '</div>',
      ];
    }

    // Transition.
    $form['views_slideshow_cycle']['transition'] = [
      '#markup' => '<h2>' . $this->t('Transition') . '</h2>',
    ];

    $effects = [
      'none' => 'none',
      'blindX' => 'blindX',
      'blindY' => 'blindY',
      'blindZ' => 'blindZ',
      'cover' => 'cover',
      'curtainX' => 'curtainX',
      'curtainY' => 'curtainY',
      'fade' => 'fade',
      'fadeZoom' => 'fadeZoom',
      'growX' => 'growX',
      'growY' => 'growY',
      'scrollUp' => 'scrollUp',
      'scrollDown' => 'scrollDown',
      'scrollLeft' => 'scrollLeft',
      'scrollRight' => 'scrollRight',
      'scrollHorz' => 'scrollHorz',
      'scrollVert' => 'scrollVert',
      'shuffle' => 'shuffle',
      'slideX' => 'slideX',
      'slideY' => 'slideY',
      'toss' => 'toss',
      'turnUp' => 'turnUp',
      'turnDown' => 'turnDown',
      'turnLeft' => 'turnLeft',
      'turnRight' => 'turnRight',
      'uncover' => 'uncover',
      'wipe' => 'wipe',
      'zoom' => 'zoom',
    ];
    $form['views_slideshow_cycle']['effect'] = [
      '#type' => 'select',
      '#title' => $this->t('Effect'),
      '#options' => $effects,
      '#default_value' => $this->getConfiguration()['effect'],
      '#description' => $this->t('The transition effect that will be used to change between images. Not all options below may be relevant depending on the effect. <a href="http://jquery.malsup.com/cycle/browser.html" target="_black">Follow this link to see examples of each effect.</a>'),
    ];

    // Transition advanced options.
    $form['views_slideshow_cycle']['transition_advanced'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('View Transition Advanced Options'),
      '#default_value' => $this->getConfiguration()['transition_advanced'],
    ];

    // Need to wrap this so it indents correctly.
    $form['views_slideshow_cycle']['transition_advanced_wrapper'] = [
      '#markup' => '<div class="vs-dependent">',
    ];

    $form['views_slideshow_cycle']['timeout'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Timer delay'),
      '#default_value' => $this->getConfiguration()['timeout'],
      '#description' => $this->t('Amount of time in milliseconds between transitions. Set the value to 0 to not rotate the slideshow automatically.'),
      '#states' => [
        'visible' => [
          ':input[name="style_options[views_slideshow_cycle][transition_advanced]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['views_slideshow_cycle']['speed'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Speed'),
      '#default_value' => $this->getConfiguration()['speed'],
      '#description' => $this->t('Time in milliseconds that each transition lasts. Numeric only!'),
      '#states' => [
        'visible' => [
          ':input[name="style_options[views_slideshow_cycle][transition_advanced]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['views_slideshow_cycle']['delay'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Initial slide delay offset'),
      '#default_value' => $this->getConfiguration()['delay'],
      '#description' => $this->t('Amount of time in milliseconds for the first slide to transition. This number will be added to Timer delay to create the initial delay.  For example if Timer delay is 4000 and Initial delay is 2000 then the first slide will change at 6000ms (6 seconds).  If Initial delay is -2000 then the first slide will change at 2000ms (2 seconds).'),
      '#states' => [
        'visible' => [
          ':input[name="style_options[views_slideshow_cycle][transition_advanced]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['views_slideshow_cycle']['sync'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Sync'),
      '#default_value' => $this->getConfiguration()['sync'],
      '#description' => $this->t('The sync option controls whether the slide transitions occur simultaneously. The default is selected which means that the current slide transitions out as the next slide transitions in. By unselecting this option you can get some interesting twists on your transitions.'),
      '#states' => [
        'visible' => [
          ':input[name="style_options[views_slideshow_cycle][transition_advanced]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['views_slideshow_cycle']['random'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Random'),
      '#description' => $this->t('This option controls the order items are displayed. The default setting, unselected, uses the views ordering. Selected will cause the images to display in a random order.'),
      '#default_value' => $this->getConfiguration()['random'],
      '#states' => [
        'visible' => [
          ':input[name="style_options[views_slideshow_cycle][transition_advanced]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['views_slideshow_cycle']['transition_advanced_wrapper_close'] = [
      '#markup' => '</div>',
    ];

    // Action.
    $form['views_slideshow_cycle']['action'] = [
      '#markup' => '<h2>' . $this->t('Action') . '</h2>',
    ];
    $form['views_slideshow_cycle']['pause'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Pause on hover'),
      '#default_value' => $this->getConfiguration()['pause'],
      '#description' => $this->t('Pause when hovering on the slideshow image.'),
    ];
    $form['views_slideshow_cycle']['pause_on_click'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Pause On Click'),
      '#default_value' => $this->getConfiguration()['pause_on_click'],
      '#description' => $this->t('Pause when the slide is clicked.'),
    ];

    // Action Advanced Options.
    $form['views_slideshow_cycle']['action_advanced'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('View Action Advanced Options'),
      '#default_value' => $this->getConfiguration()['action_advanced'],
    ];

    // Need to wrap this so it indents correctly.
    $form['views_slideshow_cycle']['action_advanced_wrapper'] = [
      '#markup' => '<div class="vs-dependent">',
    ];

    $form['views_slideshow_cycle']['start_paused'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Start Slideshow Paused'),
      '#default_value' => $this->getConfiguration()['start_paused'],
      '#description' => $this->t('Start the slideshow in the paused state.'),
      '#states' => [
        'visible' => [
          ':input[name="style_options[views_slideshow_cycle][action_advanced]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['views_slideshow_cycle']['remember_slide'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Start On Last Slide Viewed'),
      '#default_value' => $this->getConfiguration()['remember_slide'],
      '#description' => $this->t('When the user leaves a page with a slideshow and comes back start them on the last slide viewed.'),
      '#states' => [
        'visible' => [
          ':input[name="style_options[views_slideshow_cycle][action_advanced]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['views_slideshow_cycle']['remember_slide_days'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Length of Time to Remember Last Slide'),
      '#default_value' => $this->getConfiguration()['remember_slide_days'],
      '#description' => $this->t('The number of days to have the site remember the last slide. Default is 1'),
      '#prefix' => '<div class="vs-dependent">',
      '#suffix' => '</div>',
      '#size' => 4,
      '#states' => [
        'visible' => [
          ':input[name="style_options[views_slideshow_cycle][action_advanced]"]' => ['checked' => TRUE],
          ':input[name="style_options[views_slideshow_cycle][remember_slide]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // @todo Check if there is a better way to detect optional libraries.
    $pause = \Drupal::service('library.discovery')->getLibraryByName('views_slideshow_cycle', 'jquery_pause');
    if (isset($pause['js'][0]['data']) && file_exists($pause['js'][0]['data'])) {
      $form['views_slideshow_cycle']['pause_in_middle'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Pause The Slideshow In The Middle of the Transition'),
        '#default_value' => $this->getConfiguration()['pause_in_middle'],
        '#description' => $this->t('When pausing the slideshow allow it to pause in the middle of tranistioning and not finish the transition until unpaused.'),
        '#states' => [
          'visible' => [
            ':input[name="style_options[views_slideshow_cycle][transition_advanced]"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    $form['views_slideshow_cycle']['pause_when_hidden'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Pause When the Slideshow is Not Visible'),
      '#default_value' => $this->getConfiguration()['pause_when_hidden'],
      '#description' => $this->t('When the slideshow is scrolled out of view or when a window is resized that hides the slideshow, this will pause the slideshow.'),
      '#states' => [
        'visible' => [
          ':input[name="style_options[views_slideshow_cycle][action_advanced]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['views_slideshow_cycle']['pause_when_hidden_type'] = [
      '#type' => 'select',
      '#title' => $this->t('How to Calculate Amount of Slide that Needs to be Shown'),
      '#options' => [
        'full' => $this->t('Entire slide'),
        'vertical' => $this->t('Set amount of vertical'),
        'horizontal' => $this->t('Set amount of horizontal'),
        'area' => $this->t('Set total area of the slide'),
      ],
      '#default_value' => $this->getConfiguration()['pause_when_hidden_type'],
      '#description' => $this->t('Choose how to calculate how much of the slide has to be shown. Entire Slide: All the slide has to be shown. Vertical: Set amount of height that has to be shown. Horizontal: Set amount of width that has to be shown. Area: Set total area that has to be shown.'),
      '#prefix' => '<div class="vs-dependent">',
      '#suffix' => '</div>',
      '#states' => [
        'visible' => [
          ':input[name="style_options[views_slideshow_cycle][action_advanced]"]' => ['checked' => TRUE],
          ':input[name="style_options[views_slideshow_cycle][pause_when_hidden]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['views_slideshow_cycle']['amount_allowed_visible'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Amount of Slide Needed to be Shown'),
      '#default_value' => $this->getConfiguration()['amount_allowed_visible'],
      '#description' => $this->t("The amount of the slide that needs to be shown to have it rotate. You can set the value in percentage (ex: 50%) or in pixels (ex: 250). The slidehsow will not rotate until it's height/width/total area, depending on the calculation method you have chosen above, is less than the value you have entered in this field."),
      '#size' => 4,
    ];
    $form['views_slideshow_cycle']['#attached']['library'][] = 'views_slideshow_cycle/formoptions';
    $form['views_slideshow_cycle']['nowrap'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('End slideshow after last slide'),
      '#default_value' => $this->getConfiguration()['nowrap'],
      '#description' => $this->t('If selected the slideshow will end when it gets to the last slide.'),
      '#states' => [
        'visible' => [
          ':input[name="style_options[views_slideshow_cycle][action_advanced]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['views_slideshow_cycle']['fixed_height'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Make the slide window height fit the largest slide'),
      '#default_value' => $this->getConfiguration()['fixed_height'],
      '#description' => $this->t('If unselected then if the slides are different sizes the height of the slide area will change as the slides change.'),
      '#states' => [
        'visible' => [
          ':input[name="style_options[views_slideshow_cycle][action_advanced]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['views_slideshow_cycle']['items_per_slide'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Items per slide'),
      '#default_value' => $this->getConfiguration()['items_per_slide'],
      '#description' => $this->t('The number of items per slide'),
      '#size' => 4,
      '#states' => [
        'visible' => [
          ':input[name="style_options[views_slideshow_cycle][action_advanced]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['views_slideshow_cycle']['items_per_slide_first'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Different first slide'),
      '#default_value' => $this->getConfiguration()['items_per_slide_first'],
      '#description' => $this->t('Different number of items for the first slide'),
      '#states' => [
        'visible' => [
          ':input[name="style_options[views_slideshow_cycle][action_advanced]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['views_slideshow_cycle']['items_per_slide_first_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Items for first slide'),
      '#default_value' => $this->getConfiguration()['items_per_slide_first_number'],
      '#description' => $this->t('The number of items for the first slide'),
      '#size' => 4,
      '#states' => [
        'visible' => [
          ':input[name="style_options[views_slideshow_cycle][action_advanced]"]' => ['checked' => TRUE],
          ':input[name="style_options[views_slideshow_cycle][items_per_slide_first]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['views_slideshow_cycle']['wait_for_image_load'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Wait for all the slide images to load'),
      '#default_value' => $this->getConfiguration()['wait_for_image_load'],
      '#description' => $this->t('If selected the slideshow will not start unless all the slide images are loaded.  This will fix some issues on IE7/IE8/Chrome/Opera.'),
      '#states' => [
        'visible' => [
          ':input[name="style_options[views_slideshow_cycle][action_advanced]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['views_slideshow_cycle']['wait_for_image_load_timeout'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Timeout'),
      '#default_value' => $this->getConfiguration()['wait_for_image_load_timeout'],
      '#description' => $this->t('How long should it wait until it starts the slideshow anyway. Time is in milliseconds.'),
      '#prefix' => '<div class="vs-dependent">',
      '#suffix' => '</div>',
      '#states' => [
        'visible' => [
          ':input[name="style_options[views_slideshow_cycle][action_advanced]"]' => ['checked' => TRUE],
          ':input[name="style_options[views_slideshow_cycle][wait_for_image_load]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Need to wrap this so it indents correctly.
    $form['views_slideshow_cycle']['action_advanced_wrapper_close'] = [
      '#markup' => '</div>',
    ];

    // Internet Explorer Tweaks.
    $form['views_slideshow_cycle']['ie_tweaks'] = [
      '#markup' => '<h2>' . $this->t('Internet Explorer Tweaks') . '</h2>',
    ];
    $form['views_slideshow_cycle']['cleartype'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('ClearType'),
      '#default_value' => $this->getConfiguration()['cleartype'],
      '#description' => $this->t('Select if clearType corrections should be applied (for IE).  Some background issues could be fixed by unselecting this option.'),
    ];
    $form['views_slideshow_cycle']['cleartypenobg'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('ClearType Background'),
      '#default_value' => $this->getConfiguration()['cleartypenobg'],
      '#description' => $this->t('Select to disable extra cleartype fixing.  Unselect to force background color setting on slides)'),
    ];

    // Advanced Options.
    $form['views_slideshow_cycle']['advanced_options_header'] = [
      '#markup' => '<h2>' . $this->t('jQuery Cycle Custom Options') . '</h2>',
    ];

    // @todo Check if there is a better way to detect optional libraries.
    $json2 = \Drupal::service('library.discovery')->getLibraryByName('views_slideshow_cycle', 'json2');
    if (!isset($json2['js'][0]['data']) || !file_exists($json2['js'][0]['data'])) {
      // @todo Check if there is a better way to create this target _blank link.
      $form['views_slideshow_cycle']['no_json_js'] = [
        '#markup' => '<div>' . $this->t('To use the advanced options you need to download json2.js. You can do this by clicking the download button at <a href="https://github.com/douglascrockford/JSON-js" target="_black">https://github.com/douglascrockford/JSON-js</a> and extract json2.js to libraries/json2') . '</div>',
      ];
    }
    else {
      // @todo Check if there is a better way to create this target _blank link.
      $form['views_slideshow_cycle']['advanced_options_info'] = [
        '#markup' => '<p>' . $this->t('You can find a list of all the available options at <a href="http://malsup.com/jquery/cycle/options.html" target="_blank">http://malsup.com/jquery/cycle/options.html</a>. If one of the options you add uses a function, example fxFn, then you need to only enter what goes inside the function call. The variables that are in the documentation on the jquery cycle site will be available to you.') . '</p>',
      ];

      // All the jquery cycle options according to
      // http://malsup.com/jquery/cycle/options.html
      $cycle_options = [
        0 => 'Select One',
        'activePagerClass' => 'activePagerClass',
        'after' => 'after',
        'allowPagerClickBubble' => 'allowPagerClickBubble',
        'animIn' => 'animIn',
        'animOut' => 'animOut',
        'autostop' => 'autostop',
        'autostopCount' => 'autostopCount',
        'backwards' => 'backwards',
        'before' => 'before',
        'bounce' => 'bounce',
        'cleartype' => 'cleartype',
        'cleartypeNoBg' => 'cleartypeNoBg',
        'containerResize' => 'containerResize',
        'continuous' => 'continuous',
        'cssAfter' => 'cssAfter',
        'cssBefore' => 'cssBefore',
        'delay' => 'delay',
        'easeIn' => 'easeIn',
        'easeOut' => 'easeOut',
        'easing' => 'easing',
        'end' => 'end',
        'fastOnEvent' => 'fastOnEvent',
        'fit' => 'fit',
        'fx' => 'fx',
        'fxFn' => 'fxFn',
        'height' => 'height',
        'manualTrump' => 'manualTrump',
        'metaAttr' => 'metaAttr',
        'next' => 'next',
        'nowrap' => 'nowrap',
        'onPagerEvent' => 'onPagerEvent',
        'onPrevNextEvent' => 'onPrevNextEvent',
        'pager' => 'pager',
        'pagerAnchorBuilder' => 'pagerAnchorBuilder',
        'pagerEvent' => 'pagerEvent',
        'pause' => 'pause',
        'paused' => 'paused',
        'pauseOnPagerHover' => 'pauseOnPagerHover',
        'prev' => 'prev',
        'prevNextEvent' => 'prevNextEvent',
        'random' => 'random',
        'randomizeEffects' => 'randomizeEffects',
        'requeueOnImageNotLoaded' => 'requeueOnImageNotLoaded',
        'requeueTimeout' => 'requeueTimeout',
        'resumed' => 'resumed',
        'rev' => 'rev',
        'shuffle' => 'shuffle',
        'slideExpr' => 'slideExpr',
        'slideResize' => 'slideResize',
        'speed' => 'speed',
        'speedIn' => 'speedIn',
        'speedOut' => 'speedOut',
        'startingSlide' => 'startingSlide',
        'sync' => 'sync',
        'timeout' => 'timeout',
        'timeoutFn' => 'timeoutFn',
        'updateActivePagerLink' => 'updateActivePagerLink',
        'width' => 'width',
      ];

      $form['views_slideshow_cycle']['advanced_options_choices'] = [
        '#type' => 'select',
        '#title' => $this->t('Advanced Options'),
        '#options' => $cycle_options,
      ];

      $form['views_slideshow_cycle']['advanced_options_entry'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Advanced Option Value'),
        '#description' => $this->t('It is important that you click the Update link when you make any changes to the options or those changes will not be saved when you save the form.'),
      ];

      $form['views_slideshow_cycle']['advanced_options'] = [
        '#type' => 'textarea',
        '#default_value' => $this->getConfiguration()['advanced_options'],
      ];

      // @todo: Review how to create this table.
      $form['views_slideshow_cycle']['advanced_options_table'] = [
        '#markup' => '<table style="width: 400px; margin-left: 10px;" id="edit-style-options-views-slideshow-cycle-advanced-options-table"></table>',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue(['style_options', 'views_slideshow_cycle']);
    if (!is_numeric($values['timeout'])) {
      $form_state->setErrorByName('style_options][views_slideshow_cycle][timeout', $this->t('@setting must be numeric!',
        ['@setting' => $this->t('Timeout')]
      ));
    }
    if (!is_numeric($values['speed'])) {
      $form_state->setErrorByName('style_options][views_slideshow_cycle][speed', $this->t('@setting must be numeric!',
        ['@setting' => $this->t('Speed')]
      ));
    }
    if (!is_numeric($values['remember_slide_days'])) {
      $form_state->setErrorByName('style_options][views_slideshow_cycle][remember_slide_days', $this->t('@setting must be numeric!',
        ['@setting' => $this->t('Slide days')]
      ));
    }
  }

}
