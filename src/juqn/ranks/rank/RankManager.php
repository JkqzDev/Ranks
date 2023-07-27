<?php

declare(strict_types=1);

namespace juqn\ranks\rank;

use juqn\ranks\Ranks;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use RuntimeException;

final class RankManager {
    use SingletonTrait;

    /** @var Rank[] */
    private array $ranks = [];

    public function getRanks(): array {
        return $this->ranks;
    }

    public function getRank(string $rankName): ?Rank {
        return $this->ranks[$rankName] ?? null;
    }

    public function load(): void {
        $config = Ranks::getInstance()->getConfig();
        $ranks = new Config(Ranks::getInstance()->getDataFolder() . 'ranks.yml', Config::YAML);

        $totalRanks = array_merge($ranks->get('primary-ranks', []), $ranks->get('secondary-ranks', []));
        $id = 0;

        foreach ($totalRanks as $rankName => $rankData) {
            $this->ranks[$rankName] = new Rank($id, $rankName, $rankData['name'], $rankData['color'], $rankData['permissions'] ?? [], isset($ranks->get('primary-ranks', [])[$rankName]));
            $id++;
        }

        if (!isset($this->ranks[$config->get('default-rank', 'user')])) {
            throw new RuntimeException('Invalid default rank');
        }
    }
}