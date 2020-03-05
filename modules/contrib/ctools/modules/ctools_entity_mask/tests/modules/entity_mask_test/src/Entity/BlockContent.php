<?php

namespace Drupal\entity_mask_test\Entity;

use Drupal\block_content\Entity\BlockContent as BaseBlockContent;
use Drupal\ctools_entity_mask\MaskEntityTrait;

/**
 * Provides a masked version of BlockContent.
 *
 * @todo Investigate a better way to copy the upstream properties instead of
 *   manually duplicating them.
 *
 * @ContentEntityType(
 *   id = "fake_block_content",
 *   label = @Translation("Custom block"),
 *   bundle_label = @Translation("Custom block type"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "access" = "Drupal\block_content\BlockContentAccessControlHandler",
 *     "list_builder" = "Drupal\block_content\BlockContentListBuilder",
 *     "view_builder" = "Drupal\block_content\BlockContentViewBuilder",
 *     "views_data" = "Drupal\block_content\BlockContentViewsData",
 *     "form" = {
 *       "add" = "Drupal\block_content\BlockContentForm",
 *       "edit" = "Drupal\block_content\BlockContentForm",
 *       "delete" = "Drupal\block_content\Form\BlockContentDeleteForm",
 *       "default" = "Drupal\block_content\BlockContentForm"
 *     },
 *     "translation" = "Drupal\block_content\BlockContentTranslationHandler"
 *   },
 *   admin_permission = "administer blocks",
 *   base_table = "block_content",
 *   revision_table = "block_content_revision",
 *   data_table = "block_content_field_data",
 *   revision_data_table = "block_content_field_revision",
 *   show_revision_ui = TRUE,
 *   links = {
 *     "canonical" = "/block/{block_content}",
 *     "delete-form" = "/block/{block_content}/delete",
 *     "edit-form" = "/block/{block_content}",
 *     "collection" = "/admin/structure/block/block-content",
 *     "create" = "/block",
 *   },
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "bundle" = "type",
 *     "label" = "info",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *     "published" = "status",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_user",
 *     "revision_created" = "revision_created",
 *     "revision_log_message" = "revision_log"
 *   },
 *   bundle_entity_type = "block_content_type",
 *   field_ui_base_route = "entity.block_content_type.edit_form",
 *   render_cache = FALSE,
 *   mask = "block_content",
 * )
 */
class BlockContent extends BaseBlockContent {

  use MaskEntityTrait;

}
