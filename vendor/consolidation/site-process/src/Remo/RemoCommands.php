<?php

namespace Consolidation\SiteProcess\Remo;

use Consolidation\SiteProcess\SiteProcess;

use Consolidation\SiteAlias\SiteAliasManagerAwareInterface;
use Consolidation\SiteAlias\SiteAliasManagerAwareTrait;
use Consolidation\SiteAlias\SiteAliasManager;

class RemoCommands extends \Robo\Tasks
{
    use SiteAliasManagerAwareTrait;

    /**
     * Run a command identified by a site alias
     *
     * @command run
     */
    public function run($aliasName, array $args, $options = ['foo' => 'bar'])
    {
        // The site alias manager has not been added to the DI container yet.
        if (!$this->hasSiteAliasManager()) {
            // TODO: Provide some way to initialize the alias file loaders, so
            // that there is some way to specify where alias files may be
            // loaded from.
            $manager = new SiteAliasManager();
            // $manager->setRoot($root);
            $this->setSiteAliasManager($manager);
        }

        // In theory this might do something once we get an alias manager.
        $siteAlias = $this->siteAliasManager()->get($aliasName);
        if (!$siteAlias) {
            throw new \Exception("Alias name $aliasName not found.");
        }
        $process = new SiteProcess($siteAlias, $args);
        $process->setRealtimeOutput($this->io());
        $process->setTty($this->input()->isInteractive());
        $process->mustRun($process->showRealtime());
    }
}
