<?php

declare(strict_types=1);

namespace juqn\ranks\handler;

use juqn\ranks\session\SessionManager;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;

final class SessionHandler implements Listener {

    public function handleChat(PlayerChatEvent $event): void {
        $player = $event->getPlayer();
        $session = SessionManager::getInstance()->getSession($player);

        if ($session === null) {
            return;
        }

    }

    public function handleJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $session = SessionManager::getInstance()->getSession($player);

        if ($session === null) {
            return;
        }
        $session->join();
    }

    public function handleLogin(PlayerLoginEvent $event): void {
        $player = $event->getPlayer();
        SessionManager::getInstance()->createSession($player);
    }

    public function handlQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();
        SessionManager::getInstance()->removeSession($player);
    }
}