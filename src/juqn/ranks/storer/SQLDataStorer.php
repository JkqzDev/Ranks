<?php

declare(strict_types=1);

namespace juqn\ranks\storer;

use juqn\ranks\Ranks;
use pocketmine\utils\SingletonTrait;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;

final class SQLDataStorer {
    use SingletonTrait;

    private const CREATE_PLAYERS_TABLE = 'tables.players';

    private DataConnector $connector;

    public function load(): void {
        $config = Ranks::getInstance()->getConfig();
        $this->connector = libasynql::create(Ranks::getInstance(), $config->get('database', []), [
            'mysql' => 'database/mysql.sql'
        ]);
        $this->connector->executeGeneric(self::CREATE_PLAYERS_TABLE);
        $this->connector->waitAll();
    }
    public function save(): void {
        $this->connector->waitAll();
        $this->connector->close();
    }
}