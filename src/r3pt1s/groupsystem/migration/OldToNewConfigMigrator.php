<?php

namespace r3pt1s\groupsystem\migration;

use JsonException;
use pocketmine\utils\Config;
use r3pt1s\groupsystem\GroupSystem;
use r3pt1s\groupsystem\util\Configuration;
use r3pt1s\groupsystem\util\Utils;

final class OldToNewConfigMigrator implements Migrator {

    private const OLD_GROUP_KEYS = [
        "NameTag", "DisplayName", "ChatFormat", "ColorCode", "Permissions"
    ];

    private const OLD_PLAYER_KEYS = [
        "Group", "ExpireAt", "Groups", "Permissions"
    ];

    public function migrate(): void {
        if (strtolower(Configuration::getInstance()->getProvider()) == "mysql") {
            GroupSystem::getInstance()->getLogger()->warning("§cCan't convert §eold config data §cto §enew config data §cbecause you use the §bMySQL §cprovider!");
            return;
        }

        $extension = file_exists(Configuration::getInstance()->getGroupsPath() . "groups.yml") ? "yml" : "json";
        if (file_exists(Configuration::getInstance()->getGroupsPath() . "groups." . $extension)) {
            $groupsFilePath = Configuration::getInstance()->getGroupsPath();
            $groupsFile = new Config($groupsFilePath . "groups." . $extension, match ($extension) {
                "yml" => Config::YAML,
                default => Config::JSON
            });

            $anyOld = false;
            foreach (array_map(fn(array $data) => array_keys($data), $groupsFile->getAll()) as $data) {
                foreach ($data as $key) {
                    if (in_array($key, self::OLD_GROUP_KEYS)) {
                        $anyOld = true;
                        break;
                    }
                }
            }

            if ($anyOld) {
                GroupSystem::getInstance()->getLogger()->notice("§rConverting data from §eold config files §rto §enew config files§r...");
                $groups = array_map(fn(array $data) => Utils::renewGroupDataKeys($data), $groupsFile->getAll());
                if (file_exists(Configuration::getInstance()->getGroupsPath() . "groups." . $extension)) rename(Configuration::getInstance()->getGroupsPath() . "groups." . $extension, Configuration::getInstance()->getGroupsPath() . "old_groups." . $extension);
                try {
                    file_put_contents(Configuration::getInstance()->getGroupsPath() . "groups." . $extension, match ($extension) {
                        "yml" => yaml_emit($groups, YAML_UTF8_ENCODING),
                        default => json_encode($groups, JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING | JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)
                    });
                } catch (JsonException) {}

                GroupSystem::getInstance()->getLogger()->notice("§aSuccessfully §fconverted §eold group files §fto §enew group files§f!");
            }
        }

        if (file_exists(Configuration::getInstance()->getPlayersPath())) {
            $anyOldPlayer = false;
            if (count(array_diff(scandir(Configuration::getInstance()->getPlayersPath()), [".", ".."])) == 0) {
                return;
            }

            foreach (array_diff(scandir(Configuration::getInstance()->getPlayersPath()), [".", ".."]) as $file) {
                if ($anyOldPlayer) break;
                $content = file_get_contents(Configuration::getInstance()->getPlayersPath() . $file);
                foreach (self::OLD_PLAYER_KEYS as $keyOld) {
                    if (str_contains($content, $keyOld)) {
                        $anyOldPlayer = true;
                        break;
                    }
                }
            }

            if ($anyOldPlayer) {
                foreach (array_diff(scandir(Configuration::getInstance()->getPlayersPath()), [".", ".."]) as $file) {
                    $playerName = pathinfo(Configuration::getInstance()->getPlayersPath() . $file, PATHINFO_FILENAME);
                    $data = json_decode(file_get_contents(Configuration::getInstance()->getPlayersPath() . $file), true);
                    if (file_exists(Configuration::getInstance()->getPlayersPath() . $file)) rename(Configuration::getInstance()->getPlayersPath() . $file, Configuration::getInstance()->getPlayersPath() . "old_" . $playerName . ".json");
                    try {
                        file_put_contents(Configuration::getInstance()->getPlayersPath() . $file, json_encode(Utils::renewPlayerDataKeys($data), JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING | JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
                    } catch (JsonException) {}
                }

                GroupSystem::getInstance()->getLogger()->notice("§aSuccessfully §fconverted §eold player files §fto §enew player files§f!");
            }
        }
    }
}