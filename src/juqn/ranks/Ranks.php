<?php

declare(strict_types=1);

namespace juqn\ranks;

use juqn\ranks\command\RankCommand;
use juqn\ranks\handler\SessionHandler;
use juqn\ranks\rank\RankManager;
use juqn\ranks\storer\SQLDataStorer;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;

final class Ranks extends PluginBase {
    use SingletonTrait {
        setInstance as protected;
        reset as protected;
    }

    protected function onLoad(): void {
        self::setInstance($this);

        $this->saveDefaultConfig();
        $this->saveResource('ranks.yml');
    }

    protected function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents(new SessionHandler(), $this);
        $this->getServer()->getCommandMap()->register($this->getName(), new RankCommand());

        RankManager::getInstance()->load();
        SQLDataStorer::getInstance()->load();
    }

    protected function onDisable(): void {
        SQLDataStorer::getInstance()->save();
    }
}