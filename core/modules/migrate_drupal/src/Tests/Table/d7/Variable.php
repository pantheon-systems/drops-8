<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\Variable.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the variable table.
 */
class Variable extends DrupalDumpBase {

  public function load() {
    $this->createTable("variable", array(
      'primary key' => array(
        'name',
      ),
      'fields' => array(
        'name' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '128',
          'default' => '',
        ),
        'value' => array(
          'type' => 'blob',
          'not null' => TRUE,
          'length' => 100,
        ),
      ),
      'mysql_character_set' => 'utf8',
    ));
    $this->database->insert("variable")->fields(array(
      'name',
      'value',
    ))
    ->values(array(
      'name' => 'additional_settings__active_tab_article',
      'value' => 's:15:"edit-submission";',
    ))->values(array(
      'name' => 'additional_settings__active_tab_blog',
      'value' => 's:15:"edit-submission";',
    ))->values(array(
      'name' => 'additional_settings__active_tab_book',
      'value' => 's:13:"edit-workflow";',
    ))->values(array(
      'name' => 'additional_settings__active_tab_forum',
      'value' => 's:15:"edit-submission";',
    ))->values(array(
      'name' => 'additional_settings__active_tab_page',
      'value' => 's:15:"edit-submission";',
    ))->values(array(
      'name' => 'additional_settings__active_tab_test_content_type',
      'value' => 's:13:"edit-workflow";',
    ))->values(array(
      'name' => 'admin_theme',
      'value' => 's:5:"seven";',
    ))->values(array(
      'name' => 'aggregator_allowed_html_tags',
      'value' => 's:13:"<p> <div> <a>";',
    ))->values(array(
      'name' => 'aggregator_clear',
      'value' => 'i:86400;',
    ))->values(array(
      'name' => 'aggregator_fetcher',
      'value' => 's:10:"aggregator";',
    ))->values(array(
      'name' => 'aggregator_parser',
      'value' => 's:10:"aggregator";',
    ))->values(array(
      'name' => 'aggregator_processors',
      'value' => 'a:1:{i:0;s:10:"aggregator";}',
    ))->values(array(
      'name' => 'aggregator_summary_items',
      'value' => 'i:6;',
    ))->values(array(
      'name' => 'aggregator_teaser_length',
      'value' => 'i:500;',
    ))->values(array(
      'name' => 'allow_insecure_derivatives',
      'value' => 'b:1;',
    ))->values(array(
      'name' => 'anonymous',
      'value' => 's:9:"Anonymous";',
    ))->values(array(
      'name' => 'block_cache',
      'value' => 'i:0;',
    ))->values(array(
      'name' => 'book_allowed_types',
      'value' => 'a:1:{i:0;s:4:"book";}',
    ))->values(array(
      'name' => 'book_child_type',
      'value' => 's:4:"book";',
    ))->values(array(
      'name' => 'cache_lifetime',
      'value' => 's:3:"300";',
    ))->values(array(
      'name' => 'clean_url',
      'value' => 's:1:"1";',
    ))->values(array(
      'name' => 'comment_anonymous_article',
      'value' => 'i:0;',
    ))->values(array(
      'name' => 'comment_anonymous_blog',
      'value' => 'i:0;',
    ))->values(array(
      'name' => 'comment_anonymous_book',
      'value' => 'i:0;',
    ))->values(array(
      'name' => 'comment_anonymous_forum',
      'value' => 'i:0;',
    ))->values(array(
      'name' => 'comment_anonymous_page',
      'value' => 'i:0;',
    ))->values(array(
      'name' => 'comment_anonymous_test_content_type',
      'value' => 'i:0;',
    ))->values(array(
      'name' => 'comment_article',
      'value' => 's:1:"2";',
    ))->values(array(
      'name' => 'comment_blog',
      'value' => 's:1:"2";',
    ))->values(array(
      'name' => 'comment_book',
      'value' => 's:1:"2";',
    ))->values(array(
      'name' => 'comment_default_mode_article',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'comment_default_mode_blog',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'comment_default_mode_book',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'comment_default_mode_forum',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'comment_default_mode_page',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'comment_default_mode_test_content_type',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'comment_default_per_page_article',
      'value' => 's:2:"50";',
    ))->values(array(
      'name' => 'comment_default_per_page_blog',
      'value' => 's:2:"50";',
    ))->values(array(
      'name' => 'comment_default_per_page_book',
      'value' => 's:2:"50";',
    ))->values(array(
      'name' => 'comment_default_per_page_forum',
      'value' => 's:2:"50";',
    ))->values(array(
      'name' => 'comment_default_per_page_page',
      'value' => 's:2:"50";',
    ))->values(array(
      'name' => 'comment_default_per_page_test_content_type',
      'value' => 's:2:"30";',
    ))->values(array(
      'name' => 'comment_form_location_article',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'comment_form_location_blog',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'comment_form_location_book',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'comment_form_location_forum',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'comment_form_location_page',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'comment_form_location_test_content_type',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'comment_forum',
      'value' => 's:1:"2";',
    ))->values(array(
      'name' => 'comment_page',
      'value' => 's:1:"0";',
    ))->values(array(
      'name' => 'comment_preview_article',
      'value' => 's:1:"1";',
    ))->values(array(
      'name' => 'comment_preview_blog',
      'value' => 's:1:"1";',
    ))->values(array(
      'name' => 'comment_preview_book',
      'value' => 's:1:"1";',
    ))->values(array(
      'name' => 'comment_preview_forum',
      'value' => 's:1:"1";',
    ))->values(array(
      'name' => 'comment_preview_page',
      'value' => 's:1:"1";',
    ))->values(array(
      'name' => 'comment_preview_test_content_type',
      'value' => 's:1:"1";',
    ))->values(array(
      'name' => 'comment_subject_field_article',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'comment_subject_field_blog',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'comment_subject_field_book',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'comment_subject_field_forum',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'comment_subject_field_page',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'comment_subject_field_test_content_type',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'comment_test_content_type',
      'value' => 's:1:"2";',
    ))->values(array(
      'name' => 'contact_default_status',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'cron_key',
      'value' => 's:43:"_vWFj-dRR2rNoHDwl7N__J9uZNutDcLz3w4tlPJzRAM";',
    ))->values(array(
      'name' => 'cron_last',
      'value' => 'i:1441286523;',
    ))->values(array(
      'name' => 'css_js_query_string',
      'value' => 's:6:"nu3rtz";',
    ))->values(array(
      'name' => 'ctools_last_cron',
      'value' => 'i:1421720834;',
    ))->values(array(
      'name' => 'dashboard_stashed_blocks',
      'value' => 'a:5:{i:0;a:3:{s:6:"module";s:4:"node";s:5:"delta";s:6:"recent";s:6:"region";s:14:"dashboard_main";}i:1;a:3:{s:6:"module";s:4:"user";s:5:"delta";s:3:"new";s:6:"region";s:17:"dashboard_sidebar";}i:2;a:3:{s:6:"module";s:6:"search";s:5:"delta";s:4:"form";s:6:"region";s:17:"dashboard_sidebar";}i:3;a:3:{s:6:"module";s:7:"comment";s:5:"delta";s:6:"recent";s:6:"region";s:18:"dashboard_inactive";}i:4;a:3:{s:6:"module";s:4:"user";s:5:"delta";s:6:"online";s:6:"region";s:18:"dashboard_inactive";}}',
    ))->values(array(
      'name' => 'date_api_version',
      'value' => 's:3:"7.2";',
    ))->values(array(
      'name' => 'date_default_timezone',
      'value' => 's:15:"America/Chicago";',
    ))->values(array(
      'name' => 'default_nodes_main',
      'value' => 's:2:"10";',
    ))->values(array(
      'name' => 'drupal_http_request_fails',
      'value' => 'b:0;',
    ))->values(array(
      'name' => 'drupal_private_key',
      'value' => 's:43:"9eRJWxrMwQ5CufYJjXBZbPGz_t8vPIYRQr18PamdKmM";',
    ))->values(array(
      'name' => 'email__active_tab',
      'value' => 's:27:"edit-email-pending-approval";',
    ))->values(array(
      'name' => 'field_bundle_settings_comment__comment_node_test_content_type',
      'value' => 'a:2:{s:10:"view_modes";a:0:{}s:12:"extra_fields";a:2:{s:4:"form";a:2:{s:6:"author";a:1:{s:6:"weight";s:2:"-2";}s:7:"subject";a:1:{s:6:"weight";s:2:"-1";}}s:7:"display";a:0:{}}}',
    ))->values(array(
      'name' => 'field_bundle_settings_node__test_content_type',
      'value' => 'a:2:{s:10:"view_modes";a:6:{s:6:"teaser";a:1:{s:15:"custom_settings";b:1;}s:4:"full";a:1:{s:15:"custom_settings";b:0;}s:3:"rss";a:1:{s:15:"custom_settings";b:0;}s:12:"search_index";a:1:{s:15:"custom_settings";b:0;}s:13:"search_result";a:1:{s:15:"custom_settings";b:0;}s:5:"print";a:1:{s:15:"custom_settings";b:0;}}s:12:"extra_fields";a:2:{s:4:"form";a:1:{s:5:"title";a:1:{s:6:"weight";s:1:"0";}}s:7:"display";a:0:{}}}',
    ))->values(array(
      'name' => 'field_bundle_settings_user__user',
      'value' => 'a:2:{s:10:"view_modes";a:0:{}s:12:"extra_fields";a:2:{s:4:"form";a:2:{s:7:"account";a:1:{s:6:"weight";s:3:"-10";}s:8:"timezone";a:1:{s:6:"weight";s:1:"6";}}s:7:"display";a:0:{}}}',
    ))->values(array(
      'name' => 'file_default_scheme',
      'value' => 's:6:"public";',
    ))->values(array(
      'name' => 'file_private_path',
      'value' => 's:0:"";',
    ))->values(array(
      'name' => 'file_public_path',
      'value' => 's:19:"sites/default/files";',
    ))->values(array(
      'name' => 'file_temporary_path',
      'value' => 's:4:"/tmp";',
    ))->values(array(
      'name' => 'filter_fallback_format',
      'value' => 's:10:"plain_text";',
    ))->values(array(
      'name' => 'forum_block_num_active',
      'value' => 'i:9;',
    ))->values(array(
      'name' => 'forum_block_num_new',
      'value' => 'i:4;',
    ))->values(array(
      'name' => 'forum_containers',
      'value' => 'a:1:{i:0;s:1:"6";}',
    ))->values(array(
      'name' => 'forum_hot_topic',
      'value' => 'i:10;',
    ))->values(array(
      'name' => 'forum_nav_vocabulary',
      'value' => 's:1:"2";',
    ))->values(array(
      'name' => 'forum_order',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'forum_per_page',
      'value' => 'i:25;',
    ))->values(array(
      'name' => 'image_style_preview_image',
      'value' => 's:33:"core/modules/image/testsample.png";',
    ))->values(array(
      'name' => 'install_profile',
      'value' => 's:8:"standard";',
    ))->values(array(
      'name' => 'install_task',
      'value' => 's:4:"done";',
    ))->values(array(
      'name' => 'install_time',
      'value' => 'i:1421694923;',
    ))->values(array(
      'name' => 'language_content_type_article',
      'value' => 's:1:"0";',
    ))->values(array(
      'name' => 'language_content_type_blog',
      'value' => 's:1:"0";',
    ))->values(array(
      'name' => 'language_content_type_book',
      'value' => 's:1:"0";',
    ))->values(array(
      'name' => 'language_content_type_forum',
      'value' => 's:1:"0";',
    ))->values(array(
      'name' => 'language_content_type_page',
      'value' => 's:1:"0";',
    ))->values(array(
      'name' => 'language_content_type_test_content_type',
      'value' => 's:1:"0";',
    ))->values(array(
      'name' => 'language_negotiation_language',
      'value' => 'a:0:{}',
    ))->values(array(
      'name' => 'language_negotiation_language_content',
      'value' => 'a:1:{s:16:"locale-interface";a:2:{s:9:"callbacks";a:1:{s:8:"language";s:30:"locale_language_from_interface";}s:4:"file";s:19:"includes/locale.inc";}}',
    ))->values(array(
      'name' => 'language_negotiation_language_url',
      'value' => 'a:2:{s:10:"locale-url";a:2:{s:9:"callbacks";a:3:{s:8:"language";s:24:"locale_language_from_url";s:8:"switcher";s:28:"locale_language_switcher_url";s:11:"url_rewrite";s:31:"locale_language_url_rewrite_url";}s:4:"file";s:19:"includes/locale.inc";}s:19:"locale-url-fallback";a:2:{s:9:"callbacks";a:1:{s:8:"language";s:28:"locale_language_url_fallback";}s:4:"file";s:19:"includes/locale.inc";}}',
    ))->values(array(
      'name' => 'language_types',
      'value' => 'a:3:{s:8:"language";b:1;s:16:"language_content";b:0;s:12:"language_url";b:0;}',
    ))->values(array(
      'name' => 'locale_language_negotiation_session_param',
      'value' => 's:8:"language";',
    ))->values(array(
      'name' => 'locale_language_negotiation_url_part',
      'value' => 's:6:"domain";',
    ))->values(array(
      'name' => 'maintenance_mode',
      'value' => 'i:0;',
    ))->values(array(
      'name' => 'maintenance_mode_message',
      'value' => 's:42:"This is a custom maintenance mode message.";',
    ))->values(array(
      'name' => 'menu_expanded',
      'value' => 'a:0:{}',
    ))->values(array(
      'name' => 'menu_masks',
      'value' => 'a:36:{i:0;i:501;i:1;i:493;i:2;i:250;i:3;i:247;i:4;i:246;i:5;i:245;i:6;i:126;i:7;i:125;i:8;i:123;i:9;i:122;i:10;i:121;i:11;i:117;i:12;i:63;i:13;i:62;i:14;i:61;i:15;i:60;i:16;i:59;i:17;i:58;i:18;i:44;i:19;i:31;i:20;i:30;i:21;i:29;i:22;i:24;i:23;i:21;i:24;i:15;i:25;i:14;i:26;i:13;i:27;i:12;i:28;i:11;i:29;i:8;i:30;i:7;i:31;i:6;i:32;i:5;i:33;i:3;i:34;i:2;i:35;i:1;}',
    ))->values(array(
      'name' => 'menu_options_article',
      'value' => 'a:1:{i:0;s:9:"main-menu";}',
    ))->values(array(
      'name' => 'menu_options_blog',
      'value' => 'a:1:{i:0;s:9:"main-menu";}',
    ))->values(array(
      'name' => 'menu_options_book',
      'value' => 'a:1:{i:0;s:9:"main-menu";}',
    ))->values(array(
      'name' => 'menu_options_forum',
      'value' => 'a:1:{i:0;s:9:"main-menu";}',
    ))->values(array(
      'name' => 'menu_options_page',
      'value' => 'a:1:{i:0;s:9:"main-menu";}',
    ))->values(array(
      'name' => 'menu_options_test_content_type',
      'value' => 'a:4:{i:0;s:9:"main-menu";i:1;s:10:"management";i:2;s:10:"navigation";i:3;s:9:"user-menu";}',
    ))->values(array(
      'name' => 'menu_override_parent_selector',
      'value' => 'b:1;',
    ))->values(array(
      'name' => 'menu_parent_article',
      'value' => 's:11:"main-menu:0";',
    ))->values(array(
      'name' => 'menu_parent_blog',
      'value' => 's:11:"main-menu:0";',
    ))->values(array(
      'name' => 'menu_parent_book',
      'value' => 's:11:"main-menu:0";',
    ))->values(array(
      'name' => 'menu_parent_forum',
      'value' => 's:11:"main-menu:0";',
    ))->values(array(
      'name' => 'menu_parent_page',
      'value' => 's:11:"main-menu:0";',
    ))->values(array(
      'name' => 'menu_parent_test_content_type',
      'value' => 's:11:"main-menu:0";',
    ))->values(array(
      'name' => 'minimum_word_size',
      'value' => 's:1:"4";',
    ))->values(array(
      'name' => 'node_admin_theme',
      'value' => 's:1:"1";',
    ))->values(array(
      'name' => 'node_cron_last',
      'value' => 's:10:"1421727515";',
    ))->values(array(
      'name' => 'node_options_article',
      'value' => 'a:2:{i:0;s:6:"status";i:1;s:7:"promote";}',
    ))->values(array(
      'name' => 'node_options_blog',
      'value' => 'a:2:{i:0;s:6:"status";i:1;s:7:"promote";}',
    ))->values(array(
      'name' => 'node_options_book',
      'value' => 'a:2:{i:0;s:6:"status";i:1;s:8:"revision";}',
    ))->values(array(
      'name' => 'node_options_forum',
      'value' => 'a:1:{i:0;s:6:"status";}',
    ))->values(array(
      'name' => 'node_options_page',
      'value' => 'a:1:{i:0;s:6:"status";}',
    ))->values(array(
      'name' => 'node_options_test_content_type',
      'value' => 'a:3:{i:0;s:6:"status";i:1;s:7:"promote";i:2;s:8:"revision";}',
    ))->values(array(
      'name' => 'node_preview_article',
      'value' => 's:1:"1";',
    ))->values(array(
      'name' => 'node_preview_blog',
      'value' => 's:1:"1";',
    ))->values(array(
      'name' => 'node_preview_book',
      'value' => 's:1:"1";',
    ))->values(array(
      'name' => 'node_preview_forum',
      'value' => 's:1:"1";',
    ))->values(array(
      'name' => 'node_preview_page',
      'value' => 's:1:"1";',
    ))->values(array(
      'name' => 'node_preview_test_content_type',
      'value' => 's:1:"1";',
    ))->values(array(
      'name' => 'node_rank_comments',
      'value' => 's:1:"0";',
    ))->values(array(
      'name' => 'node_rank_promote',
      'value' => 's:1:"0";',
    ))->values(array(
      'name' => 'node_rank_relevance',
      'value' => 's:1:"2";',
    ))->values(array(
      'name' => 'node_rank_sticky',
      'value' => 's:1:"0";',
    ))->values(array(
      'name' => 'node_rank_views',
      'value' => 's:1:"0";',
    ))->values(array(
      'name' => 'node_submitted_article',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'node_submitted_blog',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'node_submitted_book',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'node_submitted_forum',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'node_submitted_page',
      'value' => 'i:0;',
    ))->values(array(
      'name' => 'node_submitted_test_content_type',
      'value' => 'i:0;',
    ))->values(array(
      'name' => 'overlap_cjk',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'page_cache_maximum_age',
      'value' => 's:1:"0";',
    ))->values(array(
      'name' => 'page_compression',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'path_alias_whitelist',
      'value' => 'a:1:{s:8:"taxonomy";b:1;}',
    ))->values(array(
      'name' => 'preprocess_css',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'preprocess_js',
      'value' => 'i:0;',
    ))->values(array(
      'name' => 'save_continue_test_content_type',
      'value' => 's:19:"Save and add fields";',
    ))->values(array(
      'name' => 'search_active_modules',
      'value' => 'a:2:{s:4:"node";s:4:"node";s:4:"user";s:4:"user";}',
    ))->values(array(
      'name' => 'search_and_or_limit',
      'value' => 'i:7;',
    ))->values(array(
      'name' => 'search_cron_limit',
      'value' => 's:3:"100";',
    ))->values(array(
      'name' => 'search_default_module',
      'value' => 's:4:"node";',
    ))->values(array(
      'name' => 'search_tag_weights',
      'value' => 'a:12:{s:2:"h1";i:25;s:2:"h2";i:18;s:2:"h3";i:15;s:2:"h4";i:12;s:2:"h5";i:9;s:2:"h6";i:6;s:1:"u";i:3;s:1:"b";i:3;s:1:"i";i:3;s:6:"strong";i:3;s:2:"em";i:3;s:1:"a";i:10;}',
    ))->values(array(
      'name' => 'simpletest_clear_results',
      'value' => 'b:1;',
    ))->values(array(
      'name' => 'simpletest_httpauth_method',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'simpletest_httpauth_password',
      'value' => 's:6:"foobaz";',
    ))->values(array(
      'name' => 'simpletest_httpauth_username',
      'value' => 's:7:"testbot";',
    ))->values(array(
      'name' => 'simpletest_verbose',
      'value' => 'b:1;',
    ))->values(array(
      'name' => 'site_403',
      'value' => 's:4:"node";',
    ))->values(array(
      'name' => 'site_404',
      'value' => 's:4:"node";',
    ))->values(array(
      'name' => 'site_default_country',
      'value' => 's:2:"US";',
    ))->values(array(
      'name' => 'site_frontpage',
      'value' => 's:4:"node";',
    ))->values(array(
      'name' => 'site_mail',
      'value' => 's:23:"joseph@flattandsons.com";',
    ))->values(array(
      'name' => 'site_name',
      'value' => 's:13:"The Site Name";',
    ))->values(array(
      'name' => 'site_slogan',
      'value' => 's:10:"The Slogan";',
    ))->values(array(
      'name' => 'statistics_count_content_views',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'statistics_count_content_views_ajax',
      'value' => 'i:0;',
    ))->values(array(
      'name' => 'statistics_enable_access_log',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'statistics_flush_accesslog_timer',
      'value' => 's:4:"3600";',
    ))->values(array(
      'name' => 'suppress_itok_output',
      'value' => 'b:1;',
    ))->values(array(
      'name' => 'syslog_facility',
      'value' => 'i:8;',
    ))->values(array(
      'name' => 'syslog_format',
      'value' => 's:72:"!base_url|!timestamp|!type|!ip|!request_uri|!referer|!uid|!link|!message";',
    ))->values(array(
      'name' => 'syslog_identity',
      'value' => 's:6:"drupal";',
    ))->values(array(
      'name' => 'teaser_length',
      'value' => 'i:1024;',
    ))->values(array(
      'name' => 'theme_default',
      'value' => 's:6:"bartik";',
    ))->values(array(
      'name' => 'tracker_batch_size',
      'value' => 'i:999;',
    ))->values(array(
      'name' => 'user_admin_role',
      'value' => 's:1:"3";',
    ))->values(array(
      'name' => 'user_cancel_method',
      'value' => 's:17:"user_cancel_block";',
    ))->values(array(
      'name' => 'user_email_verification',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'user_failed_login_identifier_uid_only',
      'value' => 'b:1;',
    ))->values(array(
      'name' => 'user_failed_login_ip_limit',
      'value' => 'i:30;',
    ))->values(array(
      'name' => 'user_failed_login_ip_window',
      'value' => 'i:7200;',
    ))->values(array(
      'name' => 'user_failed_login_user_limit',
      'value' => 'i:22;',
    ))->values(array(
      'name' => 'user_failed_login_user_window',
      'value' => 'i:86400;',
    ))->values(array(
      'name' => 'user_mail_cancel_confirm_body',
      'value' => 's:55:"A little birdie said you wanted to cancel your account.";',
    ))->values(array(
      'name' => 'user_mail_cancel_confirm_subject',
      'value' => 's:13:"Are you sure?";',
    ))->values(array(
      'name' => 'user_mail_password_reset_body',
      'value' => "s:32:\"Nope! You're locked out forever.\";",
    ))->values(array(
      'name' => 'user_mail_password_reset_subject',
      'value' => 's:17:"Fix your password";',
    ))->values(array(
      'name' => 'user_mail_register_admin_created_body',
      'value' => 's:30:"...and she could take it away.";',
    ))->values(array(
      'name' => 'user_mail_register_admin_created_subject',
      'value' => 's:24:"Gawd made you an account";',
    ))->values(array(
      'name' => 'user_mail_register_no_approval_required_body',
      'value' => 's:59:"You can now log in if you can figure out how to use Drupal!";',
    ))->values(array(
      'name' => 'user_mail_register_no_approval_required_subject',
      'value' => 's:8:"Welcome!";',
    ))->values(array(
      'name' => 'user_mail_register_pending_approval_body',
      'value' => 's:61:"...you will join our Circle. Let the Drupal flow through you.";',
    ))->values(array(
      'name' => 'user_mail_register_pending_approval_subject',
      'value' => 's:7:"Soon...";',
    ))->values(array(
      'name' => 'user_mail_status_activated_body',
      'value' => 's:57:"Your account was activated, and there was much rejoicing.";',
    ))->values(array(
      'name' => 'user_mail_status_activated_notify',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'user_mail_status_activated_subject',
      'value' => 's:25:"Your account is approved!";',
    ))->values(array(
      'name' => 'user_mail_status_blocked_body',
      'value' => 's:72:"You no longer please the robot overlords. Go to your room and chill out.";',
    ))->values(array(
      'name' => 'user_mail_status_blocked_notify',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'user_mail_status_blocked_subject',
      'value' => 's:7:"BEGONE!";',
    ))->values(array(
      'name' => 'user_mail_status_canceled_body',
      'value' => 's:75:"The gates of Drupal are closed to you. Now you will work in the salt mines.";',
    ))->values(array(
      'name' => 'user_mail_status_canceled_notify',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'user_mail_status_canceled_subject',
      'value' => 's:12:"So long, bub";',
    ))->values(array(
      'name' => 'user_pictures',
      'value' => 'i:1;',
    ))->values(array(
      'name' => 'user_picture_default',
      'value' => 's:0:"";',
    ))->values(array(
      'name' => 'user_picture_dimensions',
      'value' => 's:9:"1024x1024";',
    ))->values(array(
      'name' => 'user_picture_file_size',
      'value' => 's:3:"800";',
    ))->values(array(
      'name' => 'user_picture_guidelines',
      'value' => 's:0:"";',
    ))->values(array(
      'name' => 'user_picture_path',
      'value' => 's:8:"pictures";',
    ))->values(array(
      'name' => 'user_picture_style',
      'value' => 's:9:"thumbnail";',
    ))->values(array(
      'name' => 'user_register',
      'value' => 's:1:"2";',
    ))->values(array(
      'name' => 'user_signatures',
      'value' => 'i:0;',
    ))->execute();
  }

}
#d20a0a7f31645aba285f0ce7505c80a5
