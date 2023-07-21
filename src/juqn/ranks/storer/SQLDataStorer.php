<?php

declare(strict_types=1);

namespace juqn\ranks\storer;

use juqn\ranks\Ranks;
use juqn\ranks\session\SessionManager;
use pocketmine\utils\SingletonTrait;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;

final class SQLDataStorer {
    use SingletonTrait {
        setInstance as protected;
        reset as protected;
    }

    private const CREATE_PLAYERS_TABLE = 'tables.players';

    public const INSERT_PLAYER = 'insert.player';
    public const GET_PLAYER = 'get.player.xuid';
    public const GET_PLAYER_BY_NAME = 'get.player.name';
    public const UPDATE_PLAYER = 'update.player';

    private DataConnector $connector;

    public function getConnector(): DataConnector {
        return $this->connector;
    }

    public function load(): void {
        $config = Ranks::getInstance()->getConfig();
        $this->connector = libasynql::create(Ranks::getInstance(), $config->get('database', []), [
            'mysql' => 'database/mysql.sql'
        ]);
        $this->connector->executeGeneric(self::CREATE_PLAYERS_TABLE);
        $this->connector->waitAll();
    }

    public function save(): void {
        foreach (SessionManager::getInstance()->getSessions() as $session) {
            $session->destroy();
        }
        $this->connector->waitAll();
        $this->connector->close();
    }
}