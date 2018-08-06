<?php

namespace Drupal\entity_embed\Plugin\CKEditorPlugin;

use Drupal\editor\Entity\Editor;
use Drupal\embed\EmbedButtonInterface;
use Drupal\embed\EmbedCKEditorPluginBase;

/**
 * Defines the "drupalentity" plugin.
 *
 * @CKEditorPlugin(
 *   id = "drupalentity",
 *   label = @Translation("Entity"),
 *   embed_type_id = "entity"
 * )
 */
class DrupalEntity extends EmbedCKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function getButton(EmbedButtonInterface $embed_button) {
    $button = parent::getButton($embed_button);
    $button['entity_type'] = $embed_button->getTypeSetting('entity_type');
    return $button;
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'entity_embed') . '/js/plugins/drupalentity/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return array(
      'DrupalEntity_dialogTitleAdd' => t('Insert entity'),
      'DrupalEntity_dialogTitleEdit' => t('Edit entity'),
      'DrupalEntity_buttons' => $this->getButtons(),
    );
  }

}
