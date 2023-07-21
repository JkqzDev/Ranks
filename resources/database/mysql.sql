-- #! mysql
-- #{ tables
-- #    { players
CREATE TABLE IF NOT EXISTS players (
    id INT UNSIGNED AUTO_INCREMENT,
    xuid VARCHAR(100) PRIMARY KEY,
    playerName VARCHAR(100) NOT NULL,
    primaryRank VARCHAR(100) NOT NULL,
    secondaryRank VARCHAR(100) DEFAULT '',
    permissions VARCHAR(10000) DEFAULT '',
    PRIMARY KEY (id),
    UNIQUE KEY `xuid` (xuid)
);
-- #    }
-- #}
-- #{ insert.player
-- #    :xuid string
-- #    :playerName string
-- #    :primaryRank string
INSERT INTO players (xuid, playerName, primaryRank)
VALUE (:xuid, :playerName, :primaryRank);
-- #}
-- #{ get.player
-- #    :xuid string
SELECT * FROM players
WHERE xuid = :xuid;
-- #}
-- #{ update.player
-- #    :xuid string
-- #    :playerName string
-- #    :primaryRank string
-- #    :secondaryRank string
-- #    :permissions string
UPDATE players SET playerName = :playerName, primaryRank = :primaryRank, secondaryRank = :secondaryRank, permissions = :permissions
WHERE xuid = :xuid;
-- #}