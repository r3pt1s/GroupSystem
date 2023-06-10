<?php

namespace r3pt1s\groupsystem\util;

class Database extends Medoo {

    public function __construct(private array $data) {
        parent::__construct(array_merge(["type" => "mysql"], $this->data));
    }

    public function exec(string $statement, array $map = [], callable $callback = null): ?\PDOStatement {
        try {
            return parent::exec($statement, $map, $callback);
        } catch (\Exception $exception) {
            if (str_contains("gone away", $exception->getMessage())) {
                parent::__construct(array_merge(["type" => "mysql"], $this->data));
                return parent::exec($statement, $map, $callback);
            } else \GlobalLogger::get()->logException($exception);
        }
        return null;
    }

    public function initializeTable() {
        $this->create("groups", [
            "name" => "VARCHAR(32) PRIMARY KEY",
            "display_name" => "VARCHAR(100)",
            "name_tag" => "VARCHAR(100)",
            "chat_format" => "VARCHAR(100)",
            "color_code" => "VARCHAR(6)",
            "permissions" => "MEDIUMTEXT"
        ]);

        $this->create("players", [
            "username" => "VARCHAR(16) PRIMARY KEY",
            "group" => "VARCHAR(32)",
            "expire" => "TIMESTAMP NULL DEFAULT NULL",
            "groups" => "MEDIUMTEXT",
            "permissions" => "MEDIUMTEXT"
        ]);
    }
}