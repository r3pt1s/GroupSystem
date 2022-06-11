<?php

namespace r3pt1s\GroupSystem\player;

use r3pt1s\GroupSystem\group\Group;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionAttachment;
use pocketmine\player\Player;
use pocketmine\Server;

class GroupPlayer {

    private string $name;
    private ?PermissionAttachment $attachment = null;

    public function __construct(string $name) {
        $this->name = $name;
    }

    public function setAttachment(PermissionAttachment $attachment) {
        $this->attachment = $attachment;
    }

    public function setGroup(PlayerGroup $group) {
        PlayerGroupManager::getInstance()->setGroup($this, $group);
    }

    public function addGroup(PlayerGroup $group): bool {
        return PlayerGroupManager::getInstance()->addGroup($this, $group);
    }

    public function removeGroup(PlayerGroup $group): bool {
        return PlayerGroupManager::getInstance()->removeGroup($this, $group);
    }

    public function hasGroup(PlayerGroup|Group|string $group): bool {
        return PlayerGroupManager::getInstance()->hasGroup($this, $group);
    }

    public function nextGroup() {
        PlayerGroupManager::getInstance()->nextGroup($this);
    }

    public function getNextHighestGroup(): ?PlayerGroup {
        return PlayerGroupManager::getInstance()->getNextHighestGroup($this);
    }

    public function update() {
        $player = $this->getPlayer();
        if ($player !== null) {
            $group = PlayerGroupManager::getInstance()->getGroup($this);
            $player->setNameTag(str_replace(["{name}"], $player->getName(), $group->getGroup()->getNameTag()));
            $player->setDisplayName(str_replace(["{name}"], $player->getName(), $group->getGroup()->getDisplayName()));
            $this->reloadPermissions();
        }
    }

    public function reloadPermissions() {
        if ($this->attachment === null) return;
        $this->attachment->clearPermissions();
        $group = PlayerGroupManager::getInstance()->getGroup($this);
        foreach ($group->getGroup()->getPermissions() as $permission) $this->attachment->setPermission(new Permission($permission), true);
        foreach (PlayerGroupManager::getInstance()->getPermissions($this) as $permission) $this->attachment->setPermission(new Permission($permission), true);
    }

    public function getName(): string {
        return $this->name;
    }

    public function getAttachment(): ?PermissionAttachment {
        return $this->attachment;
    }

    public function getPlayer(): ?Player {
        return Server::getInstance()->getPlayerExact($this->name);
    }
}
