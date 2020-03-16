<?php

namespace Drupal\metatag;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\views\ViewEntityInterface;

/**
 * Class MetatagManager.
 *
 * @package Drupal\metatag
 */
class MetatagManager implements MetatagManagerInterface {

  /**
   * The group plugin manager.
   *
   * @var \Drupal\metatag\MetatagGroupPluginManager
   */
  protected $groupPluginManager;

  /**
   * The tag plugin manager.
   *
   * @var \Drupal\metatag\MetatagTagPluginManager
   */
  protected $tagPluginManager;

  /**
   * The Metatag defaults.
   *
   * @var array
   */
  protected $metatagDefaults;

  /**
   * The Metatag token.
   *
   * @var \Drupal\metatag\MetatagToken
   */
  protected $tokenService;

  /**
   * The Metatag logging channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructor for MetatagManager.
   *
   * @param \Drupal\metatag\MetatagGroupPluginManager $groupPluginManager
   *   The MetatagGroupPluginManager object.
   * @param \Drupal\metatag\MetatagTagPluginManager $tagPluginManager
   *   The MetatagTagPluginMπanager object.
   * @param \Drupal\metatag\MetatagToken $token
   *   The MetatagToken object.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $channelFactory
   *   The LoggerChannelFactoryInterface object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The EntityTypeManagerInterface object.
   */
  public function __construct(MetatagGroupPluginManager $groupPluginManager,
      MetatagTagPluginManager $tagPluginManager,
      MetatagToken $token,
      LoggerChannelFactoryInterface $channelFactory,
      EntityTypeManagerInterface $entityTypeManager) {
    $this->groupPluginManager = $groupPluginManager;
    $this->tagPluginManager = $tagPluginManager;
    $this->tokenService = $token;
    $this->logger = $channelFactory->get('metatag');
    $this->metatagDefaults = $entityTypeManager->getStorage('metatag_defaults');
  }

