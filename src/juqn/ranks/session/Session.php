<?php

declare(strict_types=1);

namespace juqn\ranks\session;

use juqn\ranks\rank\Rank;
use juqn\ranks\Ranks;
use pocketmine\player\Player;

final class Session {

    private Rank $primaryRank;
    private ?Rank $secondaryRank = null;

    /** @var string[] */
    private array $permissions = [];

    public function __construct(
        private readonly Player $player
    ) {}

    public function getPrimaryRank(): Rank {
        return $this->primaryRank;
    }

    public function getSecondaryRank(): ?Rank {
        return $this->secondaryRank;
    }

    public function join(): void {
        $permissions = array_merge($this->primaryRank->getPermissions(), $this->secondaryRank?->getPermissions() ?? [], $this->permissions);

        foreach ($permissions as $permission) {
            $this->player->addAttachment(Ranks::getInstance(), $permission, true);
        }
    }
}