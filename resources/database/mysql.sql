-- #! mysql
-- #{ tables
-- #    { players
CREATE TABLE IF NOT EXISTS players (
    id INT UNSIGNED AUTO_INCREMENT,
    playerXuid VARCHAR(100) NOT NULL,
    playerName VARCHAR(100) NOT NULL,
    primaryRank VARCHAR(100) NOT NULL,
    secondaryRank VARCHAR(100) DEFAULT '',
    permissions VARCHAR(10000) DEFAULT '',
    PRIMARY KEY (id),
    UNIQUE KEY `playerXuid` (playerXuid)
);
-- #    }
-- #}
-- #{ insert.player
-- #    :playerXuid string
-- #    :playerName string
-- #    :primaryRank string
INSERT INTO players (playerXuid, playerName, primaryRank)
VALUE (:playerXuid, :playerName, :primaryRank);
-- #}
-- #{ get
-- #    { player.xuid
-- #        :playerXuid string
SELECT * FROM players
WHERE playerXuid = :playerXuid;
-- #    }
-- #    { player.name
-- #        :playerName string
SELECT * FROM players
WHERE playerName = :playerName;
-- #    }
-- #}
-- #{ update
-- #    { player.xuid
-- #        :playerXuid string
-- #        :playerName string
-- #        :primaryRank string
-- #        :secondaryRank string
-- #        :permissions string
UPDATE players SET playerName = :playerName, primaryRank = :primaryRank, secondaryRank = :secondaryRank, permissions = :permissions
WHERE playerXuid = :playerXuid;
-- #    }
-- #    { player.name
-- #        :playerName string
-- #        :primaryRank string
-- #        :secondaryRank string
UPDATE players SET primaryRank = :primaryRank, secondaryRank = :secondaryRank
WHERE playerName = :playerName;
-- #    }
-- #}