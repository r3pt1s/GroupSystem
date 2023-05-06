<?php

namespace r3pt1s\GroupSystem\group;

use r3pt1s\GroupSystem\event\GroupCreateEvent;
use r3pt1s\GroupSystem\event\GroupEditEvent;
use r3pt1s\GroupSystem\event\GroupRemoveEvent;
use pocketmine\utils\Config;
use r3pt1s\GroupSystem\util\Configuration;

class GroupManager {

    private static self $instance;
    /** @var Group[] */
    private array $groups = [];

    public function __construct() {
        self::$instance = $this;

        $this->load();
        $this->createDefaults();
    }

    public function load() {
        foreach ($this->getGroupsConfig()->getAll() as $name => $data) {
            if (isset($data["NameTag"]) && isset($data["DisplayName"]) && isset($data["ChatFormat"]) && isset($data["ColorCode"]) && isset($data["Permissions"])) {
                if (is_array($data["Permissions"])) {
                    $this->groups[$name] = new Group($name, $data["NameTag"], $data["DisplayName"], $data["ChatFormat"], $data["ColorCode"], $data["Permissions"]);
                }
            }
        }
    }

    public function createGroup(Group $group) {
        $cfg = $this->getGroupsConfig();
        $cfg->set($group->getName(), [
            "NameTag" => $group->getNameTag(),
            "DisplayName" => $group->getDisplayName(),
            "ChatFormat" => $group->getChatFormat(),
            "ColorCode" => $group->getColorCode(),
            "Permissions" => $group->getPermissions()
        ]);
        $cfg->save();

        (new GroupCreateEvent($group))->call();

        if (!isset($this->groups[$group->getName()])) $this->groups[$group->getName()] = $group;
    }

    public function removeGroup(Group $group) {
        $cfg = $this->getGroupsConfig();
        $cfg->remove($group->getName());
        $cfg->save();

        (new GroupRemoveEvent($group))->call();

        if (isset($this->groups[$group->getName()])) unset($this->groups[$group->getName()]);
    }

    public function editGroup(Group $group, string $nameTag, string $displayName, string $chatFormat, string $colorCode, array $permissions) {
        $cfg = $this->getGroupsConfig();
        $cfg->set($group->getName(), [
            "NameTag" => $nameTag,
            "DisplayName" => $displayName,
            "ChatFormat" => $chatFormat,
            "ColorCode" => $colorCode,
            "Permissions" => $permissions
        ]);
        $cfg->save();
        $this->reload();
        (new GroupEditEvent(
            $group,
            ["nameTag" => $group->getNameTag(), "displayName" => $group->getDisplayName(), "chatFormat" => $group->getChatFormat(), "colorCode" => $group->getColorCode(), "permissions" => $group->getPermissions()],
            ["nameTag" => $nameTag, "displayName" => $displayName, "chatFormat" => $chatFormat, "colorCode" => $colorCode, "permissions" => $permissions]
        ))->call();
    }

    private function createDefaults() {
        if (!$this->isGroupExisting(Configuration::getInstance()->getDefaultGroup())) $this->createGroup(new Group(Configuration::getInstance()->getDefaultGroup()));
    }

    public function getGroupByName(string $name): ?Group {
        foreach ($this->groups as $group) if ($group->getName() == $name) return $group;
        return null;
    }

    public function getDefaultGroup(): ?Group {
        return $this->getGroupByName(Configuration::getInstance()->getDefaultGroup());
    }

    public function isGroupExisting(string $name): bool {
        return self::getGroupsConfig()->exists($name);
    }

    public function getGroups(): array {
        return $this->groups;
    }

    public function getGroupsConfig(): Config {
        return new Config(Configuration::getInstance()->getGroupsPath() . "groups.yml", 2);
    }

    public function reload() {
        $this->groups = [];
        $this->getGroupsConfig()->reload();
        $this->load();
    }

    public static function getInstance(): GroupManager {
        return self::$instance;
    }
}