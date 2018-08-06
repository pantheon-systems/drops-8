<?php

namespace Drupal\video_filter\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Render Video Filter.
 *
 * @Filter(
 *   id = "video_filter",
 *   title = @Translation("Video Filter"),
 *   description = @Translation("Substitutes [video:URL] with embedded HTML."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   settings = {
 *     "width" = 400,
 *     "height" = 400,
 *     "plugins" = {
 *       "youtube" = 1,
 *       "vimeo" = 1
 *     },
 *     "allow_multiple_sources" = TRUE
 *   }
 * )
 */
class VideoFilter extends FilterBase implements ContainerInjectionInterface {

  protected $plugin_manager;

  /**
   * Implements __construct().
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->plugin_manager = \Drupal::service('plugin.manager.video_filter');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('module_handler'));
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    if (preg_match_all('/\[video(\:(.+))?( .+)?\]/isU', $text, $matches_code)) {
      // Load all codecs.
      $plugins = array_filter($this->settings['plugins']);
      if (empty($this->settings['plugins'])) {
        $plugins = $this->plugin_manager->getDefinitions();
      }
      foreach ($matches_code[0] as $ci => $code) {
        $video = [
          'source'   => $matches_code[2][$ci],
        ];

        // Pick random out of multiple sources separated by comma (,).
        if ($this->settings['allow_multiple_sources'] && strstr($video['source'], ',')) {
          $sources         = explode(',', $video['source']);
          $random          = array_rand($sources, 1);
          $video['source'] = $sources[$random];
        }

        // Find codec.
        foreach ($plugins as $plugin_info) {
          $id = !empty($plugin_info['id']) ? $plugin_info['id'] : $plugin_info;
          $plugin = $this->plugin_manager->createInstance($id);
          $codec = [];
          $regexp = $plugin->getRegexp();
          $codec['regexp'] = !is_array($regexp) ? [$regexp] : $regexp;

          // Try different regular expressions.
          foreach ($codec['regexp'] as $delta => $regexp) {
            if (preg_match($regexp, $video['source'], $matches)) {
              $video['codec'] = $codec;
              $video['codec']['delta'] = $delta;
              $video['codec']['ratio'] = $plugin->getRatio();
              $video['codec']['control_bar_height'] = $plugin->getControlBarHeight();
              $video['codec']['matches'] = $matches;
              $video['codec']['id'] = $id;
              break 2;
            }
          }
        }

        // Codec found.
        if (isset($video['codec'])) {

          // Override default attributes.
          if (!empty($matches_code[3][$ci]) && preg_match_all('/\s+([a-zA-Z_]+)\:(\s+)?([0-9a-zA-Z\/]+)/i', $matches_code[3][$ci], $matches_attributes)) {
            foreach ($matches_attributes[0] as $ai => $attribute) {
              $video[$matches_attributes[1][$ai]] = $matches_attributes[3][$ai];
            }
          }

          // Use configured ratio if present, otherwise use that from the codec,
          // if set. Fall back to 1.
          $ratio = 1;
          if (!empty($video['ratio']) && preg_match('/(\d+)\/(\d+)/', $video['ratio'], $tratio)) {
            // Validate given ratio parameter.
            $ratio = $tratio[1] / $tratio[2];
          }
          elseif (!empty($video['codec']['ratio'])) {
            if (is_float($video['codec']['ratio']) || is_int($video['codec']['ratio'])) {
              $ratio = $video['codec']['ratio'];
            }
            elseif (preg_match('/(\d+)\s*\/\s*(\d+)/', $video['codec']['ratio'], $cratio)) {
              $ratio = $cratio[1] / $cratio[2];
            }
          }

          // Sets video width & height after any user input has been parsed.
          // First, check if user has set a width.
          if (isset($video['width']) && !isset($video['height'])) {
            $video['height'] = $this->settings['height'] != '' ? $this->settings['height'] : 400;
          }
          // Else, if user has set height.
          elseif (isset($video['height']) && !isset($video['width'])) {
            $video['width'] = $video['height'] * $ratio;
          }
          // Maybe both?
          elseif (isset($video['height']) && isset($video['width'])) {
            $video['width'] = $video['width'];
            $video['height'] = $video['height'];
          }
          // Fall back to defaults.
          elseif (!isset($video['height']) && !isset($video['width'])) {
            $video['width'] = $this->settings['width'] != '' ? $this->settings['width'] : 400;
            $video['height'] = $this->settings['height'] != '' ? $this->settings['height'] : 400;
          }

          // Default value for control bar height.
          $control_bar_height = 0;
          if (isset($video['control_bar_height'])) {
            // Respect control_bar_height option if present.
            $control_bar_height = $video['control_bar_height'];
          }
          elseif (isset($video['codec']['control_bar_height'])) {
            // Respect setting provided by codec otherwise.
            $control_bar_height = $video['codec']['control_bar_height'];
          }

          // Resize to fit within width and height repecting aspect ratio.
          if ($ratio) {
            $scale_factor = min([
              ($video['height'] - $control_bar_height),
              $video['width'] / $ratio,
            ]);
            $video['height'] = round($scale_factor + $control_bar_height);
            $video['width'] = round($scale_factor * $ratio);
          }

          $video['align'] = (isset($video['align']) && in_array($video['align'], [
            'left',
            'right',
            'center',
          ])) ? $video['align'] : NULL;

          // Let modules have final say on video parameters.
          \Drupal::moduleHandler()->alter('video_filter_video', $video);

          $iframe = $plugin->iframe($video);
          $flash = $plugin->flash($video);
          $html = $plugin->html($video);

          // Add CSS classes to elements.
          $video['classes'] = $this->classes($video);

          // iframe.
          if (!empty($iframe['src'])) {
            $video['iframe'] = $iframe;
            $element = [
              '#theme' => 'video_filter_iframe',
              '#video' => $video,
            ];
            $replacement = \Drupal::service('renderer')->render($element);
          }
          // flash.
          elseif (!empty($flash['src'])) {

            $defaults = [
              'movie' => $video['source'],
              'wmode' => 'transparent',
              'allowFullScreen' => 'true',
            ];

            $flash['properties'] = array_merge($defaults, (is_array($flash['properties']) && count($flash['properties'])) ? $flash['properties'] : []);

            $video['flash'] = $flash;
            $element = [
              '#theme' => 'video_filter_flash',
              '#video' => $video,
            ];
            $replacement = \Drupal::service('renderer')->render($element);
          }
          // html.
          elseif (!empty($html)) {
            $video['html'] = $html;
            $element = [
              '#theme' => 'video_filter_html',
              '#video' => $video,
            ];
            $replacement = \Drupal::service('renderer')->render($element);
          }
          else {
            // Plugin doesn't exist.
            $replacement = '<!-- VIDEO FILTER - PLUGIN DOES NOT EXISTS FOR: ' . $video['source'] . ' -->';
          }
        }
        // Invalid format.
        else {
          $replacement = '<!-- VIDEO FILTER - INVALID CODEC IN: ' . $code . ' -->';
        }

        $text = str_replace($code, $replacement, $text);
      }
    }
    return new FilterProcessResult($text);
  }

  /**
   * Video Filter classes.
   */
  private function classes($video) {
    $classes = [
      'video-' . $video['codec']['id'],
    ];
    // Add alignment.
    if (isset($video['align'])) {
      $classes[] = 'video-' . $video['align'];
    }
    // First match is the URL, we don't want that as a class.
    unset($video['codec']['matches'][0]);
    foreach ($video['codec']['matches'] as $match) {
      $classes[] = 'vf-' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $match));
    }
    return $classes;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    // Show long description.
    if ($long) {
      $tips = [];
      $supported = [];
      foreach (array_filter($this->settings['plugins']) as $plugin_id) {
        $plugin = $this->plugin_manager->createInstance($plugin_id);
        // Get plugin/codec usage instructions.
        $instructions = $plugin->instructions();
        $supported[] = '<strong>' . $plugin->getName() . '</strong>';
        if (!empty($instructions)) {
          $tips[] = $instructions;
        }
      }
      return $this->t('
        <p><strong>Video Filter</strong></p>
        <p>You may insert videos from popular video sites by using a simple tag <code>[video:URL]</code>.</p>
        <p>Examples:</p>
        <ul>
          <li>Single video:<br /><code>[video:http://www.youtube.com/watch?v=uN1qUeId]</code></li>
          <li>Random video out of multiple:<br /><code>[video:http://www.youtube.com/watch?v=uN1qUeId1,http://www.youtube.com/watch?v=uN1qUeId2]</code></li>
          <li>Override default autoplay setting: <code>[video:http://www.youtube.com/watch?v=uN1qUeId autoplay:1]</code></li>
          <li>Override default width and height:<br /><code>[video:http://www.youtube.com/watch?v=uN1qUeId width:X height:Y]</code></li>
          <li>Override default aspect ratio:<br /><code>[video:http://www.youtube.com/watch?v=uN1qUeId ratio:4/3]</code></li>
          <li>Align the video:<br /><code>[video:http://www.youtube.com/watch?v=uN1qUeId align:right]</code></li>
        </ul>
        <p>Supported sites: !codecs.</p>
        <p><strong>Special instructions:</strong></p>
        <p><em>Some codecs need special input. You\'ll find those instructions here.</em></p>
        <ul>!instructions</ul>', [
          '!codecs' => implode(', ', $supported),
          '!instructions' => implode('', $tips),
        ]
      );
    }
    else {
      return $this->t('You may insert videos with [video:URL]');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Player Width'),
      '#description' => $this->t('Default width value'),
      '#default_value' => $this->settings['width'],
      '#maxlength' => 4,
    ];
    $form['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Player Height'),
      '#description' => $this->t('Default height value'),
      '#default_value' => $this->settings['height'],
      '#maxlength' => 4,
    ];
    $form['allow_multiple_sources'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow multiple sources'),
      '#description' => $this->t('Allow the use of multiple sources (used source is selected at random).'),
      '#default_value' => $this->settings['allow_multiple_sources'],
    ];
    $plugins = [];
    foreach ($this->plugin_manager->getDefinitions() as $plugin_info) {
      $plugin = $this->plugin_manager->createInstance($plugin_info['id']);
      $plugins[$plugin_info['id']] = $plugin->getName();
    }
    $form['plugins'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enabled plugins'),
      '#options' => $plugins,
      '#default_value' => $this->settings['plugins'],
    ];
    return $form;
  }

}
