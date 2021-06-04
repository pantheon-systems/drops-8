<?php











namespace Composer;

use Composer\Autoload\ClassLoader;
use Composer\Semver\VersionParser;






class InstalledVersions
{
private static $installed = array (
  'root' => 
  array (
    'pretty_version' => '1.0.0+no-version-set',
    'version' => '1.0.0.0',
    'aliases' => 
    array (
    ),
    'reference' => NULL,
    'name' => 'drupal/legacy-project',
  ),
  'versions' => 
  array (
    'asm89/stack-cors' => 
    array (
      'pretty_version' => '1.3.0',
      'version' => '1.3.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'b9c31def6a83f84b4d4a40d35996d375755f0e08',
    ),
    'composer/installers' => 
    array (
      'pretty_version' => 'v1.11.0',
      'version' => '1.11.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'ae03311f45dfe194412081526be2e003960df74b',
    ),
    'composer/semver' => 
    array (
      'pretty_version' => '3.2.2',
      'version' => '3.2.2.0',
      'aliases' => 
      array (
      ),
      'reference' => '4089fddb67bcf6bf860d91b979e95be303835002',
    ),
    'doctrine/annotations' => 
    array (
      'pretty_version' => '1.11.1',
      'version' => '1.11.1.0',
      'aliases' => 
      array (
      ),
      'reference' => 'ce77a7ba1770462cd705a91a151b6c3746f9c6ad',
    ),
    'doctrine/lexer' => 
    array (
      'pretty_version' => '1.2.1',
      'version' => '1.2.1.0',
      'aliases' => 
      array (
      ),
      'reference' => 'e864bbf5904cb8f5bb334f99209b48018522f042',
    ),
    'doctrine/reflection' => 
    array (
      'pretty_version' => '1.2.2',
      'version' => '1.2.2.0',
      'aliases' => 
      array (
      ),
      'reference' => 'fa587178be682efe90d005e3a322590d6ebb59a5',
    ),
    'drupal/action' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/aggregator' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/automated_cron' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/ban' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/bartik' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/basic_auth' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/big_pipe' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/block' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/block_content' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/book' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/breakpoint' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/ckeditor' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/claro' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/classy' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/color' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/comment' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/config' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/config_translation' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/contact' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/content_moderation' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/content_translation' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/contextual' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/core' => 
    array (
      'pretty_version' => '9.1.10',
      'version' => '9.1.10.0',
      'aliases' => 
      array (
      ),
      'reference' => '7fa70eb78addcef8ad704edad9fa73337b8cdab5',
    ),
    'drupal/core-annotation' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/core-assertion' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/core-bridge' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/core-class-finder' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/core-composer-scaffold' => 
    array (
      'pretty_version' => '9.1.10',
      'version' => '9.1.10.0',
      'aliases' => 
      array (
      ),
      'reference' => '7b125516d6568b888945ee03ac2636dcced76e8d',
    ),
    'drupal/core-datetime' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/core-dependency-injection' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/core-diff' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/core-discovery' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/core-event-dispatcher' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/core-file-cache' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/core-file-security' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/core-filesystem' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/core-front-matter' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/core-gettext' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/core-graph' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/core-http-foundation' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/core-php-storage' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/core-plugin' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/core-project-message' => 
    array (
      'pretty_version' => '9.1.10',
      'version' => '9.1.10.0',
      'aliases' => 
      array (
      ),
      'reference' => '812d6da43dd49cc210af62e80fa92189e68e565a',
    ),
    'drupal/core-proxy-builder' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/core-recommended' => 
    array (
      'pretty_version' => '9.1.10',
      'version' => '9.1.10.0',
      'aliases' => 
      array (
      ),
      'reference' => 'cf3bc76f7f15a77b6655d74a535ed6e704da9490',
    ),
    'drupal/core-render' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/core-serialization' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/core-transliteration' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/core-utility' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/core-uuid' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/core-vendor-hardening' => 
    array (
      'pretty_version' => '9.1.10',
      'version' => '9.1.10.0',
      'aliases' => 
      array (
      ),
      'reference' => '71df53f24d54c464ac18762a530fc7c3bf131a7f',
    ),
    'drupal/core-version' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/datetime' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/datetime_range' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/dblog' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/dynamic_page_cache' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/editor' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/entity_reference' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/field' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/field_layout' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/field_ui' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/file' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/filter' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/forum' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/hal' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/help' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/help_topics' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/history' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/image' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/inline_form_errors' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/jsonapi' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/language' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/layout_builder' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/layout_discovery' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/legacy-project' => 
    array (
      'pretty_version' => '1.0.0+no-version-set',
      'version' => '1.0.0.0',
      'aliases' => 
      array (
      ),
      'reference' => NULL,
    ),
    'drupal/link' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/locale' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/media' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/media_library' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/menu_link_content' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/menu_ui' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/migrate' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/migrate_drupal' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/migrate_drupal_multilingual' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/migrate_drupal_ui' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/minimal' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/node' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/olivero' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/options' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/page_cache' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/path' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/path_alias' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/quickedit' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/rdf' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/responsive_image' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/rest' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/search' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/serialization' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/settings_tray' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/seven' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/shortcut' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/standard' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/stark' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/statistics' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/syslog' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/system' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/taxonomy' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/telephone' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/text' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/toolbar' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/tour' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/tracker' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/update' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/user' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/views' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/views_ui' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/workflows' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'drupal/workspaces' => 
    array (
      'replaced' => 
      array (
        0 => '9.1.10',
      ),
    ),
    'egulias/email-validator' => 
    array (
      'pretty_version' => '2.1.22',
      'version' => '2.1.22.0',
      'aliases' => 
      array (
      ),
      'reference' => '68e418ec08fbfc6f58f6fd2eea70ca8efc8cc7d5',
    ),
    'guzzlehttp/guzzle' => 
    array (
      'pretty_version' => '6.5.5',
      'version' => '6.5.5.0',
      'aliases' => 
      array (
      ),
      'reference' => '9d4290de1cfd701f38099ef7e183b64b4b7b0c5e',
    ),
    'guzzlehttp/promises' => 
    array (
      'pretty_version' => '1.4.0',
      'version' => '1.4.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '60d379c243457e073cff02bc323a2a86cb355631',
    ),
    'guzzlehttp/psr7' => 
    array (
      'pretty_version' => '1.7.0',
      'version' => '1.7.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '53330f47520498c0ae1f61f7e2c90f55690c06a3',
    ),
    'laminas/laminas-diactoros' => 
    array (
      'pretty_version' => '2.5.0',
      'version' => '2.5.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '4ff7400c1c12e404144992ef43c8b733fd9ad516',
    ),
    'laminas/laminas-escaper' => 
    array (
      'pretty_version' => '2.7.0',
      'version' => '2.7.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '5e04bc5ae5990b17159d79d331055e2c645e5cc5',
    ),
    'laminas/laminas-feed' => 
    array (
      'pretty_version' => '2.13.0',
      'version' => '2.13.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'fb89aac1984222227f37792dd193d34829a0762f',
    ),
    'laminas/laminas-stdlib' => 
    array (
      'pretty_version' => '3.3.0',
      'version' => '3.3.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'b9d84eaa39fde733356ea948cdef36c631f202b6',
    ),
    'laminas/laminas-zendframework-bridge' => 
    array (
      'pretty_version' => '1.1.1',
      'version' => '1.1.1.0',
      'aliases' => 
      array (
      ),
      'reference' => '6ede70583e101030bcace4dcddd648f760ddf642',
    ),
    'masterminds/html5' => 
    array (
      'pretty_version' => '2.7.4',
      'version' => '2.7.4.0',
      'aliases' => 
      array (
      ),
      'reference' => '9227822783c75406cfe400984b2f095cdf03d417',
    ),
    'pear/archive_tar' => 
    array (
      'pretty_version' => '1.4.13',
      'version' => '1.4.13.0',
      'aliases' => 
      array (
      ),
      'reference' => '2b87b41178cc6d4ad3cba678a46a1cae49786011',
    ),
    'pear/console_getopt' => 
    array (
      'pretty_version' => 'v1.4.3',
      'version' => '1.4.3.0',
      'aliases' => 
      array (
      ),
      'reference' => 'a41f8d3e668987609178c7c4a9fe48fecac53fa0',
    ),
    'pear/pear-core-minimal' => 
    array (
      'pretty_version' => 'v1.10.10',
      'version' => '1.10.10.0',
      'aliases' => 
      array (
      ),
      'reference' => '625a3c429d9b2c1546438679074cac1b089116a7',
    ),
    'pear/pear_exception' => 
    array (
      'pretty_version' => 'v1.0.1',
      'version' => '1.0.1.0',
      'aliases' => 
      array (
      ),
      'reference' => 'dbb42a5a0e45f3adcf99babfb2a1ba77b8ac36a7',
    ),
    'psr/container' => 
    array (
      'pretty_version' => '1.0.0',
      'version' => '1.0.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'b7ce3b176482dbbc1245ebf52b181af44c2cf55f',
    ),
    'psr/container-implementation' => 
    array (
      'provided' => 
      array (
        0 => '1.0',
      ),
    ),
    'psr/event-dispatcher-implementation' => 
    array (
      'provided' => 
      array (
        0 => '1.0',
      ),
    ),
    'psr/http-factory' => 
    array (
      'pretty_version' => '1.0.1',
      'version' => '1.0.1.0',
      'aliases' => 
      array (
      ),
      'reference' => '12ac7fcd07e5b077433f5f2bee95b3a771bf61be',
    ),
    'psr/http-factory-implementation' => 
    array (
      'provided' => 
      array (
        0 => '1.0',
      ),
    ),
    'psr/http-message' => 
    array (
      'pretty_version' => '1.0.1',
      'version' => '1.0.1.0',
      'aliases' => 
      array (
      ),
      'reference' => 'f6561bf28d520154e4b0ec72be95418abe6d9363',
    ),
    'psr/http-message-implementation' => 
    array (
      'provided' => 
      array (
        0 => '1.0',
      ),
    ),
    'psr/log' => 
    array (
      'pretty_version' => '1.1.3',
      'version' => '1.1.3.0',
      'aliases' => 
      array (
      ),
      'reference' => '0f73288fd15629204f9d42b7055f72dacbe811fc',
    ),
    'psr/log-implementation' => 
    array (
      'provided' => 
      array (
        0 => '1.0',
      ),
    ),
    'ralouphie/getallheaders' => 
    array (
      'pretty_version' => '3.0.3',
      'version' => '3.0.3.0',
      'aliases' => 
      array (
      ),
      'reference' => '120b605dfeb996808c31b6477290a714d356e822',
    ),
    'roundcube/plugin-installer' => 
    array (
      'replaced' => 
      array (
        0 => '*',
      ),
    ),
    'rsky/pear-core-min' => 
    array (
      'replaced' => 
      array (
        0 => 'v1.10.10',
      ),
    ),
    'shama/baton' => 
    array (
      'replaced' => 
      array (
        0 => '*',
      ),
    ),
    'stack/builder' => 
    array (
      'pretty_version' => 'v1.0.6',
      'version' => '1.0.6.0',
      'aliases' => 
      array (
      ),
      'reference' => 'a4faaa6f532c6086bc66c29e1bc6c29593e1ca7c',
    ),
    'symfony-cmf/routing' => 
    array (
      'pretty_version' => '2.3.3',
      'version' => '2.3.3.0',
      'aliases' => 
      array (
      ),
      'reference' => '3c97e7b7709b313cecfb76d691ad4cc22acbf3f5',
    ),
    'symfony/console' => 
    array (
      'pretty_version' => 'v4.4.19',
      'version' => '4.4.19.0',
      'aliases' => 
      array (
      ),
      'reference' => '24026c44fc37099fa145707fecd43672831b837a',
    ),
    'symfony/debug' => 
    array (
      'pretty_version' => 'v4.4.19',
      'version' => '4.4.19.0',
      'aliases' => 
      array (
      ),
      'reference' => 'af4987aa4a5630e9615be9d9c3ed1b0f24ca449c',
    ),
    'symfony/dependency-injection' => 
    array (
      'pretty_version' => 'v4.4.19',
      'version' => '4.4.19.0',
      'aliases' => 
      array (
      ),
      'reference' => '2468b95d869c872c6fb1b93b395a7fcd5331f2b9',
    ),
    'symfony/error-handler' => 
    array (
      'pretty_version' => 'v4.4.19',
      'version' => '4.4.19.0',
      'aliases' => 
      array (
      ),
      'reference' => 'd603654eaeb713503bba3e308b9e748e5a6d3f2e',
    ),
    'symfony/event-dispatcher' => 
    array (
      'pretty_version' => 'v4.4.19',
      'version' => '4.4.19.0',
      'aliases' => 
      array (
      ),
      'reference' => 'c352647244bd376bf7d31efbd5401f13f50dad0c',
    ),
    'symfony/event-dispatcher-contracts' => 
    array (
      'pretty_version' => 'v1.1.9',
      'version' => '1.1.9.0',
      'aliases' => 
      array (
      ),
      'reference' => '84e23fdcd2517bf37aecbd16967e83f0caee25a7',
    ),
    'symfony/event-dispatcher-implementation' => 
    array (
      'provided' => 
      array (
        0 => '1.1',
      ),
    ),
    'symfony/http-client-contracts' => 
    array (
      'pretty_version' => 'v2.3.1',
      'version' => '2.3.1.0',
      'aliases' => 
      array (
      ),
      'reference' => '41db680a15018f9c1d4b23516059633ce280ca33',
    ),
    'symfony/http-foundation' => 
    array (
      'pretty_version' => 'v4.4.19',
      'version' => '4.4.19.0',
      'aliases' => 
      array (
      ),
      'reference' => '8888741b633f6c3d1e572b7735ad2cae3e03f9c5',
    ),
    'symfony/http-kernel' => 
    array (
      'pretty_version' => 'v4.4.19',
      'version' => '4.4.19.0',
      'aliases' => 
      array (
      ),
      'reference' => '07ea794a327d7c8c5d76e3058fde9fec6a711cb4',
    ),
    'symfony/mime' => 
    array (
      'pretty_version' => 'v5.1.11',
      'version' => '5.1.11.0',
      'aliases' => 
      array (
      ),
      'reference' => 'd7d899822da1fa89bcf658e8e8d836f5578e6f7a',
    ),
    'symfony/polyfill-ctype' => 
    array (
      'pretty_version' => 'v1.20.0',
      'version' => '1.20.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'f4ba089a5b6366e453971d3aad5fe8e897b37f41',
    ),
    'symfony/polyfill-iconv' => 
    array (
      'pretty_version' => 'v1.20.0',
      'version' => '1.20.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'c536646fdb4f29104dd26effc2fdcb9a5b085024',
    ),
    'symfony/polyfill-intl-idn' => 
    array (
      'pretty_version' => 'v1.20.0',
      'version' => '1.20.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '3b75acd829741c768bc8b1f84eb33265e7cc5117',
    ),
    'symfony/polyfill-intl-normalizer' => 
    array (
      'pretty_version' => 'v1.20.0',
      'version' => '1.20.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '727d1096295d807c309fb01a851577302394c897',
    ),
    'symfony/polyfill-mbstring' => 
    array (
      'pretty_version' => 'v1.20.0',
      'version' => '1.20.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '39d483bdf39be819deabf04ec872eb0b2410b531',
    ),
    'symfony/polyfill-php72' => 
    array (
      'pretty_version' => 'v1.23.0',
      'version' => '1.23.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '9a142215a36a3888e30d0a9eeea9766764e96976',
    ),
    'symfony/polyfill-php73' => 
    array (
      'pretty_version' => 'v1.23.0',
      'version' => '1.23.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'fba8933c384d6476ab14fb7b8526e5287ca7e010',
    ),
    'symfony/polyfill-php80' => 
    array (
      'pretty_version' => 'v1.20.0',
      'version' => '1.20.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'e70aa8b064c5b72d3df2abd5ab1e90464ad009de',
    ),
    'symfony/process' => 
    array (
      'pretty_version' => 'v4.4.19',
      'version' => '4.4.19.0',
      'aliases' => 
      array (
      ),
      'reference' => '7e950b6366d4da90292c2e7fa820b3c1842b965a',
    ),
    'symfony/psr-http-message-bridge' => 
    array (
      'pretty_version' => 'v2.0.2',
      'version' => '2.0.2.0',
      'aliases' => 
      array (
      ),
      'reference' => '51a21cb3ba3927d4b4bf8f25cc55763351af5f2e',
    ),
    'symfony/routing' => 
    array (
      'pretty_version' => 'v4.4.19',
      'version' => '4.4.19.0',
      'aliases' => 
      array (
      ),
      'reference' => '87529f6e305c7acb162840d1ea57922038072425',
    ),
    'symfony/serializer' => 
    array (
      'pretty_version' => 'v4.4.19',
      'version' => '4.4.19.0',
      'aliases' => 
      array (
      ),
      'reference' => '6b383bc45777d14857b634e9f8fa2b8a2e69b66d',
    ),
    'symfony/service-contracts' => 
    array (
      'pretty_version' => 'v2.2.0',
      'version' => '2.2.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'd15da7ba4957ffb8f1747218be9e1a121fd298a1',
    ),
    'symfony/service-implementation' => 
    array (
      'provided' => 
      array (
        0 => '1.0',
      ),
    ),
    'symfony/translation' => 
    array (
      'pretty_version' => 'v4.4.19',
      'version' => '4.4.19.0',
      'aliases' => 
      array (
      ),
      'reference' => 'e1d0c67167a553556d9f974b5fa79c2448df317a',
    ),
    'symfony/translation-contracts' => 
    array (
      'pretty_version' => 'v2.3.0',
      'version' => '2.3.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'e2eaa60b558f26a4b0354e1bbb25636efaaad105',
    ),
    'symfony/translation-implementation' => 
    array (
      'provided' => 
      array (
        0 => '1.0',
      ),
    ),
    'symfony/validator' => 
    array (
      'pretty_version' => 'v4.4.19',
      'version' => '4.4.19.0',
      'aliases' => 
      array (
      ),
      'reference' => '039479123c8d824f23efba9bb413b85dc3f42e43',
    ),
    'symfony/var-dumper' => 
    array (
      'pretty_version' => 'v5.1.11',
      'version' => '5.1.11.0',
      'aliases' => 
      array (
      ),
      'reference' => 'cee600a1248b423330375c869812bdd61a085cd0',
    ),
    'symfony/yaml' => 
    array (
      'pretty_version' => 'v4.4.19',
      'version' => '4.4.19.0',
      'aliases' => 
      array (
      ),
      'reference' => '17ed9f14c1aa05b1a5cf2e2c5ef2d0be28058ef9',
    ),
    'twig/twig' => 
    array (
      'pretty_version' => 'v2.14.1',
      'version' => '2.14.1.0',
      'aliases' => 
      array (
      ),
      'reference' => '5eb9ac5dfdd20c3f59495c22841adc5da980d312',
    ),
    'typo3/phar-stream-wrapper' => 
    array (
      'pretty_version' => 'v3.1.6',
      'version' => '3.1.6.0',
      'aliases' => 
      array (
      ),
      'reference' => '60131cb573a1e478cfecd34e4ea38e3b31505f75',
    ),
    'zendframework/zend-diactoros' => 
    array (
      'replaced' => 
      array (
        0 => '^2.2.1',
      ),
    ),
    'zendframework/zend-escaper' => 
    array (
      'replaced' => 
      array (
        0 => '^2.6.1',
      ),
    ),
    'zendframework/zend-feed' => 
    array (
      'replaced' => 
      array (
        0 => '^2.12.0',
      ),
    ),
    'zendframework/zend-stdlib' => 
    array (
      'replaced' => 
      array (
        0 => '^3.2.1',
      ),
    ),
  ),
);
private static $canGetVendors;
private static $installedByVendor = array();







public static function getInstalledPackages()
{
$packages = array();
foreach (self::getInstalled() as $installed) {
$packages[] = array_keys($installed['versions']);
}


if (1 === \count($packages)) {
return $packages[0];
}

return array_keys(array_flip(\call_user_func_array('array_merge', $packages)));
}









public static function isInstalled($packageName)
{
foreach (self::getInstalled() as $installed) {
if (isset($installed['versions'][$packageName])) {
return true;
}
}

return false;
}














public static function satisfies(VersionParser $parser, $packageName, $constraint)
{
$constraint = $parser->parseConstraints($constraint);
$provided = $parser->parseConstraints(self::getVersionRanges($packageName));

return $provided->matches($constraint);
}










public static function getVersionRanges($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

$ranges = array();
if (isset($installed['versions'][$packageName]['pretty_version'])) {
$ranges[] = $installed['versions'][$packageName]['pretty_version'];
}
if (array_key_exists('aliases', $installed['versions'][$packageName])) {
$ranges = array_merge($ranges, $installed['versions'][$packageName]['aliases']);
}
if (array_key_exists('replaced', $installed['versions'][$packageName])) {
$ranges = array_merge($ranges, $installed['versions'][$packageName]['replaced']);
}
if (array_key_exists('provided', $installed['versions'][$packageName])) {
$ranges = array_merge($ranges, $installed['versions'][$packageName]['provided']);
}

return implode(' || ', $ranges);
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getVersion($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

if (!isset($installed['versions'][$packageName]['version'])) {
return null;
}

return $installed['versions'][$packageName]['version'];
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getPrettyVersion($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

if (!isset($installed['versions'][$packageName]['pretty_version'])) {
return null;
}

return $installed['versions'][$packageName]['pretty_version'];
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getReference($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

if (!isset($installed['versions'][$packageName]['reference'])) {
return null;
}

return $installed['versions'][$packageName]['reference'];
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getRootPackage()
{
$installed = self::getInstalled();

return $installed[0]['root'];
}







public static function getRawData()
{
return self::$installed;
}



















public static function reload($data)
{
self::$installed = $data;
self::$installedByVendor = array();
}




private static function getInstalled()
{
if (null === self::$canGetVendors) {
self::$canGetVendors = method_exists('Composer\Autoload\ClassLoader', 'getRegisteredLoaders');
}

$installed = array();

if (self::$canGetVendors) {
foreach (ClassLoader::getRegisteredLoaders() as $vendorDir => $loader) {
if (isset(self::$installedByVendor[$vendorDir])) {
$installed[] = self::$installedByVendor[$vendorDir];
} elseif (is_file($vendorDir.'/composer/installed.php')) {
$installed[] = self::$installedByVendor[$vendorDir] = require $vendorDir.'/composer/installed.php';
}
}
}

$installed[] = self::$installed;

return $installed;
}
}
