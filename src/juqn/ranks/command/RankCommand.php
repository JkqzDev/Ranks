<?php

declare(strict_types=1);

namespace juqn\ranks\command;

use juqn\ranks\rank\RankManager;
use juqn\ranks\Ranks;
use juqn\ranks\session\SessionManager;
use juqn\ranks\storer\SQLDataStorer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use RuntimeException;

final class RankCommand extends Command {

    public function __construct() {
        parent::__construct('rank', 'Use command to ranks');
        $this->setPermission('rank.command');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if (!$this->testPermission($sender)) {
            return;
        }

        if (count($args) < 1) {
            $sender->sendMessage(TextFormat::colorize('&cUse /rank help'));
            return;
        }

        switch (strtolower($args[0])) {
            case 'info':
                if (count($args) < 2) {
                    $sender->sendMessage(TextFormat::colorize('&cUse /rank info [player]'));
                    return;

                }
                $player = $sender->getServer()->getPlayerByPrefix($args[1]);

                if ($player !== null) {
                    $session = SessionManager::getInstance()->getSession($player);

                    if ($session === null) {
                        return;
                    }
                    $primaryRank = $session->getPrimaryRank();
                    $secondaryRank = $session->getSecondaryRank();
                    $permissions = $session->getPermissions();

                    $sender->sendMessage(TextFormat::colorize(
                        '&5' . $player->getName() . ' Information' . PHP_EOL .
                        '&5Primary rank: ' . $primaryRank->getColor() . $primaryRank->getName() . PHP_EOL .
                        '&5Secondary rank: ' . ($secondaryRank !== null ? $secondaryRank->getColor() . $secondaryRank->getName() : '&cNone') . PHP_EOL .
                        '&5Permissions: &f' . (count($permissions) !== 0 ? '&cNone' : '&f' . implode(',', $permissions))
                    ));
                    return;
                }
                SQLDataStorer::getInstance()->getConnector()->executeSelect(
                    SQLDataStorer::GET_PLAYER_BY_NAME,
                    [
                        'playerName' => $args[1]
                    ],
                    function (array $rows, array $columns) use ($sender): void {
                        if (count($rows) === 0) {
                            $sender->sendMessage(TextFormat::colorize('&cPlayer no exists.'));
                            return;
                        }
                        $config = Ranks::getInstance()->getConfig();
                        $defaultRank = RankManager::getInstance()->getRank($config->get('default-rank')) ?? throw new RuntimeException('Invalid default rank');
                        $data = $rows[0];

                        $primaryRank = RankManager::getInstance()->getRank($data['primaryRank']) ?? $defaultRank;
                        $secondaryRank = RankManager::getInstance()->getRank($data['secondaryRank'] ?? '000nu');
                        $permissions = explode(',', $data['permissions']);

                        $sender->sendMessage(TextFormat::colorize(
                            '&5' . $data['playerName'] . ' Information' . PHP_EOL .
                            '&5Primary rank: ' . $primaryRank->getColor() . $primaryRank->getName() . PHP_EOL .
                            '&5Secondary rank: ' . ($secondaryRank !== null ? $secondaryRank->getColor() . $secondaryRank->getName() : '&cNone') . PHP_EOL .
                            '&5Permissions: &f' . (count($permissions) !== 0 ? '&cNone' : '&f' . implode(',', $permissions))
                        ));
                    }
                );
                break;
            default:
                $sender->sendMessage(TextFormat::colorize('&cInvalid rank subcommand. Use /rank help'));
                break;
        }
    }
}