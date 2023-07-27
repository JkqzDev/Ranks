<?php

declare(strict_types=1);

namespace juqn\ranks\command;

use juqn\ranks\rank\Rank;
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
            case 'help':
                $messages = [
                    '&l&dRank commands&r',
                    '&d/rank info <player> &7- Use command to see player information',
                    '&d/rank set <type> &7- Use command to set primary or secondary rank',
                    '&d/rank remove <player> &7- Use command to remove player\'s rank',
                    '&d/rank list &7- Use command to see rank list'
                ];
                $sender->sendMessage(TextFormat::colorize(implode(PHP_EOL, $messages)));
                break;

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

            case 'set':
                if (count($args) < 2) {
                    $sender->sendMessage(TextFormat::colorize('&cUse /rank set [type]'));
                    return;
                }

                if (strtolower($args[1]) === 'primary') {
                    if (count($args) < 4) {
                        $sender->sendMessage(TextFormat::colorize('&cUse /rank set primary [player] [rank]'));
                        return;
                    }
                    $player = $sender->getServer()->getPlayerByPrefix($args[2]);
                    $rankName = $args[3];

                    $primaryRanks = array_filter(RankManager::getInstance()->getRanks(), fn(Rank $rank) => $rank->isPrimary());
                    $rank = $primaryRanks[$rankName] ?? null;

                    if ($rank === null) {
                        $sender->sendMessage(TextFormat::colorize('&cPrimary rank no exists.'));
                        return;
                    }

                    if ($player !== null) {
                        $session = SessionManager::getInstance()->getSession($player);

                        if ($session === null) {
                            $sender->sendMessage(TextFormat::colorize('&cPlayer not found.'));
                            return;
                        }
                        $session->setPrimaryRank($rank);
                        $player->sendMessage(TextFormat::colorize('&aYou have been received main rank ' . $rank->getColor() . $rank->getName()));
                        $sender->sendMessage(TextFormat::colorize('&aYou have been added main rank ' . $rank->getColor() . $rank->getName() . ' &r&ato ' . $player->getName()));
                        return;
                    }
                    SQLDataStorer::getInstance()->getConnector()->executeSelect(
                        SQLDataStorer::GET_PLAYER_BY_NAME,
                        [
                            'playerName' => $args[1]
                        ],
                        function (array $rows, array $columns) use ($sender, $rank): void {
                            if (count($rows) === 0) {
                                $sender->sendMessage(TextFormat::colorize('&cPlayer not found.'));
                                return;
                            }
                            $data = $rows[0];
                            $playerName = $data['playerName'];

                            SQLDataStorer::getInstance()->getConnector()->executeChange(
                                SQLDataStorer::UPDATE_PLAYER_BY_NAME,
                                [
                                    'playerName' => $playerName,
                                    'primaryRank' => $rank->getEnumName(),
                                    'secondaryRank' => $data['secondaryRank']
                                ],
                                fn(int $affectedRows) => $sender->sendMessage(TextFormat::colorize('&aYou have been added main rank ' . $rank->getColor() . $rank->getName() , ' &r&ato' . $playerName))
                            );
                        }
                    );
                } elseif (strtolower($args[1]) === 'secondary') {
                    if (count($args) < 4) {
                        $sender->sendMessage(TextFormat::colorize('&cUse /rank set secondary [player] [rank]'));
                        return;
                    }
                    $player = $sender->getServer()->getPlayerByPrefix($args[2]);
                    $rankName = $args[3];

                    $secondaryRanks = array_filter(RankManager::getInstance()->getRanks(), fn(Rank $rank) => !$rank->isPrimary());
                    $rank = $secondaryRanks[$rankName] ?? null;

                    if ($rank === null) {
                        $sender->sendMessage(TextFormat::colorize('&cSecondary rank no exists.'));
                        return;
                    }

                    if ($player !== null) {
                        $session = SessionManager::getInstance()->getSession($player);

                        if ($session === null) {
                            $sender->sendMessage(TextFormat::colorize('&cPlayer not found.'));
                            return;
                        }
                        $session->setSecondaryRank($rank);
                        $player->sendMessage(TextFormat::colorize('&aYou have been received rank ' . $rank->getColor() . $rank->getName()));
                        $sender->sendMessage(TextFormat::colorize('&aYou have been added rank ' . $rank->getColor() . $rank->getName() . ' &r&ato ' . $player->getName()));
                        return;
                    }
                    SQLDataStorer::getInstance()->getConnector()->executeSelect(
                        SQLDataStorer::GET_PLAYER_BY_NAME,
                        [
                            'playerName' => $args[1]
                        ],
                        function (array $rows, array $columns) use ($sender, $rank): void {
                            if (count($rows) === 0) {
                                $sender->sendMessage(TextFormat::colorize('&cPlayer not found.'));
                                return;
                            }
                            $data = $rows[0];
                            $playerName = $data['playerName'];

                            SQLDataStorer::getInstance()->getConnector()->executeChange(
                                SQLDataStorer::UPDATE_PLAYER_BY_NAME,
                                [
                                    'playerName' => $playerName,
                                    'primaryRank' => $data['primaryRank'],
                                    'secondaryRank' => $rank->getEnumName()
                                ],
                                fn(int $affectedRows) => $sender->sendMessage(TextFormat::colorize('&aYou have been added rank ' . $rank->getColor() . $rank->getName() , ' &r&ato' . $playerName))
                            );
                        }
                    );
                } else {
                    $sender->sendMessage(TextFormat::colorize('&cType invalid.'));
                }
                break;

            case 'remove':
                if (count($args) < 2) {
                    $sender->sendMessage(TextFormat::colorize('&cUse /rank remove [player]'));
                    return;
                }
                $player = $sender->getServer()->getPlayerByPrefix($args[1]);

                if ($player !== null) {
                    $session = SessionManager::getInstance()->getSession($player);

                    if ($session === null) {
                        $sender->sendMessage(TextFormat::colorize('&cPlayer not found.'));
                        return;
                    }

                    if ($session->getSecondaryRank() === null) {
                        $sender->sendMessage(TextFormat::colorize('&cPlayer no has rank'));
                        return;
                    }
                    $session->setSecondaryRank();
                    $player->sendMessage(TextFormat::colorize('&cYou rank has been removed.'));
                    $sender->sendMessage(TextFormat::colorize('&cYou have been removed ' . $player->getName() . '\'s rank'));
                    return;
                }
                SQLDataStorer::getInstance()->getConnector()->executeSelect(
                    SQLDataStorer::GET_PLAYER_BY_NAME,
                    [
                        'playerName' => $args[1]
                    ],
                    function (array $rows, array $columns) use ($sender): void {
                        if (count($rows) === 0) {
                            $sender->sendMessage(TextFormat::colorize('&cPlayer not found.'));
                            return;
                        }
                        $data = $rows[0];

                        if ($data['secondaryRank'] === '') {
                            $sender->sendMessage(TextFormat::colorize('&cPlayer no has rank'));
                            return;
                        }
                        SQLDataStorer::getInstance()->getConnector()->executeChange(
                            SQLDataStorer::UPDATE_PLAYER_BY_NAME,
                            [
                                'playerName' => $data['playerName'],
                                'primaryRank' => $data['primaryRank'],
                                'secondaryRank' => ''
                            ]
                        );
                        $sender->sendMessage(TextFormat::colorize('&cYou have been removed ' . $data['playerName'] . '\'s rank'));
                    }
                );
                break;

            case 'list':
                $primaryRanks = array_filter(RankManager::getInstance()->getRanks(), fn(Rank $rank) => $rank->isPrimary());
                $secondaryRanks = array_filter(RankManager::getInstance()->getRanks(), fn(Rank $rank) => !$rank->isPrimary());

                $sender->sendMessage(TextFormat::colorize('&dPrimary ranks &7(' . count($primaryRanks) . ' total)'));
                $sender->sendMessage(TextFormat::colorize(implode(PHP_EOL, array_map(fn(Rank $rank) => '&7' . $rank->getEnumName() . ': ' . $rank->getColor() . $rank->getName(), $primaryRanks))));

                if (count($secondaryRanks) !== 0) {
                    $sender->sendMessage(PHP_EOL);
                    $sender->sendMessage(TextFormat::colorize('&dSecondary ranks &7(' . count($secondaryRanks) . ' total)'));
                    $sender->sendMessage(TextFormat::colorize(implode(PHP_EOL, array_map(fn(Rank $rank) => '&7' . $rank->getEnumName() . ': ' . $rank->getColor() . $rank->getName(), $secondaryRanks))));
                }
                break;

            default:
                $sender->sendMessage(TextFormat::colorize('&cInvalid rank subcommand. Use /rank help'));
                break;
        }
    }
}