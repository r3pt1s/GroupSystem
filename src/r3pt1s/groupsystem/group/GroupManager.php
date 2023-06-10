<?php

namespace r3pt1s\groupsystem\group;

use JetBrains\PhpStorm\Pure;
use r3pt1s\groupsystem\event\GroupCreateEvent;
use r3pt1s\groupsystem\event\GroupEditEvent;
use r3pt1s\groupsystem\event\GroupRemoveEvent;
use r3pt1s\groupsystem\GroupSystem;
use r3pt1s\groupsystem\provider\JSONProvider;
use r3pt1s\groupsystem\provider\YAMLProvider;
use r3pt1s\groupsystem\util\Configuration;

class GroupManager {

    private static self $instance;
    /** @var array<Group> */
    private array $groups = [];

    public function __construct() {
        self::$instance = $this;

        $this->load();
        $this->createDefaults();
    }

    private function load() {
        GroupSystem::getInstance()->getProvider()->getAllGroups()->onCompletion(function(array $groups): void {
            $this->groups = $groups;
        }, function(): void {
            GroupSystem::getInstance()->getLogger()->warning("§cFailed to fetch groups");
        });
    }

    public function createGroup(Group $group) {
        GroupSystem::getInstance()->getProvider()->createGroup($group);
        (new GroupCreateEvent($group))->call();
        if (!isset($this->groups[$group->getName()])) $this->groups[$group->getName()] = $group;
    }

    public function removeGroup(Group $group) {
        GroupSystem::getInstance()->getProvider()->removeGroup($group);
        (new GroupRemoveEvent($group))->call();
        if (isset($this->groups[$group->getName()])) unset($this->groups[$group->getName()]);
    }

    public function editGroup(Group $group, string $nameTag, string $displayName, string $chatFormat, string $colorCode, array $permissions) {
        $group->apply([
            "name_tag" => $nameTag, "display_name" => $displayName, "chat_format" => $chatFormat, "color_code" => $colorCode, "permissions" => $permissions
        ]);

        GroupSystem::getInstance()->getProvider()->editGroup($group, array_merge(["name" => $group->getName()], [
            "name_tag" => $nameTag, "display_name" => $displayName, "chat_format" => $chatFormat, "color_code" => $colorCode, "permissions" => $permissions
        ]));

        (new GroupEditEvent(
            $group,
            ["nameTag" => $group->getNameTag(), "displayName" => $group->getDisplayName(), "chatFormat" => $group->getChatFormat(), "colorCode" => $group->getColorCode(), "permissions" => $group->getPermissions()],
            ["nameTag" => $nameTag, "displayName" => $displayName, "chatFormat" => $chatFormat, "colorCode" => $colorCode, "permissions" => $permissions]
        ))->call();
    }

    private function createDefaults() {
        if (!$this->isGroupExisting(Configuration::getInstance()->getDefaultGroup())) $this->createGroup(new Group(Configuration::getInstance()->getDefaultGroup()));
    }

    #[Pure] public function getGroupByName(string $name): ?Group {
        return $this->groups[$name] ?? null;
    }

    #[Pure] public function getDefaultGroup(): ?Group {
        return $this->getGroupByName(Configuration::getInstance()->getDefaultGroup());
    }

    public function isGroupExisting(string $name): bool {
        return isset($this->groups[$name]);
    }

    public function getGroups(): array {
        return $this->groups;
    }

    public function reload() {
        $provider = GroupSystem::getInstance()->getProvider();
        $this->groups = [];
        if ($provider instanceof YAMLProvider || $provider instanceof JSONProvider) $provider->getFile()?->reload();
        $this->load();
    }

    public static function getInstance(): GroupManager {
        return self::$instance;
    }
}