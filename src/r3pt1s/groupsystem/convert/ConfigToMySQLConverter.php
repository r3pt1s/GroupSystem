<?php

namespace r3pt1s\groupsystem\convert;

use pocketmine\utils\Config;
use r3pt1s\groupsystem\GroupSystem;
use r3pt1s\groupsystem\util\AsyncExecutor;
use r3pt1s\groupsystem\util\Configuration;
use r3pt1s\groupsystem\util\Database;
use r3pt1s\groupsystem\util\Utils;

class ConfigToMySQLConverter implements Converter {

    public function convert(): void {
        if (strtolower(Configuration::getInstance()->getProvider()) !== "mysql") {
            GroupSystem::getInstance()->getLogger()->warning("§cCan't convert §econfig data §cto §bMySQL§c because you don't use the §bMySQL §cprovider!");
            return;
        }

        $extension = file_exists(Configuration::getInstance()->getGroupsPath() . "groups.yml") ? "yml" : "json";
        if (file_exists(Configuration::getInstance()->getGroupsPath() . "groups." . $extension)) {
            GroupSystem::getInstance()->getLogger()->notice("§rConverting data from §econfig files §rto §bMySQL§r...");
            $groupsFilePath = Configuration::getInstance()->getGroupsPath();
            $groupsFile = new Config($groupsFilePath . "groups." . $extension, match ($extension) {
                "yml" => Config::YAML,
                default => Config::JSON
            });
            $groups = $groupsFile->getAll();
            AsyncExecutor::execute(function(Database $database) use($groups, $groupsFilePath, $extension): void {
                foreach ($groups as $name => $group) {
                    if (!isset($group["name"])) $group["name"] = $name;
                    $group = Utils::renewGroupDataKeys($group);
                    if (!$database->has("groups", ["name" => $group["name"]])) {
                        $group["permissions"] = json_encode($group["permissions"]);
                        $database->insert("groups", $group);
                    }
                }

                if (file_exists($groupsFilePath . "groups." . $extension)) rename($groupsFilePath . "groups." . $extension, $groupsFilePath . "old_groups." . $extension);
            }, function() use($extension): void {
                if (file_exists(Configuration::getInstance()->getPlayersPath())) {
                    if (count(array_diff(scandir(Configuration::getInstance()->getPlayersPath()), [".", ".."])) == 0) {
                        GroupSystem::getInstance()->getLogger()->notice("§aSuccessfully §fconverted §econfig files §fto §bMySQL§f!");
                        return;
                    }

                    $path = Configuration::getInstance()->getPlayersPath();
                    $files = array_filter(array_diff(scandir(Configuration::getInstance()->getPlayersPath()), [".", ".."]), fn(string $file) => is_file(Configuration::getInstance()->getPlayersPath() . $file));
                    AsyncExecutor::execute(function(Database $database) use($files, $path): void {
                        foreach ($files as $file) {
                            $playerName = pathinfo($path . $file, PATHINFO_FILENAME);
                            $playerData = Utils::renewPlayerDataKeys(json_decode(file_get_contents($path . $file), true));
                            if (file_exists($path . $file)) rename($path . $file, $path . "old_" . $file);
                            if (!$database->has("players", ["username" => $playerName])) {
                                if (isset($playerData["groups"])) $playerData["groups"] = json_encode($playerData["groups"]);
                                if (isset($playerData["permissions"])) $playerData["permissions"] = json_encode($playerData["permissions"]);
                                $database->insert("players", array_merge(["username" => $playerName], $playerData));
                            }
                        }
                    }, fn() => GroupSystem::getInstance()->getLogger()->notice("§aSuccessfully §fconverted §econfig files §fto §bMySQL§f!"));
                } else {
                    GroupSystem::getInstance()->getLogger()->notice("§aSuccessfully §fconverted §econfig files §fto §bMySQL§f!");
                }
            });
        }
    }
}