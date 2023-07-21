<?php

declare(strict_types=1);

namespace juqn\ranks\session;

use juqn\ranks\rank\Rank;
use juqn\ranks\rank\RankManager;
use juqn\ranks\Ranks;
use juqn\ranks\storer\SQLDataStorer;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use RuntimeException;

final class Session {

    private Rank $primaryRank;
    private ?Rank $secondaryRank = null;

    /** @var string[] */
    private array $permissions = [];

    private bool $changed = false;

    public function __construct(
        private readonly Player $player
    ) {
        $config = Ranks::getInstance()->getConfig();
        $defaultRank = RankManager::getInstance()->getRank($config->get('default-rank')) ?? throw new RuntimeException('Invalid default rank');

        SQLDataStorer::getInstance()->getConnector()->executeSelect(
            SQLDataStorer::GET_PLAYER,
            [
                'playerXuid' => $this->player->getXuid()
            ],
            function (array $rows, array $columns) use ($defaultRank): void {
                if (count($rows) === 0) {
                    $this->primaryRank = $defaultRank;

                    SQLDataStorer::getInstance()->getConnector()->executeInsert(
                        SQLDataStorer::INSERT_PLAYER,
                        [
                            'playerXuid' => $this->player->getXuid(),
                            'playerName' => $this->player->getName(),
                            'primaryRank' => $defaultRank->getEnumName()
                        ]
                    );
                } else {
                    $data = $rows[0];

                    $primaryRank = RankManager::getInstance()->getRank($data['primaryRank']) ?? $defaultRank;
                    $permissions = explode(',', $data['permissions']);

                    if ($data['secondaryRank'] !== '') {
                        $this->secondaryRank = RankManager::getInstance()->getRank($data['secondaryRank']);
                    }
                    $this->primaryRank = $primaryRank;
                    $this->permissions = $permissions;
                }
            }
        );
    }

    public function getPrimaryRank(): Rank {
        return $this->primaryRank;
    }

    public function getSecondaryRank(): ?Rank {
        return $this->secondaryRank;
    }

    public function getPermissions(): array {
        return $this->permissions;
    }

    public function existsPermission(string $permission): bool {
        return in_array($permission, $this->permissions);
    }

    public function setPrimaryRank(Rank $primaryRank): void {
        $this->primaryRank = $primaryRank;
        $this->changed = true;
    }

    public function setSecondaryRank(?Rank $secondaryRank): void {
        $this->secondaryRank = $secondaryRank;
        $this->changed = true;
    }

    public function addPermission(string $permission): void {
        $this->permissions[] = $permission;
        $this->changed = true;
    }

    public function removePermission(string $permission): void {
        if (!$this->existsPermission($permission)) {
            return;
        }
        unset($this->permissions[array_search($permission, $this->permissions)]);
        $this->changed = true;
    }

    public function join(): void {
        $config = Ranks::getInstance()->getConfig();
        $primaryRank = $this->primaryRank;
        $secondaryRank = $this->secondaryRank;
        $permissions = $this->permissions;

        if ($config->get('apply-nametag-format', false)) {
            $nametagFormat = str_replace(['{primaryRank}', '{secondaryRank}', '{player}'], [$primaryRank->getColor() . ' ' . $primaryRank->getName() . ' ', $secondaryRank !== null ? $secondaryRank->getColor() . ' ' . $secondaryRank->getName() . ' ' : '', $this->player->getName()], $config->get('nametag-format', ''));
            $this->player->setNameTag(TextFormat::colorize($nametagFormat));
        }
        $permissions = array_merge($primaryRank->getPermissions(), $secondaryRank?->getPermissions() ?? [], $permissions);

        foreach ($permissions as $permission) {
            $this->player->addAttachment(Ranks::getInstance(), $permission, true);
        }
    }

    public function destroy(): void {
        if ($this->changed) {
            SQLDataStorer::getInstance()->getConnector()->executeChange(
                SQLDataStorer::UPDATE_PLAYER,
                [
                    'playerXuid' => $this->player->getXuid(),
                    'playerName' => $this->player->getName(),
                    'primaryRank' => $this->primaryRank->getEnumName(),
                    'secondaryRank' => $this->secondaryRank?->getEnumName() ?? '',
                    'permissions' => implode(',', $this->permissions)
                ]
            );
        }
    }
}