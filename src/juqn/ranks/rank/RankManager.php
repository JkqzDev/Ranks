<?php

declare(strict_types=1);

namespace juqn\ranks\rank;

use pocketmine\utils\SingletonTrait;

final class RankManager {
    use SingletonTrait;

    /** @var Rank[] */
    private array $ranks = [];
}