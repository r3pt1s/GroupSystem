<?php

namespace r3pt1s\groupsystem\migration;

use pocketmine\utils\Config;
use r3pt1s\groupsystem\group\Group;
use r3pt1s\groupsystem\GroupSystem;
use r3pt1s\groupsystem\util\Configuration;
use r3pt1s\groupsystem\util\Utils;

final class ConfigToMySQLMigrator implements Migrator {

    public function migrate(): void {
        if (strtolower(Configuration::getInstance()->getProvider()) !== "mysql") {
            GroupSystem::getInstance()->getLogger()->warning("§cCan't convert §econfig data §cto §bMySQL§c because you don't use the §bMySQL §cprovider!");
            return;
        }

        $provider = GroupSystem::getInstance()->getProvider();
        $extension = file_exists(Configuration::getInstance()->getGroupsPath() . "groups.yml") ? "yml" : "json";
        if (file_exists(Configuration::getInstance()->getGroupsPath() . "groups." . $extension)) {
            GroupSystem::getInstance()->getLogger()->notice("§rConverting groups from §econfig files §rto §bMySQL§r...");
            $groupsFilePath = Configuration::getInstance()->getGroupsPath();
            $groupsFile = new Config($groupsFilePath . "groups." . $extension, match ($extension) {
                "yml" => Config::YAML,
                default => Config::JSON
            });
            $groups = $groupsFile->getAll();
            foreach ($groups as $key => $groupData) {
                $groupData = Utils::renewGroupDataKeys($groupData);
                if (($group = Group::read($groupData)) !== null) {
                    $provider->checkGroup($group->getName())->onCompletion(function (bool $exists) use($group, $provider): void {
                        if (!$exists) $provider->createGroup($group);
                    }, fn() => GroupSystem::getInstance()->getLogger()->warning("§cFailed to convert group §e" . $key . " §cfrom config files to §bMySQL§c!"));
                }
            }

            GroupSystem::getInstance()->getLogger()->notice("§aSuccessfully §rconverted §egroup config files §rto §bMySQL§r! §rRenaming old §egroups." . $extension . " §rto §eold_groups." . $extension . "§r...");
            if (file_exists($groupsFilePath . "groups." . $extension)) rename($groupsFilePath . "groups." . $extension, $groupsFilePath . "old_groups." . $extension);
        }

        if (file_exists($path = Configuration::getInstance()->getPlayersPath())) {
            if (count(array_diff(scandir($path), [".", ".."])) == 0) return;
            $files = array_filter(array_diff(scandir(Configuration::getInstance()->getPlayersPath()), [".", ".."]), fn(string $file) => is_file(Configuration::getInstance()->getPlayersPath() . $file));

            foreach ($files as $file) {
                $playerName = pathinfo($path . $file, PATHINFO_FILENAME);
                $playerData = Utils::renewPlayerDataKeys(json_decode(file_get_contents($path . $file), true));
                if (file_exists($path . $file)) rename($path . $file, $path . "old_" . $file);
                $provider->checkPlayer($playerName)->onCompletion(function (bool $exists) use($playerName, $playerData, $provider): void {
                    if ($exists) return;
                    $provider->createPlayer($playerName, customData: $playerData);
                }, fn() => GroupSystem::getInstance()->getLogger()->warning("§cFailed to convert player §e" . $playerName . " §cfrom config files to §bMySQL§c!"));
            }

            GroupSystem::getInstance()->getLogger()->notice("§aSuccessfully §rconverted §eplayer config files §rto §bMySQL§r!");
        }
    }
}