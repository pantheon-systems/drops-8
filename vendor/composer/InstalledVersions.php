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
      'pretty_version' => 'v1.12.0',
      'version' => '1.12.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'd20a64ed3c94748397ff5973488761b22f6d3f19',
    ),
    'composer/semver' => 
    array (
      'pretty_version' => '3.3.2',
      'version' => '3.3.2.0',
      'aliases' => 
      array (
      ),
      'reference' => '3953f23262f2bff1919fc82183ad9acb13ff62c9',
    ),
    'doctrine/annotations' => 
    array (
      'pretty_version' => '1.13.3',
      'version' => '1.13.3.0',
      'aliases' => 
      array (
      ),
      'reference' => '648b0343343565c4a056bfc8392201385e8d89f0',
    ),
    'doctrine/lexer' => 
    array (
      'pretty_version' => '1.2.3',
      'version' => '1.2.3.0',
      'aliases' => 
      array (
      ),
      'reference' => 'c268e882d4dbdd85e36e4ad69e02dc284f89d229',
    ),
    'doctrine/reflection' => 
    array (
      'pretty_version' => '1.2.3',
      'version' => '1.2.3.0',
      'aliases' => 
      array (
      ),
      'reference' => '1034e5e71f89978b80f9c1570e7226f6c3b9b6fb',
    ),
    'drupal/core' => 
    array (
      'pretty_version' => '9.4.8',
      'version' => '9.4.8.0',
      'aliases' => 
      array (
      ),
      'reference' => 'a627d1b2a00f2cef0572e37b94dea298800541f4',
    ),
    'drupal/core-annotation' => 
    array (
      'replaced' => 
      array (
        0 => '9.4.8',
      ),
    ),
    'drupal/core-assertion' => 
    array (
      'replaced' => 
      array (
        0 => '9.4.8',
      ),
    ),
    'drupal/core-bridge' => 
    array (
      'replaced' => 
      array (
        0 => '9.4.8',
      ),
    ),
    'drupal/core-class-finder' => 
    array (
      'replaced' => 
      array (
        0 => '9.4.8',
      ),
    ),
    'drupal/core-composer-scaffold' => 
    array (
      'pretty_version' => '9.4.8',
      'version' => '9.4.8.0',
      'aliases' => 
      array (
      ),
      'reference' => '5f37a9e4008b34e3e4f6bb34ce0b3f7e5ec8984f',
    ),
    'drupal/core-datetime' => 
    array (
      'replaced' => 
      array (
        0 => '9.4.8',
      ),
    ),
    'drupal/core-dependency-injection' => 
    array (
      'replaced' => 
      array (
        0 => '9.4.8',
      ),
    ),
    'drupal/core-diff' => 
    array (
      'replaced' => 
      array (
        0 => '9.4.8',
      ),
    ),
    'drupal/core-discovery' => 
    array (
      'replaced' => 
      array (
        0 => '9.4.8',
      ),
    ),
    'drupal/core-event-dispatcher' => 
    array (
      'replaced' => 
      array (
        0 => '9.4.8',
      ),
    ),
    'drupal/core-file-cache' => 
    array (
      'replaced' => 
      array (
        0 => '9.4.8',
      ),
    ),
    'drupal/core-file-security' => 
    array (
      'replaced' => 
      array (
        0 => '9.4.8',
      ),
    ),
    'drupal/core-filesystem' => 
    array (
      'replaced' => 
      array (
        0 => '9.4.8',
      ),
    ),
    'drupal/core-front-matter' => 
    array (
      'replaced' => 
      array (
        0 => '9.4.8',
      ),
    ),
    'drupal/core-gettext' => 
    array (
      'replaced' => 
      array (
        0 => '9.4.8',
      ),
    ),
    'drupal/core-graph' => 
    array (
      'replaced' => 
      array (
        0 => '9.4.8',
      ),
    ),
    'drupal/core-http-foundation' => 
    array (
      'replaced' => 
      array (
        0 => '9.4.8',
      ),
    ),
    'drupal/core-php-storage' => 
    array (
      'replaced' => 
      array (
        0 => '9.4.8',
      ),
    ),
    'drupal/core-plugin' => 
    array (
      'replaced' => 
      array (
        0 => '9.4.8',
      ),
    ),
    'drupal/core-project-message' => 
    array (
      'pretty_version' => '9.4.8',
      'version' => '9.4.8.0',
      'aliases' => 
      array (
      ),
      'reference' => '5dfa0b75a057caf6542be67f61e7531c737db48c',
    ),
    'drupal/core-proxy-builder' => 
    array (
      'replaced' => 
      array (
        0 => '9.4.8',
      ),
    ),
    'drupal/core-recommended' => 
    array (
      'pretty_version' => '9.4.8',
      'version' => '9.4.8.0',
      'aliases' => 
      array (
      ),
      'reference' => '684cc844f7b729286f5d62f1ee4b20ab12586502',
    ),
    'drupal/core-render' => 
    array (
      'replaced' => 
      array (
        0 => '9.4.8',
      ),
    ),
    'drupal/core-serialization' => 
    array (
      'replaced' => 
      array (
        0 => '9.4.8',
      ),
    ),
    'drupal/core-transliteration' => 
    array (
      'replaced' => 
      array (
        0 => '9.4.8',
      ),
    ),
    'drupal/core-utility' => 
    array (
      'replaced' => 
      array (
        0 => '9.4.8',
      ),
    ),
    'drupal/core-uuid' => 
    array (
      'replaced' => 
      array (
        0 => '9.4.8',
      ),
    ),
    'drupal/core-vendor-hardening' => 
    array (
      'pretty_version' => '9.4.8',
      'version' => '9.4.8.0',
      'aliases' => 
      array (
      ),
      'reference' => '6e2b95d65ca2aac7350039c5826175cab5a81881',
    ),
    'drupal/core-version' => 
    array (
      'replaced' => 
      array (
        0 => '9.4.8',
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
    'egulias/email-validator' => 
    array (
      'pretty_version' => '3.2.1',
      'version' => '3.2.1.0',
      'aliases' => 
      array (
      ),
      'reference' => 'f88dcf4b14af14a98ad96b14b2b317969eab6715',
    ),
    'guzzlehttp/guzzle' => 
    array (
      'pretty_version' => '6.5.8',
      'version' => '6.5.8.0',
      'aliases' => 
      array (
      ),
      'reference' => 'a52f0440530b54fa079ce76e8c5d196a42cad981',
    ),
    'guzzlehttp/promises' => 
    array (
      'pretty_version' => '1.5.2',
      'version' => '1.5.2.0',
      'aliases' => 
      array (
      ),
      'reference' => 'b94b2807d85443f9719887892882d0329d1e2598',
    ),
    'guzzlehttp/psr7' => 
    array (
      'pretty_version' => '1.9.0',
      'version' => '1.9.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'e98e3e6d4f86621a9b75f623996e6bbdeb4b9318',
    ),
    'laminas/laminas-diactoros' => 
    array (
      'pretty_version' => '2.11.3',
      'version' => '2.11.3.0',
      'aliases' => 
      array (
      ),
      'reference' => '1f97b0c52eafd108e09c76d6b29d83ef4a855f76',
    ),
    'laminas/laminas-escaper' => 
    array (
      'pretty_version' => '2.9.0',
      'version' => '2.9.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '891ad70986729e20ed2e86355fcf93c9dc238a5f',
    ),
    'laminas/laminas-feed' => 
    array (
      'pretty_version' => '2.17.0',
      'version' => '2.17.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '1ccb024ea615606ed1d676ba0fa3f22a398f3ac0',
    ),
    'laminas/laminas-stdlib' => 
    array (
      'pretty_version' => '3.7.1',
      'version' => '3.7.1.0',
      'aliases' => 
      array (
      ),
      'reference' => 'bcd869e2fe88d567800057c1434f2380354fe325',
    ),
    'masterminds/html5' => 
    array (
      'pretty_version' => '2.7.6',
      'version' => '2.7.6.0',
      'aliases' => 
      array (
      ),
      'reference' => '897eb517a343a2281f11bc5556d6548db7d93947',
    ),
    'pear/archive_tar' => 
    array (
      'pretty_version' => '1.4.14',
      'version' => '1.4.14.0',
      'aliases' => 
      array (
      ),
      'reference' => '4d761c5334c790e45ef3245f0864b8955c562caa',
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
      'pretty_version' => 'v1.10.11',
      'version' => '1.10.11.0',
      'aliases' => 
      array (
      ),
      'reference' => '68d0d32ada737153b7e93b8d3c710ebe70ac867d',
    ),
    'pear/pear_exception' => 
    array (
      'pretty_version' => 'v1.0.2',
      'version' => '1.0.2.0',
      'aliases' => 
      array (
      ),
      'reference' => 'b14fbe2ddb0b9f94f5b24cf08783d599f776fff0',
    ),
    'psr/cache' => 
    array (
      'pretty_version' => '1.0.1',
      'version' => '1.0.1.0',
      'aliases' => 
      array (
      ),
      'reference' => 'd11b50ad223250cf17b86e38383413f5a6764bf8',
    ),
    'psr/container' => 
    array (
      'pretty_version' => '1.1.1',
      'version' => '1.1.1.0',
      'aliases' => 
      array (
      ),
      'reference' => '8622567409010282b7aeebe4bb841fe98b58dcaf',
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
      'pretty_version' => '1.1.4',
      'version' => '1.1.4.0',
      'aliases' => 
      array (
      ),
      'reference' => 'd49695b909c3b7628b6289db5479a1c204601f11',
    ),
    'psr/log-implementation' => 
    array (
      'provided' => 
      array (
        0 => '1.0|2.0',
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
        0 => 'v1.10.11',
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
      'pretty_version' => '2.3.4',
      'version' => '2.3.4.0',
      'aliases' => 
      array (
      ),
      'reference' => 'bbcdf2f6301d740454ba9ebb8adaefd436c36a6b',
    ),
    'symfony/console' => 
    array (
      'pretty_version' => 'v4.4.45',
      'version' => '4.4.45.0',
      'aliases' => 
      array (
      ),
      'reference' => '28b77970939500fb04180166a1f716e75a871ef8',
    ),
    'symfony/debug' => 
    array (
      'pretty_version' => 'v4.4.44',
      'version' => '4.4.44.0',
      'aliases' => 
      array (
      ),
      'reference' => '1a692492190773c5310bc7877cb590c04c2f05be',
    ),
    'symfony/dependency-injection' => 
    array (
      'pretty_version' => 'v4.4.44',
      'version' => '4.4.44.0',
      'aliases' => 
      array (
      ),
      'reference' => '25502a57182ba1e15da0afd64c975cae4d0a1471',
    ),
    'symfony/deprecation-contracts' => 
    array (
      'pretty_version' => 'v2.5.2',
      'version' => '2.5.2.0',
      'aliases' => 
      array (
      ),
      'reference' => 'e8b495ea28c1d97b5e0c121748d6f9b53d075c66',
    ),
    'symfony/error-handler' => 
    array (
      'pretty_version' => 'v4.4.44',
      'version' => '4.4.44.0',
      'aliases' => 
      array (
      ),
      'reference' => 'be731658121ef2d8be88f3a1ec938148a9237291',
    ),
    'symfony/event-dispatcher' => 
    array (
      'pretty_version' => 'v4.4.44',
      'version' => '4.4.44.0',
      'aliases' => 
      array (
      ),
      'reference' => '1e866e9e5c1b22168e0ce5f0b467f19bba61266a',
    ),
    'symfony/event-dispatcher-contracts' => 
    array (
      'pretty_version' => 'v1.1.13',
      'version' => '1.1.13.0',
      'aliases' => 
      array (
      ),
      'reference' => '1d5cd762abaa6b2a4169d3e77610193a7157129e',
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
      'pretty_version' => 'v2.5.2',
      'version' => '2.5.2.0',
      'aliases' => 
      array (
      ),
      'reference' => 'ba6a9f0e8f3edd190520ee3b9a958596b6ca2e70',
    ),
    'symfony/http-foundation' => 
    array (
      'pretty_version' => 'v4.4.46',
      'version' => '4.4.46.0',
      'aliases' => 
      array (
      ),
      'reference' => '7acdc97f28a48b96def93af1efd77cfc5e8776dd',
    ),
    'symfony/http-kernel' => 
    array (
      'pretty_version' => 'v4.4.46',
      'version' => '4.4.46.0',
      'aliases' => 
      array (
      ),
      'reference' => 'fb72bc54f300151fadef84fce79764138b1ef943',
    ),
    'symfony/mime' => 
    array (
      'pretty_version' => 'v5.4.13',
      'version' => '5.4.13.0',
      'aliases' => 
      array (
      ),
      'reference' => 'bb2ccf759e2b967dcd11bdee5bdf30dddd2290bd',
    ),
    'symfony/polyfill-ctype' => 
    array (
      'pretty_version' => 'v1.25.0',
      'version' => '1.25.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '30885182c981ab175d4d034db0f6f469898070ab',
    ),
    'symfony/polyfill-iconv' => 
    array (
      'pretty_version' => 'v1.25.0',
      'version' => '1.25.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'f1aed619e28cb077fc83fac8c4c0383578356e40',
    ),
    'symfony/polyfill-intl-idn' => 
    array (
      'pretty_version' => 'v1.25.0',
      'version' => '1.25.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '749045c69efb97c70d25d7463abba812e91f3a44',
    ),
    'symfony/polyfill-intl-normalizer' => 
    array (
      'pretty_version' => 'v1.25.0',
      'version' => '1.25.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '8590a5f561694770bdcd3f9b5c69dde6945028e8',
    ),
    'symfony/polyfill-mbstring' => 
    array (
      'pretty_version' => 'v1.25.0',
      'version' => '1.25.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '0abb51d2f102e00a4eefcf46ba7fec406d245825',
    ),
    'symfony/polyfill-php72' => 
    array (
      'pretty_version' => 'v1.26.0',
      'version' => '1.26.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'bf44a9fd41feaac72b074de600314a93e2ae78e2',
    ),
    'symfony/polyfill-php73' => 
    array (
      'pretty_version' => 'v1.26.0',
      'version' => '1.26.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'e440d35fa0286f77fb45b79a03fedbeda9307e85',
    ),
    'symfony/polyfill-php80' => 
    array (
      'pretty_version' => 'v1.25.0',
      'version' => '1.25.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '4407588e0d3f1f52efb65fbe92babe41f37fe50c',
    ),
    'symfony/process' => 
    array (
      'pretty_version' => 'v4.4.44',
      'version' => '4.4.44.0',
      'aliases' => 
      array (
      ),
      'reference' => '5cee9cdc4f7805e2699d9fd66991a0e6df8252a2',
    ),
    'symfony/psr-http-message-bridge' => 
    array (
      'pretty_version' => 'v2.1.3',
      'version' => '2.1.3.0',
      'aliases' => 
      array (
      ),
      'reference' => 'd444f85dddf65c7e57c58d8e5b3a4dbb593b1840',
    ),
    'symfony/routing' => 
    array (
      'pretty_version' => 'v4.4.44',
      'version' => '4.4.44.0',
      'aliases' => 
      array (
      ),
      'reference' => 'f7751fd8b60a07f3f349947a309b5bdfce22d6ae',
    ),
    'symfony/serializer' => 
    array (
      'pretty_version' => 'v4.4.45',
      'version' => '4.4.45.0',
      'aliases' => 
      array (
      ),
      'reference' => 'd19621a350491f76e2faed2afb982e0706f63252',
    ),
    'symfony/service-contracts' => 
    array (
      'pretty_version' => 'v2.5.2',
      'version' => '2.5.2.0',
      'aliases' => 
      array (
      ),
      'reference' => '4b426aac47d6427cc1a1d0f7e2ac724627f5966c',
    ),
    'symfony/service-implementation' => 
    array (
      'provided' => 
      array (
        0 => '1.0|2.0',
      ),
    ),
    'symfony/translation' => 
    array (
      'pretty_version' => 'v4.4.45',
      'version' => '4.4.45.0',
      'aliases' => 
      array (
      ),
      'reference' => '4e6b4c0dbeb04d6f004ed7f43eb0905ce8396def',
    ),
    'symfony/translation-contracts' => 
    array (
      'pretty_version' => 'v2.5.2',
      'version' => '2.5.2.0',
      'aliases' => 
      array (
      ),
      'reference' => '136b19dd05cdf0709db6537d058bcab6dd6e2dbe',
    ),
    'symfony/translation-implementation' => 
    array (
      'provided' => 
      array (
        0 => '1.0|2.0',
      ),
    ),
    'symfony/validator' => 
    array (
      'pretty_version' => 'v4.4.46',
      'version' => '4.4.46.0',
      'aliases' => 
      array (
      ),
      'reference' => '51d06a00a7a8e9c45b91735932040b9f1df2c994',
    ),
    'symfony/var-dumper' => 
    array (
      'pretty_version' => 'v5.4.13',
      'version' => '5.4.13.0',
      'aliases' => 
      array (
      ),
      'reference' => '2bf2ccab581bec363191672f0df40e0c85569e1c',
    ),
    'symfony/yaml' => 
    array (
      'pretty_version' => 'v4.4.45',
      'version' => '4.4.45.0',
      'aliases' => 
      array (
      ),
      'reference' => 'aeccc4dc52a9e634f1d1eebeb21eacfdcff1053d',
    ),
    'twig/twig' => 
    array (
      'pretty_version' => 'v2.15.3',
      'version' => '2.15.3.0',
      'aliases' => 
      array (
      ),
      'reference' => 'ab402673db8746cb3a4c46f3869d6253699f614a',
    ),
    'typo3/phar-stream-wrapper' => 
    array (
      'pretty_version' => 'v3.1.7',
      'version' => '3.1.7.0',
      'aliases' => 
      array (
      ),
      'reference' => '5cc2f04a4e2f5c7e9cc02a3bdf80fae0f3e11a8c',
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
