<?php

namespace Drupal\webform\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for webform about.
 */
class WebformAboutController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a WebformAboutController object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, StateInterface $state) {
    $this->configFactory = $config_factory;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('state')
    );
  }

  /**
   * Returns dedicated webform about page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   A renderable array containing a webform about page.
   */
  public function webform(Request $request) {
    $build = [
      '#prefix' => '<div class="webform-about">',
      '#suffix' => '</div>',
    ];

    // Webform.
    $build['webform']['content'] = [
      '#markup' => $this->t('The Webform module is a powerful and flexible Open Source form builder and submission manager for Drupal 8. It provides all the features expected from an enterprise proprietary form builder combined with the flexibility and openness of Drupal.'),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
    $build['webform']['video'] = $this->buildVideo('rJ-Hcg5WtSU');
    $build['webform']['divider'] = $this->buildDivider();

    // Help.
    $build['help']['title'] = [
      '#markup' => $this->t('Need help with the Webform module?'),
      '#prefix' => '<h2>',
      '#suffix' => '</h2>',
    ];
    $build['help']['content'] = [
      '#markup' => $this->t('The best place to start is by reading the <a href="https://www.drupal.org/docs/8/modules/webform">documentation</a>, watching the help <a href="https://www.drupal.org/docs/8/modules/webform/webform-videos">videos</a>, and looking at the examples and templates included in the Webform module. It’s also worth taking a moment to explore the <a href="https://www.drupal.org/docs/8/modules/webform/webform-cookbook">Webform Cookbook</a>, which contains recipes that provide tips and tricks.'),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
    $build['help']['link'] = $this->buildLink(
      $this->t('Get help with the Webform module'),
      'https://www.drupal.org/docs/8/modules/webform/webform-support'
    );
    $build['help']['divider'] = $this->buildDivider();

    // Help us.
    $build['help_us']['title'] = [
      '#markup' => $this->t('Help us Help You'),
      '#prefix' => '<h2>',
      '#suffix' => '</h2>',
    ];
    $build['help_us']['video'] = $this->buildVideo('uQo-1s2h06E');
    $build['help_us']['divider'] = $this->buildDivider();

    // Issue.
    $issue_query = [
      'title' => '{Your title should be descriptive and concise}',
      'version' => $this->state->get('webform.version'),
      'body' => "@see http://cgit.drupalcode.org/webform/tree/ISSUE_TEMPLATE.html

<h2>Problem/Motivation</h2>
(Why the issue was filed, steps to reproduce the problem, etc.)

SUGGESTIONS

* Search existing issues.
* Try Simplytest.me
* Export and attach an example webform.

<h2>Proposed resolution</h2>
(Description of the proposed solution, the rationale behind it, and workarounds for people who cannot use the patch.)",
    ];
    $build['issue']['title'] = [
      '#markup' => $this->t('How can you report bugs and issues?'),
      '#prefix' => '<h2>',
      '#suffix' => '</h2>',
    ];
    $build['issue']['content'][] = [
      '#markup' => $this->t("The first step is to review the Webform module’s issue queue for similar issues. You may be able to find a patch or other solution there. You can also contribute to an existing issue with your additional details."),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
    $build['issue']['content'][] = [
      '#markup' => $this->t("If you need to create a new issue, please make and export an example of the broken form configuration. This will help guarantee that your issue is reproducible. To get the best response, it’s helpful to craft a good issue report. You can find advice and tips on the <a href=\"https://www.drupal.org/node/73179\">How to Create a Good Issue</a> page. Please use the issue summary template when creating new issues."),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
    $build['issue']['link'] = $this->buildLink(
      $this->t('Report a bug/issue with the Webform module'),
      Url::fromUri('https://www.drupal.org/node/add/project-issue/webform', ['query' => $issue_query])
    );
    $build['issue']['divider'] = $this->buildDivider();

    // Feature.
    $feature_query = [
      'title' => '{Your title should be descriptive and concise}',
      'version' => $this->state->get('webform.version'),
      'body' => "
@see http://cgit.drupalcode.org/webform/tree/FEATURE_REQUEST_TEMPLATE.html

<h2>Problem/Motivation</h2>
(Explain why this new feature or functionality is important or useful.)

<h2>Proposed resolution</h2>
(Description of the proposed solution, the rationale behind it, and workarounds for people who cannot use the patch.)",
    ];
    $build['feature']['title'] = [
      '#markup' => $this->t('How can you request a feature?'),
      '#prefix' => '<h2>',
      '#suffix' => '</h2>',
    ];
    $build['feature']['content'] = [
      '#markup' => $this->t('Feature requests can be added to the Webform module\'s issue queue. Use the same tips provided for creating issue reports to help you author a feature request. The better you can define your needs and ideas, the easier it’ll be for people to help you.'),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
    $build['feature']['link'] = $this->buildLink(
      $this->t('Help improve the Webform module'),
      Url::fromUri('https://www.drupal.org/node/add/project-issue/webform', ['query' => $feature_query])
    );

    // Maintainer.
    $maintainer_url = Url::fromUri('http://www.jrockowitz.com/', ['fragment' => 'contact']);;

    $build['maintainer'] = [
      '#type' => 'fieldset',
      '#attributes' => ['class' => 'webform-about-callout webform-about-callout--maintainer'],
    ];
    $build['maintainer']['image'] = [
      '#type' => 'link',
      '#title' => [
        '#theme' => 'image',
        '#uri' => drupal_get_path('module', 'webform') . '/images/about/jrockowitz.png',
        '#alt' => $this->t('Jacob Rockowitz'),
      ],
      '#url' => $maintainer_url,
    ];
    $build['maintainer']['title'] = [
      '#markup' => $this->t('About the maintainer (me)'),
      '#prefix' => '<h2>',
      '#suffix' => '</h2>',
    ];
    $build['maintainer']['content'] = [
      '#markup' => $this->t('Hi, my name is Jacob Rockowitz (<a href="http://www.jrockowitz.com/">jrockowitz</a>). I built and maintain the Webform module for Drupal 8. I want you to get the most out the Webform module and Drupal 8 Webform module.'),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
    $build['maintainer']['link'] = $this->buildLink(
      $this->t('Hire me to help you with the Webform module and Drupal 8'),
      $maintainer_url,
      ['button', 'button--primary']
    );

    $build['#attached']['library'][] = 'webform/webform.about';

    return $build;
  }

  /**
   * Returns dedicated Drupal about page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   A renderable array containing a Drupal about page.
   */
  public function drupal(Request $request) {
    $build = [
      '#prefix' => '<div class="webform-about">',
      '#suffix' => '</div>',
    ];

    // Drupal.
    $build['drupal']['content'] = [
      '#markup' => $this->t("The Drupal project is open source software. Anyone can download, use, work on, and share it with others. It's built on <a href=\"https://www.drupal.org/about/mission-and-principles\">principles</a> like collaboration, globalism, and innovation. It's distributed under the terms of the <a href=\"http://www.gnu.org/copyleft/gpl.html\">GNU General Public License</a> (GPL). There are <a href=\"https://www.drupal.org/about/licensing\">no licensing fees</a>, ever. Drupal will always be free."),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
    $build['drupal']['divider'] = $this->buildDivider();

    // Community.
    $build['community'] = [];
    $build['community']['title'] = [
      '#markup' => $this->t('The Drupal Community'),
      '#prefix' => '<h2>',
      '#suffix' => '</h2>',
    ];
    $build['community']['image'] = [
      '#theme' => 'image',
      '#uri' => 'https://pbs.twimg.com/media/C-RXmp7XsAEgMN2.jpg',
      '#alt' => $this->t('DrupalCon Baltimore'),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
    $build['community']['quote'] = [
      '#prefix' => '<blockquote>',
      '#suffix' => '</blockquote>',
    ];
    $build['community']['quote'][] = [
      '#markup' => $this->t("It’s really the Drupal community and not so much the software that makes the Drupal project what it is. So fostering the Drupal community is actually more important than just managing the code base."),
      '#prefix' => '<address>',
      '#suffix' => '</address>',
    ];
    $build['community']['quote'][] = [
      '#markup' => $this->t('- Dries Buytaert'),
    ];

    $build['community']['content'] = [
      '#markup' => $this->t("The Drupal community is one of the largest open source communities in the world. We're more than 1,000,000 passionate developers, designers, trainers, strategists, coordinators, editors, and sponsors working together. We build Drupal, provide support, create documentation, share networking opportunities, and more. Our shared commitment to the open source spirit pushes the Drupal project forward. New members are always welcome."),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
    $build['community']['divide'] = $this->buildDivider();

    // New.
    $build['new'] = [];
    $build['new']['title'] = [
      '#markup' => $this->t('Are you new to Drupal?'),
      '#prefix' => '<h2>',
      '#suffix' => '</h2>',
    ];
    $build['new']['content'] = [
      '#markup' => $this->t("As an open source project, we don’t have employees to provide Drupal improvements and support. We depend on our diverse community of passionate volunteers to move the project forward. Volunteers work not just on web development and user support but also on many other contributions and interests such as marketing, organizing user groups and camps, speaking at events, maintaining documentation, and helping to review issues."),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
    $build['new']['link'] = $this->buildLink(
      $this->t('Get involved in the Drupal community'),
      'https://www.drupal.org/getting-involved'
    );
    $build['new']['divide'] = $this->buildDivider();

    // User.
    $build['user'] = [];
    $build['user']['title'] = [
      '#markup' => $this->t('Start by creating your Drupal.org user account'),
      '#prefix' => '<h2>',
      '#suffix' => '</h2>',
    ];
    $build['user']['content'] = [
      '#markup' => $this->t("When you create a Drupal.org account, a whole ecosystem of Drupal.org sites and services becomes available to you. Your account works on Drupal.org and any of its subsites including Drupal Groups, Drupal Jobs, Drupal Association and more."),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
    $build['user']['link'] = $this->buildLink(
      $this->t('Become a member of the Drupal community'),
'https://register.drupal.org/user/register?destination=/project/webform'
    );
    $build['user']['divide'] = $this->buildDivider();

    // Association.
    $build['association'] = [];
    $build['association']['title'] = [
      '#markup' => $this->t('Meet the Drupal Association'),
      '#prefix' => '<h2>',
      '#suffix' => '</h2>',
    ];
    $build['association']['video'] = $this->buildVideo('LZWqFSMul84');
    $build['association']['content'] = [
      '#markup' => $this->t("The Drupal Association is dedicated to fostering and supporting the Drupal software project, the community, and its growth. We help the Drupal community with funding, infrastructure, education, promotion, distribution, and online collaboration at Drupal.org."),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
    $build['association']['link'] = $this->buildLink(
      $this->t('Learn more about the Drupal Association'),
'https://www.drupal.org/association/campaign/value-2017'
    );

    // Join.
    $join_url = Url::fromUri('https://www.drupal.org/association/campaign/value-2017', ['fragment' => 'join']);;
    $build['join'] = [
      '#type' => 'fieldset',
      '#attributes' => ['class' => 'webform-about-callout webform-about-callout--join'],
    ];
    $build['join']['image'] = [
      '#type' => 'link',
      '#title' => [
        '#theme' => 'image',
        '#uri' => drupal_get_path('module', 'webform') . '/images/about/drupal-association.png',
        '#alt' => $this->t('Drupal Association'),
      ],
      '#url' => $join_url,
    ];
    $build['join']['title'] = [
      '#markup' => $this->t('The Drupal Association brings value to Drupal and to you.'),
      '#prefix' => '<h2>',
      '#suffix' => '</h2>',
    ];
    $build['join']['content'] = [
      '#markup' => $this->t("The Engineering Team on Drupal.org maintains all the infrastructure behind the many services you use on this site, as well as the services themselves. Join the Drupal Association as a member today to fund the work we do to keep the home of the Drupal project thriving."),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
    $build['join']['link'] = $this->buildLink(
      $this->t('Join the Drupal Association today'),
      $join_url,
      ['button', 'button--primary']
    );

    $build['#attached']['library'][] = 'webform/webform.about';

    return $build;
  }

  /****************************************************************************/
  // Build methods.
  /****************************************************************************/

  /**
   * Build a divider.
   *
   * @return array
   *   A render array containing an HR.
   */
  protected function buildDivider() {
    return ['#markup' => '<p><hr /></p>'];
  }

  /**
   * Build a link.
   *
   * @param $title
   *   Link title.
   * @param string $url
   *   Link URL.
   * @param array $class
   *   Link class names.
   *
   * @return array
   *   A render array containing a link.
   */
  protected function buildLink($title, $url, array $class = ['button']) {
    if (is_string($url)) {
      $url = Url::fromUri($url);
    }
    return [
      '#type' => 'link',
      '#title' => $title . ' ›',
      '#url' => $url,
      '#attributes' => ['class' => $class],
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
  }

  /**
   * Build about video player or linked button.
   *
   * @param string $youtube_id
   *   A YouTube id.
   *
   * @return array
   *   A video player, linked button, or an empty array if videos are disabled.
   */
  protected function buildVideo($youtube_id) {
    $video_display = $this->configFactory->get('webform.settings')->get('ui.video_display');
    switch ($video_display) {
      case 'dialog':
        return [
          '#theme' => 'webform_help_video_youtube',
          '#youtube_id' => $youtube_id,
          '#autoplay' => FALSE,
        ];

      case 'link':
        return [
          '#type' => 'link',
          '#title' => $this->t('Watch video'),
          '#url' => Url::fromUri('https://youtu.be/' . $youtube_id),
          '#attributes' => ['class' => ['button', 'button-action', 'button--small', 'button-webform-play']],
          '#prefix' => ' ',
        ];

      case 'hidden':
      default:
        return [];
    }
  }

}
