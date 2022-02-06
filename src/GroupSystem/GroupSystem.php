<?php

namespace GroupSystem;

use GroupSystem\commands\GroupCommand;
use GroupSystem\group\GroupManager;
use GroupSystem\listener\EventListener;
use GroupSystem\utils\Utils;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\plugin\PluginBase;

class GroupSystem extends PluginBase {

    private static self $instance;
    private GroupManager $groupManager;

    protected function onEnable(): void {
        self::$instance = $this;

        $this->saveResource("messages.json");
        $this->saveResource("config.yml");

        $this->groupManager = new GroupManager();

        $this->loadPermissions();

        $this->getServer()->getCommandMap()->register("group", new GroupCommand("group", "Group Command", "", ["rank", "rang"]));

        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
    }

    private function loadPermissions() {
        $operator = PermissionManager::getInstance()->getPermission(DefaultPermissions::ROOT_OPERATOR);
        if ($operator !== null) {
            DefaultPermissions::registerPermission(new Permission("group.command"), [$operator]);
        }
    }

    public function getGroupManager(): GroupManager {
        return $this->groupManager;
    }

    public static function getInstance(): GroupSystem {
        return self::$instance;
    }
}