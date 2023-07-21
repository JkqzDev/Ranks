<?php

declare(strict_types=1);

namespace juqn\ranks\session;

use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;

final class SessionManager {
    use SingletonTrait {
        setInstance as protected;
        reset as protected;
    }

    /** @var Session[] */
    private array $sessions = [];

    public function getSession(Player $player): ?Session {
        return $this->sessions[$player->getXuid()] ?? null;
    }

    public function createSession(Player $player): void {
        $this->sessions[$player->getXuid()] = new Session($player);
    }

    public function removeSession(Player $player): void {
        if (!isset($this->sessions[$player->getXuid()])) {
            return;
        }
        $session = $this->sessions[$player->getXuid()];
        $session->destroy();

        unset($this->sessions[$player->getXuid()]);
    }
}