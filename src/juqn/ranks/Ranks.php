<?php

declare(strict_types=1);

namespace juqn\ranks;

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
    }

    protected function onEnable(): void {
        SQLDataStorer::getInstance()->load();
    }

    protected function onDisable(): void {
        SQLDataStorer::getInstance()->save();
    }
}