<?php

namespace Consolidation\SiteAlias\Cli;

use Consolidation\SiteAlias\SiteAliasFileLoader;
use Consolidation\SiteAlias\SiteAliasManager;
use Consolidation\SiteAlias\Util\YamlDataFileLoader;
use Consolidation\SiteAlias\SiteSpecParser;
use Consolidation\SiteAlias\SiteAliasName;

class SiteAliasCommands extends \Robo\Tasks
{
    protected $aliasLoader;

    /**
     * List available site aliases.
     *
     * @command site:list
     * @format yaml
     * @return array
     */
    public function siteList(array $varArgs)
    {
        $this->aliasLoader = new SiteAliasFileLoader();
        $ymlLoader = new YamlDataFileLoader();
        $this->aliasLoader->addLoader('yml', $ymlLoader);
        $aliasName = $this->getLocationsAndAliasName($varArgs, $this->aliasLoader);

        $this->manager = new SiteAliasManager($this->aliasLoader);

        return $this->renderAliases($this->manager->getMultiple($aliasName));
    }

    /**
     * Load available site aliases.
     *
     * @command site:load
     * @format yaml
     * @return array
     */
    public function siteLoad(array $dirs)
    {
        $this->aliasLoader = new SiteAliasFileLoader();
        $ymlLoader = new YamlDataFileLoader();
        $this->aliasLoader->addLoader('yml', $ymlLoader);

        foreach ($dirs as $dir) {
            $this->io()->note("Add search location: $dir");
            $this->aliasLoader->addSearchLocation($dir);
        }

        $all = $this->aliasLoader->loadAll();

        return $this->renderAliases($all);
    }

    protected function getLocationsAndAliasName($varArgs)
    {
        $aliasName = '';
        foreach ($varArgs as $arg) {
            if (SiteAliasName::isAliasName($arg)) {
                $this->io()->note("Alias parameter: '$arg'");
                $aliasName = $arg;
            } else {
                $this->io()->note("Add search location: $arg");
                $this->aliasLoader->addSearchLocation($arg);
            }
        }
        return $aliasName;
    }

    protected function renderAliases($all)
    {
        if (empty($all)) {
            throw new \Exception("No aliases found");
        }

        $result = [];
        foreach ($all as $name => $alias) {
            $result[$name] = $alias->export();
        }

        return $result;
    }

    /**
     * Show contents of a single site alias.
     *
     * @command site:get
     * @format yaml
     * @return array
     */
    public function siteGet(array $varArgs)
    {
        $this->aliasLoader = new SiteAliasFileLoader();
        $ymlLoader = new YamlDataFileLoader();
        $this->aliasLoader->addLoader('yml', $ymlLoader);
        $aliasName = $this->getLocationsAndAliasName($varArgs, $this->aliasLoader);

        $manager = new SiteAliasManager($this->aliasLoader);
        $result = $manager->get($aliasName);
        if (!$result) {
            throw new \Exception("No alias found");
        }

        return $result->export();
    }

    /**
     * Access a value from a single alias.
     *
     * @command site:value
     * @format yaml
     * @return string
     */
    public function siteValue(array $varArgs)
    {
        $this->aliasLoader = new SiteAliasFileLoader();
        $ymlLoader = new YamlDataFileLoader();
        $this->aliasLoader->addLoader('yml', $ymlLoader);
        $key = array_pop($varArgs);
        $aliasName = $this->getLocationsAndAliasName($varArgs, $this->aliasLoader);

        $manager = new SiteAliasManager($this->aliasLoader);
        $result = $manager->get($aliasName);
        if (!$result) {
            throw new \Exception("No alias found");
        }

        return $result->get($key);
    }

    /**
     * Parse a site specification.
     *
     * @command site-spec:parse
     * @format yaml
     * @return array
     */
    public function parse($spec, $options = ['root' => ''])
    {
        $parser = new SiteSpecParser();
        return $parser->parse($spec, $options['root']);
    }
}
