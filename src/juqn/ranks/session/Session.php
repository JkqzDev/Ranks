<?php

declare(strict_types=1);

namespace juqn\ranks\session;

use juqn\ranks\rank\Rank;
use juqn\ranks\rank\RankManager;
use juqn\ranks\Ranks;
use juqn\ranks\storer\SQLDataStorer;
use pocketmine\permission\PermissionAttachment;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use RuntimeException;

final class Session {

    private Rank $primaryRank;
    private ?Rank $secondaryRank = null;

    /** @var string[] */
    private array $permissions = [];
    /** @var PermissionAttachment[] */
    private array $attachments = [];

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

        $this->updatePermissions();
    }

    public function setSecondaryRank(?Rank $secondaryRank = null): void {
        $this->secondaryRank = $secondaryRank;
        $this->changed = true;

        $this->updatePermissions();
    }

    public function addPermission(string $permission): void {
        $this->permissions[] = $permission;
        $this->changed = true;

        $this->updatePermissions();
    }

    public function removePermission(string $permission): void {
        if (!$this->existsPermission($permission)) {
            return;
        }
        unset($this->permissions[array_search($permission, $this->permissions)]);
        $this->changed = true;

        $this->updatePermissions();
    }

    public function join(): void {
        $config = Ranks::getInstance()->getConfig();
        $primaryRank = $this->primaryRank;
        $secondaryRank = $this->secondaryRank;

        if ($config->get('apply-nametag-format', false)) {
            $nametagFormat = str_replace(['{primaryRank}', '{secondaryRank}', '{player}'], [$primaryRank->getColor() . ' ' . $primaryRank->getName() . ' ', $secondaryRank !== null ? $secondaryRank->getColor() . ' ' . $secondaryRank->getName() . ' ' : '', $this->player->getName()], $config->get('nametag-format', ''));
            $this->player->setNameTag(TextFormat::colorize($nametagFormat));
        }
        $this->updatePermissions();
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

    private function updatePermissions(): void {
        $primaryRank = $this->primaryRank;
        $secondaryRank = $this->secondaryRank;
        $permissions = $this->permissions;
        $permissions = array_merge($primaryRank->getPermissions(), $secondaryRank?->getPermissions() ?? [], $permissions);

        if (count($this->attachments) !== 0) {
            foreach ($this->attachments as $attachment) {
                $this->player->removeAttachment($attachment);
            }
            $this->attachments = [];
        }

        foreach ($permissions as $permission) {
            $this->attachments[] = $this->player->addAttachment(Ranks::getInstance(), $permission, true);
        }
    }
}