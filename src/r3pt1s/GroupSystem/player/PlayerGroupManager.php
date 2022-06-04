<?php

namespace r3pt1s\GroupSystem\player;

use r3pt1s\GroupSystem\event\GroupSetEvent;
use r3pt1s\GroupSystem\event\PermissionAddEvent;
use r3pt1s\GroupSystem\event\PermissionRemoveEvent;
use r3pt1s\GroupSystem\group\Group;
use r3pt1s\GroupSystem\group\GroupManager;
use r3pt1s\GroupSystem\GroupSystem;
use r3pt1s\GroupSystem\task\GroupExpireTask;
use r3pt1s\GroupSystem\util\Utils;
use pocketmine\player\Player;
use pocketmine\utils\Config;

class PlayerGroupManager {

    private static self $instance;
    /** @var array<GroupPlayer> */
    private array $players = [];

    public function __construct() {
        self::$instance = $this;
        if (!file_exists(GroupSystem::getInstance()->getDataFolder() . "players/")) mkdir(GroupSystem::getInstance()->getDataFolder() . "players/");
        GroupSystem::getInstance()->getScheduler()->scheduleRepeatingTask(new GroupExpireTask(), 20);
    }

    public function loadPlayer(string $username): GroupPlayer {
        $this->players[$username] = $player = new GroupPlayer($username);
        return $player;
    }

    public function unloadPlayer(GroupPlayer|string $player) {
        $player = $player instanceof GroupPlayer ? $player->getName() : $player;
        if (isset($this->players[$player])) unset($this->players[$player]);
    }

    public function setGroup(GroupPlayer|string $player, PlayerGroup $group) {
        $cfg = $this->getPlayerConfig($player);
        $expireAt = null;
        if ($group->getExpireDate() instanceof \DateTime) $expireAt = $group->getExpireDate()->format("Y-m-d H:i:s");
        else if ($group->getExpireDate() !== null) $expireAt = Utils::convertStringToDateFormat($group->getExpireDate())?->format("Y-m-d H:i:s");
        $cfg->set("Group", $group->getGroup()->getName());
        $cfg->set("ExpireAt", $expireAt);
        $cfg->set("Priority", $group->getPriority()->getName());
        $cfg->save();
        $username = $player instanceof GroupPlayer ? $player->getName() : $player;
        (new GroupSetEvent($username, $group))->call();
        if ($player instanceof GroupPlayer) {
            $player->update();
        } else {
            $player = $this->getPlayer($username);
            if ($player !== null) $player->update();
        }
    }

    public function addGroup(GroupPlayer|string $player, PlayerGroup $group): bool {
        $cfg = $this->getPlayerConfig($player);
        $currentGroups = $this->getGroups($player);
        foreach ($currentGroups as $i => $data) if ($data["group"] == $group->getGroup()->getName()) return false;
        $currentGroups[] = ["group" => $group->getGroup()->getName(), "priority" => $group->getPriority()->getName(), "time" => $group->getExpireDate()];
        $cfg->set("Groups", $currentGroups);
        $cfg->save();
        return true;
    }

    public function removeGroup(GroupPlayer|string $player, PlayerGroup|Group $group): bool {
        $cfg = $this->getPlayerConfig($player);
        $currentGroups = $this->getGroups($player);
        $index = -1;
        foreach ($currentGroups as $i => $data) if ($data["group"] == ($group instanceof PlayerGroup ? $group->getGroup()->getName() : $group->getName())) $index = $i;
        if (isset($currentGroups[$index])) {
            unset($currentGroups[$index]);
            $cfg->set("Groups", $currentGroups);
            $cfg->save();
            return true;
        }
        return false;
    }

    public function hasGroup(GroupPlayer|string $player, PlayerGroup|Group|string $group): bool {
        $group = $group instanceof PlayerGroup ? $group->getGroup()->getName() : ($group instanceof Group ? $group->getName() : $group);
        /** @var PlayerGroup $v */
        foreach ($this->getGroups($player, true) as $v) if ($v->getGroup()->getName() == $group) return true;
        return false;
    }

    public function getGroup(GroupPlayer|string $player): PlayerGroup {
        $cfg = $this->getPlayerConfig($player);
        $expireAt = null;
        if ($cfg->get("ExpireAt", null) !== null) {
            try {
                $expireAt = new \DateTime($cfg->get("ExpireAt"));
            } catch (\Exception $exception) {}
        }
        $currentGroup = GroupManager::getInstance()->getGroupByName($cfg->get("Group", "")) ?? GroupManager::getInstance()->getDefaultGroup();
        $currentGroupPriority = $cfg->get("Priority", "");
        return new PlayerGroup($currentGroup, GroupPriority::get($currentGroupPriority), $expireAt);
    }

