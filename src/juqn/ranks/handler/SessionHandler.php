<?php

declare(strict_types=1);

namespace juqn\ranks\handler;

use juqn\ranks\Ranks;
use juqn\ranks\session\SessionManager;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\chat\LegacyRawChatFormatter;
use pocketmine\utils\TextFormat;

final class SessionHandler implements Listener {

    public function handleChat(PlayerChatEvent $event): void {
        $player = $event->getPlayer();
        $session = SessionManager::getInstance()->getSession($player);

        if ($session === null) {
            return;
        }
        $config = Ranks::getInstance()->getConfig();

        if ($config->get('apply-chat-format')) {
            $primaryRank = $session->getPrimaryRank();
            $secondaryRank = $session->getSecondaryRank();
            $chatFormat = str_replace(['{primaryRank}', '{secondaryRank}', '{player}', '{message}'], [$primaryRank->getName() !== '' ? $primaryRank->getColor() . $primaryRank->getName() . ' ' : '', $secondaryRank !== null ? $secondaryRank->getColor() . $secondaryRank->getName() . ' ' : '', $player->getName(), $event->getMessage()], $config->get('chat-format', ''));

            $event->setFormatter(new LegacyRawChatFormatter(TextFormat::colorize($chatFormat)));
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

    public function handleQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();
        SessionManager::getInstance()->removeSession($player);
    }
}