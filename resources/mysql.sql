-- #!mysql

-- #{ table
    -- #{ groups
        CREATE TABLE IF NOT EXISTS `groups` (
            name VARCHAR(32) PRIMARY KEY,
            display_name VARCHAR(100),
            name_tag VARCHAR(100),
            chat_format VARCHAR(100),
            color_code VARCHAR(6),
            permissions MEDIUMTEXT
        );
    -- #}
    -- #{ players
        CREATE TABLE IF NOT EXISTS players (
            username VARCHAR(16) PRIMARY KEY,
            `group` VARCHAR(32),
            expire TIMESTAMP NULL DEFAULT NULL,
            `groups` MEDIUMTEXT,
            permissions MEDIUMTEXT
        );
    -- #}
-- #}

-- #{ groups
    -- #{ add
        -- # :name string
        -- # :display_name string
        -- # :name_tag string
        -- # :chat_format string
        -- # :color_code string
        -- # :permissions string
        INSERT INTO `groups` (name, display_name, name_tag, chat_format, color_code, permissions)
        VALUES (:name, :display_name, :name_tag, :chat_format, :color_code, :permissions);
    -- #}
    -- #{ remove
        -- # :name string
        DELETE FROM `groups` WHERE name = :name;
    -- #}
    -- #{ edit
        -- # :name string
        -- # :display_name string
        -- # :name_tag string
        -- # :chat_format string
        -- # :color_code string
        -- # :permissions string
        UPDATE `groups` SET display_name = :display_name, name_tag = :name_tag, chat_format = :chat_format, color_code = :color_code, permissions = :permissions
        WHERE name = :name;
    -- #}
    -- #{ check
        -- # :name string
        SELECT EXISTS(SELECT * from groups WHERE name = :name);
    -- #}
    -- #{ get
        -- # :name string
        SELECT * FROM `groups` WHERE name = :name;
    -- #}
    -- #{ getAll
        SELECT * FROM `groups`;
    -- #}
-- #}

-- #{ player
    -- #{ create
        -- # :username string
        -- # :group string
        -- # :expire string null
        -- # :groups string
        -- # :permissions string
        INSERT INTO players(username, `group`, expire, `groups`, permissions)
        VALUES (:username, :group, :expire, :groups, :permissions);
    -- #}
    -- #{ setGroup
        -- # :username string
        -- # :group string
        -- # :expire string null
        UPDATE players SET `group` = :group, expire = :expire WHERE username = :username;
    -- #}
    -- #{ updateGroups
        -- # :username string
        -- # :groups string
        UPDATE players SET `groups` = :groups WHERE username = :username;
    -- #}
    -- #{ updatePermissions
        -- # :username string
        -- # :permissions string
        UPDATE players SET permissions = :permissions WHERE username = :username;
    -- #}
    -- #{ getGroup
        -- # :username string
        SELECT players.group, players.expire
        FROM players WHERE username = :username;
    -- #}
    -- #{ getGroups
        -- # :username string
        SELECT players.groups FROM players WHERE username = :username;
    -- #}
    -- #{ getPermissions
        -- # :username string
        SELECT players.permissions FROM players WHERE username = :username;
    -- #}
    -- #{ check
        -- # :username string
        SELECT EXISTS(SELECT * FROM players WHERE username = :username)
    -- #}
-- #}