  /**
   * Returns the list of protected defaults.
   *
   * @return array
   *   Th protected defaults.
   */
  public static function protectedDefaults() {
    return [
      'global',
      '403',
      '404',
      'node',
      'front',
      'taxonomy_term',
      'user',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function tagsFromEntity(ContentEntityInterface $entity) {
    $tags = [];

    $fields = $this->getFields($entity);

    /* @var \Drupal\field\Entity\FieldConfig $field_info */
    foreach ($fields as $field_name => $field_info) {
      // Get the tags from this field.
      $tags = $this->getFieldTags($entity, $field_name);
    }

    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public function tagsFromEntityWithDefaults(ContentEntityInterface $entity) {
    return $this->tagsFromEntity($entity) + $this->defaultTagsFromEntity($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultTagsFromEntity(ContentEntityInterface $entity) {
    /** @var \Drupal\metatag\Entity\MetatagDefaults $metatags */
    $metatags = $this->metatagDefaults->load('global');
    if (!$metatags || !$metatags->status()) {
      return NULL;
    }
    // Add/overwrite with tags set on the entity type.
    /** @var \Drupal\metatag\Entity\MetatagDefaults $entity_type_tags */
    $entity_type_tags = $this->metatagDefaults->load($entity->getEntityTypeId());
    if (!is_null($entity_type_tags) && $entity_type_tags->status()) {
      $metatags->overwriteTags($entity_type_tags->get('tags'));
    }
    // Add/overwrite with tags set on the entity bundle.
    /** @var \Drupal\metatag\Entity\MetatagDefaults $bundle_metatags */
    $bundle_metatags = $this->metatagDefaults->load($entity->getEntityTypeId() . '__' . $entity->bundle());
    if (!is_null($bundle_metatags) && $bundle_metatags->status()) {
      $metatags->overwriteTags($bundle_metatags->get('tags'));
    }
    return $metatags->get('tags');
  }

  /**
   * Gets the group plugin definitions.
   *
   * @return array
   *   Group definitions.
   */
  protected function groupDefinitions() {
    return $this->groupPluginManager->getDefinitions();
  }

  /**
   * Gets the tag plugin definitions.
   *
   * @return array
   *   Tag definitions
   */
  protected function tagDefinitions() {
    return $this->tagPluginManager->getDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function sortedGroups() {
    $metatag_groups = $this->groupDefinitions();

    // Pull the data from the definitions into a new array.
    $groups = [];
    foreach ($metatag_groups as $group_name => $group_info) {
      $groups[$group_name]['id'] = $group_info['id'];
      $groups[$group_name]['label'] = $group_info['label']->render();
      $groups[$group_name]['description'] = $group_info['description'];
      $groups[$group_name]['weight'] = $group_info['weight'];
    }

    // Create the 'sort by' array.
    $sort_by = [];
    foreach ($groups as $group) {
      $sort_by[] = $group['weight'];
    }

    // Sort the groups by weight.
    array_multisort($sort_by, SORT_ASC, $groups);

    return $groups;
  }

  /**
   * {@inheritdoc}
   */
  public function sortedTags() {
    $metatag_tags = $this->tagDefinitions();

    // Pull the data from the definitions into a new array.
    $tags = [];
    foreach ($metatag_tags as $tag_name => $tag_info) {
      $tags[$tag_name]['id'] = $tag_info['id'];
      $tags[$tag_name]['label'] = $tag_info['label']->render();
      $tags[$tag_name]['group'] = $tag_info['group'];
      $tags[$tag_name]['weight'] = $tag_info['weight'];
    }

    // Create the 'sort by' array.
    $sort_by = [];
    foreach ($tags as $key => $tag) {
      $sort_by['group'][$key] = $tag['group'];
      $sort_by['weight'][$key] = $tag['weight'];
    }

    // Sort the tags by weight.
    array_multisort($sort_by['group'], SORT_ASC, $sort_by['weight'], SORT_ASC, $tags);

    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public function sortedGroupsWithTags() {
    $groups = $this->sortedGroups();
    $tags = $this->sortedTags();

    foreach ($tags as $tag_name => $tag) {
      $tag_group = $tag['group'];

      if (!isset($groups[$tag_group])) {
        // If the tag is claiming a group that has no matching plugin, log an
        // error and force it to the basic group.
        $this->logger->error("Undefined group '%group' on tag '%tag'", ['%group' => $tag_group, '%tag' => $tag_name]);
        $tag['group'] = 'basic';
        $tag_group = 'basic';
      }

      $groups[$tag_group]['tags'][$tag_name] = $tag;
    }

    return $groups;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $values, array $element, array $token_types = [], array $included_groups = NULL, array $included_tags = NULL) {
    // Add the outer fieldset.
    $element += [
      '#type' => 'details',
    ];

    $element += $this->tokenService->tokenBrowser($token_types);

    $groups_and_tags = $this->sortedGroupsWithTags();

    foreach ($groups_and_tags as $group_name => $group) {
      // Only act on groups that have tags and are in the list of included
      // groups (unless that list is null).
      if (isset($group['tags']) && (is_null($included_groups) || in_array($group_name, $included_groups) || in_array($group['id'], $included_groups))) {
        // Create the fieldset.
        $element[$group_name]['#type'] = 'details';
        $element[$group_name]['#title'] = $group['label'];
        $element[$group_name]['#description'] = $group['description'];
        $element[$group_name]['#open'] = FALSE;

        foreach ($group['tags'] as $tag_name => $tag) {
          // Only act on tags in the included tags list, unless that is null.
          if (is_null($included_tags) || in_array($tag_name, $included_tags) || in_array($tag['id'], $included_tags)) {
            // Make an instance of the tag.
            $tag = $this->tagPluginManager->createInstance($tag_name);

            // Set the value to the stored value, if any.
            $tag_value = isset($values[$tag_name]) ? $values[$tag_name] : NULL;
            $tag->setValue($tag_value);

            // Open any groups that have non-empty values.
            if (!empty($tag_value)) {
              $element[$group_name]['#open'] = TRUE;
            }

            // Create the bit of form for this tag.
            $element[$group_name][$tag_name] = $tag->form($element);
          }
        }
      }
    }

    return $element;
  }

  /**
   * Returns a list of the Metatag fields on an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to examine.
   *
   * @return array
   *   The fields from the entity which are Metatag fields.
   */
  protected function getFields(ContentEntityInterface $entity) {
    $field_list = [];

    if ($entity instanceof ContentEntityInterface) {
      // Get a list of the metatag field types.
      $field_types = $this->fieldTypes();

      // Get a list of the field definitions on this entity.
      $definitions = $entity->getFieldDefinitions();

      // Iterate through all the fields looking for ones in our list.
      foreach ($definitions as $field_name => $definition) {
        // Get the field type, ie: metatag.
        $field_type = $definition->getType();

        // Check the field type against our list of fields.
        if (isset($field_type) && in_array($field_type, $field_types)) {
          $field_list[$field_name] = $definition;
        }
      }
    }

    return $field_list;
  }

  /**
   * Returns a list of the meta tags with values from a field.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The ContentEntityInterface object.
   * @param string $field_name
   *   The name of the field to work on.
   *
   * @return array
   *   Array of field tags.
   */
  protected function getFieldTags(ContentEntityInterface $entity, $field_name) {
    $tags = [];
    foreach ($entity->{$field_name} as $item) {
      // Get serialized value and break it into an array of tags with values.
      $serialized_value = $item->get('value')->getValue();
      if (!empty($serialized_value)) {
        $tags += unserialize($serialized_value);
      }
    }

    return $tags;
  }

  /**
   * Returns default meta tags for an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to work on.
   *
   * @return array
   *   The default meta tags appropriate for this entity.
   */
  public function getDefaultMetatags(ContentEntityInterface $entity = NULL) {
    // Get general global metatags.
    $metatags = $this->getGlobalMetatags();
    // If that is empty something went wrong.
    if (!$metatags) {
      return;
    }

    // Check if this is a special page.
    $special_metatags = $this->getSpecialMetatags();

    // Merge with all globals defaults.
    if ($special_metatags) {
      $metatags->set('tags', array_merge($metatags->get('tags'), $special_metatags->get('tags')));
    }

    // Next check if there is this page is an entity that has meta tags.
    // @todo Think about using other defaults, e.g. views. Maybe use plugins?
    else {
      if (is_null($entity)) {
        $entity = metatag_get_route_entity();
      }

      if (!empty($entity)) {
        // Get default meta tags for a given entity.
        $entity_defaults = $this->getEntityDefaultMetatags($entity);
        if ($entity_defaults != NULL) {
          $metatags->set('tags', array_merge($metatags->get('tags'), $entity_defaults));
        }
      }
    }

    return $metatags->get('tags');
  }

  /**
   * Returns global meta tags.
   *
   * @return \Drupal\metatag\Entity\MetatagDefaults|null
   *   The global meta tags or NULL.
   */
  public function getGlobalMetatags() {
    $metatags = $this->metatagDefaults->load('global');
    return (!empty($metatags) && $metatags->status()) ? $metatags : NULL;
  }

  /**
   * Returns special meta tags.
   *
   * @return \Drupal\metatag\Entity\MetatagDefaults|null
   *   The defaults for this page, if it's a special page.
   */
  public function getSpecialMetatags() {
    $metatags = NULL;

    if (\Drupal::service('path.matcher')->isFrontPage()) {
      $metatags = $this->metatagDefaults->load('front');
    }
    elseif (\Drupal::service('current_route_match')->getRouteName() == 'system.403') {
      $metatags = $this->metatagDefaults->load('403');
    }
    elseif (\Drupal::service('current_route_match')->getRouteName() == 'system.404') {
      $metatags = $this->metatagDefaults->load('404');
    }

    if ($metatags && !$metatags->status()) {
      // Do not return disabled special metatags.
      return NULL;
    }

    return $metatags;
  }

  /**
   * Returns default meta tags for an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to work with.
   *
   * @return array
   *   The appropriate default meta tags.
   */
  public function getEntityDefaultMetatags(ContentEntityInterface $entity) {
    /** @var \Drupal\metatag\Entity\MetatagDefaults $entity_metatags */
    $entity_metatags = $this->metatagDefaults->load($entity->getEntityTypeId());
    $metatags = [];
    if ($entity_metatags != NULL && $entity_metatags->status()) {
      // Merge with global defaults.
      $metatags = array_merge($metatags, $entity_metatags->get('tags'));
    }

    // Finally, check if we should apply bundle overrides.
    /** @var \Drupal\metatag\Entity\MetatagDefaults $bundle_metatags */
    $bundle_metatags = $this->metatagDefaults->load($entity->getEntityTypeId() . '__' . $entity->bundle());
    if ($bundle_metatags != NULL && $bundle_metatags->status()) {
      // Merge with existing defaults.
      $metatags = array_merge($metatags, $bundle_metatags->get('tags'));
    }

    return $metatags;
  }

  /**
   * Generate the elements that go in the hook_page_attachments attached array.
   *
   * @param array $tags
   *   The array of tags as plugin_id => value.
   * @param object $entity
   *   Optional entity object to use for token replacements.
   *
   * @return array
   *   Render array with tag elements.
   */
  public function generateElements(array $tags, $entity = NULL) {
    $elements = [];
    $tags = $this->generateRawElements($tags, $entity);

    foreach ($tags as $name => $tag) {
      if (!empty($tag)) {
        $elements['#attached']['html_head'][] = [
          $tag,
          $name,
        ];
      }
    }

    return $elements;
  }

  /**
   * Generate the actual meta tag values.
   *
   * @param array $tags
   *   The array of tags as plugin_id => value.
   * @param object $entity
   *   Optional entity object to use for token replacements.
   *
   * @return array
   *   Render array with tag elements.
   */
  public function generateRawElements(array $tags, $entity = NULL) {
    // Ignore the update.php path.
    $request = \Drupal::request();
    if ($request->getBaseUrl() == '/update.php') {
      return [];
    }

    $rawTags = [];

    $metatag_tags = $this->tagPluginManager->getDefinitions();

    // Order the elements by weight first, as some systems like Facebook care.
    uksort($tags, function ($tag_name_a, $tag_name_b) use ($metatag_tags) {
      $weight_a = isset($metatag_tags[$tag_name_a]['weight']) ? $metatag_tags[$tag_name_a]['weight'] : 0;
      $weight_b = isset($metatag_tags[$tag_name_b]['weight']) ? $metatag_tags[$tag_name_b]['weight'] : 0;

      return ($weight_a < $weight_b) ? -1 : 1;
    });

    // Each element of the $values array is a tag with the tag plugin name as
    // the key.
    foreach ($tags as $tag_name => $value) {
      // Check to ensure there is a matching plugin.
      if (isset($metatag_tags[$tag_name])) {
        // Get an instance of the plugin.
        $tag = $this->tagPluginManager->createInstance($tag_name);

        // Render any tokens in the value.
        $token_replacements = [];
        if ($entity) {
          // @todo This needs a better way of discovering the context.
          if ($entity instanceof ViewEntityInterface) {
            // Views tokens require the ViewExecutable, not the config entity.
            // @todo Can we move this into metatag_views somehow?
            $token_replacements = ['view' => $entity->getExecutable()];
          }
          elseif ($entity instanceof ContentEntityInterface) {
            $token_replacements = [$entity->getEntityTypeId() => $entity];
          }
        }

        // Set the value as sometimes the data needs massaging, such as when
        // field defaults are used for the Robots field, which come as an array
        // that needs to be filtered and converted to a string.
        // @see Robots::setValue()
        $tag->setValue($value);
        $langcode = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();

        $processed_value = PlainTextOutput::renderFromHtml(htmlspecialchars_decode($this->tokenService->replace($tag->value(), $token_replacements, ['langcode' => $langcode])));

        // Now store the value with processed tokens back into the plugin.
        $tag->setValue($processed_value);

        // Have the tag generate the output based on the value we gave it.
        $output = $tag->output();

        if (!empty($output)) {
          $output = $tag->multiple() ? $output : [$output];

          // Backwards compatibility for modules which don't support this logic.
          if (isset($output['#tag'])) {
            $output = [$output];
          }

          foreach ($output as $index => $element) {
            // Add index to tag name as suffix to avoid having same key.
            $index_tag_name = $tag->multiple() ? $tag_name . '_' . $index : $tag_name;
            $rawTags[$index_tag_name] = $element;
          }
        }
      }
    }

    return $rawTags;
  }

  /**
   * Returns a list of fields handled by Metatag.
   *
   * @return array
   *   A list of supported field types.
   */
  protected function fieldTypes() {
    // @todo Either get this dynamically from field plugins or forget it and
    // just hardcode metatag where this is called.
    return ['metatag'];
  }

}
