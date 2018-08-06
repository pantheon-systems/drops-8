core = 8.x
api = 2

;;;;;;;;;;;;;;;;;;;;;
;; Contrib modules
;;;;;;;;;;;;;;;;;;;;;

projects[config_update][type] = module
projects[config_update][subdir] = "contrib"
projects[config_update][version] = 1.4

projects[ctools][type] = module
projects[ctools][subdir] = "contrib"
projects[ctools][version] = 3.x-dev

projects[token][type] = module
projects[token][subdir] = "contrib"
projects[token][version] = 1.0

projects[field_group][type] = module
projects[field_group][subdir] = "contrib"
projects[field_group][version] = 1.0-rc6

projects[smart_trim][type] = module
projects[smart_trim][subdir] = "contrib"
projects[smart_trim][version] = 1.0

projects[markdown][type] = module
projects[markdown][subdir] = "contrib"
projects[markdown][version] = 1.2

projects[menu_block][type] = module
projects[menu_block][subdir] = "contrib"
projects[menu_block][version] = 1.4

projects[linkit][type] = module
projects[linkit][subdir] = "contrib"
projects[linkit][version] = 4.3

projects[entity][type] = module
projects[entity][subdir] = "contrib"
projects[entity][version] = 1.0-beta1

projects[entity_browser][type] = module
projects[entity_browser][subdir] = "contrib"
projects[entity_browser][version] = 1.3
;; Issue #2820132 by matthieuscarset, marcoscano: getDisplay() on null Entity Browser reference.
;; projects[entity_browser][patch][] = https://www.drupal.org/files/issues/ElementPatch_0.patch
;; Issue #2845037 by slashrsm, RajabNatshah: Fixed the issue of Call to a member function getConfigDependencyKey() on null on [Widget view], and [SelectionDisplay view]
;; projects[entity_browser][patch][] = https://www.drupal.org/files/issues/2845037_15.patch

projects[entity_embed][type] = module
projects[entity_embed][subdir] = "contrib"
projects[entity_embed][version] = 1.0-beta2

projects[inline_entity_form][type] = module
projects[inline_entity_form][subdir] = "contrib"
projects[inline_entity_form][version] = 1.0-beta1

projects[media_entity][type] = module
projects[media_entity][subdir] = "contrib"
projects[media_entity][version] = 1.6

projects[media_entity_document][type] = module
projects[media_entity_document][subdir] = "contrib"
projects[media_entity_document][version] = 1.1

projects[media_entity_image][type] = module
projects[media_entity_image][subdir] = "contrib"
projects[media_entity_image][version] = 1.2

projects[video_embed_field][type] = module
projects[video_embed_field][subdir] = "contrib"
projects[video_embed_field][version] = 1.5

projects[crop][type] = module
projects[crop][subdir] = "contrib"
projects[crop][version] = 1.3

projects[focal_point][type] = module
projects[focal_point][subdir] = "contrib"
projects[focal_point][version] = 1.0-beta5

projects[pathologic][type] = module
projects[pathologic][subdir] = "contrib"
projects[pathologic][download][url] = https://git.drupal.org/project/pathologic.git
projects[pathologic][download][revision] = e0473546e51cbeaa3acb34e3208a0c503ca85613
projects[pathologic][download][branch] = 1.x

projects[role_delegation][type] = module
projects[role_delegation][subdir] = "contrib"
projects[role_delegation][version] = 1.0-alpha1

projects[responsive_preview][type] = module
projects[responsive_preview][subdir] = "contrib"
projects[responsive_preview][version] = 1.0-alpha7

projects[webform][type] = module
projects[webform][subdir] = "contrib"
projects[webform][version] = 5.0-beta23

projects[webform_views][type] = module
projects[webform_views][subdir] = "contrib"
projects[webform_views][version] = 5.x

projects[content_lock][type] = module
projects[content_lock][subdir] = "contrib"
projects[content_lock][version] = 1.0-alpha4

projects[pathauto][type] = module
projects[pathauto][subdir] = "contrib"
projects[pathauto][version] = 1.0

projects[redirect][type] = module
projects[redirect][subdir] = "contrib"
projects[redirect][version] = 1.0-beta1

projects[metatag][type] = module
projects[metatag][subdir] = "contrib"
projects[metatag][version] = 1.3

projects[google_analytics][type] = module
projects[google_analytics][subdir] = "contrib"
projects[google_analytics][version] = 2.2

projects[google_cse][type] = module
projects[google_cse][subdir] = "contrib"
projects[google_cse][version] = 3.x

projects[captcha][type] = module
projects[captcha][subdir] = "contrib"
projects[captcha][version] = 1.0-beta1

projects[recaptcha][type] = module
projects[recaptcha][subdir] = "contrib"
projects[recaptcha][version] = 2.2

projects[features][type] = module
projects[features][subdir] = "contrib"
projects[features][version] = 3.5

projects[libraries][type] = module
projects[libraries][subdir] = "contrib"
projects[libraries][download][url] = https://git.drupal.org/project/libraries.git
projects[libraries][download][revision] = 08a46ab12b573f1f48e9d160ed21a36417b5f749
projects[libraries][download][branch] = 3.x

projects[xmlsitemap][type] = module
projects[xmlsitemap][subdir] = "contrib"
projects[xmlsitemap][version] = 1.0-alpha2

projects[simplesamlphp_auth][type] = module
projects[simplesamlphp_auth][subdir] = "contrib"
projects[simplesamlphp_auth][version] = 3.0-rc2

projects[user_external_invite][type] = module
projects[user_external_invite][subdir] = "contrib"
projects[user_external_invite][version] = 1.x

projects[honeypot][type] = module
projects[honeypot][subdir] = "contrib"
projects[honeypot][version] = 1.27

projects[views_slideshow][type] = module
projects[views_slideshow][subdir] = "contrib"
projects[views_slideshow][version] = 4.5

projects[colorbox][type] = module
projects[colorbox][subdir] = "contrib"
projects[colorbox][version] = 1.4

projects[fitvids][type] = module
projects[fitvids][subdir] = "contrib"
projects[fitvids][version] = 1.0

projects[auto_entitylabel][type] = module
projects[auto_entitylabel][subdir] = "contrib"
projects[auto_entitylabel][version] = 2.0-beta1

;;;;;;;;;;;;;;;;;;;;;
;; Still Unstable
;;;;;;;;;;;;;;;;;;;;;

projects[vppr][type] = module
projects[vppr][subdir] = "contrib"
projects[vppr][version] = 1.x

projects[view_unpublished][type] = module
projects[view_unpublished][subdir] = "contrib"
projects[view_unpublished][version] = 1.x

projects[video_filter][type] = module
projects[video_filter][subdir] = "contrib"
projects[video_filter][version] = 1.x

;;;;;;;;;;;;;;;;;;;;;
;; Themes
;;;;;;;;;;;;;;;;;;;;;

projects[bootstrap][type] = theme
projects[bootstrap][subdir] = "contrib"
projects[bootstrap][version] = 3.6

;;;;;;;;;;;;;;;;;;;;;
;; Development
;;;;;;;;;;;;;;;;;;;;;


projects[libraries_ui][type] = module
projects[libraries_ui][subdir] = "contrib"
projects[libraries_ui][version] = 1.0

projects[devel][type] = module
projects[devel][subdir] = "contrib"
projects[devel][version] = 1.2

projects[diff][type] = module
projects[diff][subdir] = "contrib"
projects[diff][version] = 1.0-rc1