    public function getGroups(GroupPlayer|string $player, bool $asPlayerGroup = false): array {
        $cfg = $this->getPlayerConfig($player);
        $groups = [];
        foreach ((array)$cfg->get("Groups", []) as $data) {
            $group = GroupManager::getInstance()->getGroupByName($data["group"]);
            if ($group === null) continue;
            $time = null;
            if (isset($data["time"])) if (Utils::convertStringToDateFormat($data["time"]) !== null) $time = $data["time"];
            if ($asPlayerGroup) $groups[] = new PlayerGroup($group, GroupPriority::get($data["priority"] ?? "low"), $time);
            else $groups[] = ["group" => $group->getName(), "priority" => GroupPriority::get($data["priority"] ?? "low")->getName(), "time" => $time];
        }
        return $groups;
    }

    public function nextGroup(GroupPlayer|string $player) {
        if (count($this->getGroups($player)) == 0) {
            $this->setGroup($player, new PlayerGroup(GroupManager::getInstance()->getDefaultGroup(), GroupPriority::LOW(), null));
        } else {
            $group = $this->getNextHighestGroup($player);
            if ($group !== null) {
                $this->removeGroup($player, $group);
                $this->setGroup($player, $group);
                $this->update($player);
            } else {
                $this->setGroup($player, new PlayerGroup(GroupManager::getInstance()->getDefaultGroup(), GroupPriority::LOW(), null));
            }
        }
    }

    public function getNextHighestGroup(GroupPlayer|string $player): ?PlayerGroup {
        if (count($this->getGroups($player)) == 0) return null;
        $groups = $this->getGroups($player, true);
        usort($groups, function(PlayerGroup $a, PlayerGroup $b): int {
            if ($a->getPriority()->getLevel() == $b->getPriority()->getLevel()) return 0;
            return ($a->getPriority()->isLower($b->getPriority())) ? -1 : 1;
        });
        return $groups[0] ?? null;
    }

    public function update(GroupPlayer|string $player) {
        if ($player instanceof GroupPlayer) {
            $player->update();
        } else {
            $player = $this->getPlayer($player);
            if ($player instanceof GroupPlayer) $player->update();
        }
    }

    public function addPermission(GroupPlayer|string $player, string $permission) {
        $cfg = $this->getPlayerConfig($player);
        $permissions = $this->getPermissions($player);
        $permissions[] = $permission;
        $cfg->set("Permissions", $permissions);
        $cfg->save();

        (new PermissionAddEvent(($player instanceof GroupPlayer ? $player->getName() : $player), $permission))->call();

        if ($player instanceof GroupPlayer) {
            $player->reloadPermissions();
        } else {
            $player = $this->getPlayer($player);
            if ($player instanceof GroupPlayer) $player->reloadPermissions();
        }
    }

    public function removePermission(GroupPlayer|string $player, string $permission) {
        $cfg = $this->getPlayerConfig($player);
        $permissions = $this->getPermissions($player);
        $index = -1;
        foreach ($permissions as $i => $perm) if ($perm == $permission) $index = $i;
        if (isset($permissions[$index])) unset($permissions[$index]);
        $cfg->set("Permissions", $permissions);
        $cfg->save();

        (new PermissionRemoveEvent(($player instanceof GroupPlayer ? $player->getName() : $player), $permission))->call();

        if ($player instanceof GroupPlayer) {
            $player->reloadPermissions();
        } else {
            $player = $this->getPlayer($player);
            if ($player instanceof GroupPlayer) $player->reloadPermissions();
        }
    }

    public function checkPlayer(string $username): bool {
        return file_exists(GroupSystem::getInstance()->getDataFolder() . "players/{$username}.json");
    }

    public function getPermissions(GroupPlayer|string $player): array {
        $cfg = $this->getPlayerConfig($player);
        return (array) $cfg->get("Permissions", []);
    }

    public function getPlayer(string $name): ?GroupPlayer {
        return $this->players[strtolower($name)] ?? null;
    }

    public function getPlayers(): array {
        return $this->players;
    }

    public function getPlayerConfig(Player|GroupPlayer|string $username): Config {
        $username = $username instanceof Player || $username instanceof GroupPlayer ? $username->getName() : $username;
        return new Config(GroupSystem::getInstance()->getDataFolder() . "players/{$username}.json", 1);
    }
    
    public function getAllPlayersConfig(): array {
        $configs = [];
        foreach (scandir(GroupSystem::getInstance()->getDataFolder() . "players/") as $file) {
            if ($file == "." || $file == "..") continue;
            if (pathinfo(GroupSystem::getInstance()->getDataFolder() . "players/" . $file, PATHINFO_EXTENSION) == "json") {
                $configs[str_replace(".json", "", $file)] = $this->getPlayerConfig(str_replace(".json", "", $file));
            }
        }
        return $configs;
    }

    public static function getInstance(): PlayerGroupManager {
        return self::$instance;
    }
}