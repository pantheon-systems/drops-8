<?php

namespace Drupal\token\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\token\TreeBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns tree responses for tokens.
 */
class TokenTreeController extends ControllerBase {

  /**
   * @var \Drupal\token\TreeBuilderInterface
   */
  protected $treeBuilder;

  public function __construct(TreeBuilderInterface $tree_builder) {
    $this->treeBuilder = $tree_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('token.tree_builder')
    );
  }

  /**
   * Page callback to output a token tree as an empty page.
   */
  function outputTree(Request $request) {
    $options = $request->query->has('options') ? Json::decode($request->query->get('options')) : [];

    // The option token_types may only be an array OR 'all'. If it is not set,
    // we assume that only global token types are requested.
    $token_types = !empty($options['token_types']) ? $options['token_types'] : [];
    if ($token_types == 'all') {
      $build = $this->treeBuilder->buildAllRenderable($options);
    }
    else {
      $build = $this->treeBuilder->buildRenderable($token_types, $options);
    }

    $build['#cache']['contexts'][] = 'url.query_args:options';
    $build['#title'] = $this->t('Available tokens');

    // If this is an AJAX/modal request, add a wrapping div to the contents so
    // that Drupal.behaviors.tokenTree and Drupal.behaviors.tokenAttach can
    // stil find the elements they need to.
    // @see https://www.drupal.org/project/token/issues/2994671
    // @see https://www.drupal.org/node/2940704
    // @see http://danielnouri.org/notes/2011/03/14/a-jquery-find-that-also-finds-the-root-element/
    if ($request->isXmlHttpRequest()) {
      $build['#prefix'] = '<div>';
      $build['#suffix'] = '</div>';
    }

    return $build;
  }

}
