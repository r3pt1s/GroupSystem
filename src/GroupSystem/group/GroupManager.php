<?php

namespace GroupSystem\group;

use GroupSystem\event\GroupCreateEvent;
use GroupSystem\event\GroupRemoveEvent;
use GroupSystem\event\GroupSetEvent;
use GroupSystem\GroupSystem;
use GroupSystem\utils\Utils;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionAttachment;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Config;

class GroupManager {

    private static self $instance;
    /** @var Group[] */
    private array $groups = [];
    /** @var PermissionAttachment[] */
    private array $attachments = [];

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

    public function createPlayer(Player $player): bool {
        if (!$this->isPlayerExisting($player->getName())) {
            if (($group = $this->getDefaultGroup()) !== null) {
                $cfg = $this->getPlayersConfig();
                $cfg->set($player->getName(), $group->getName());
                $cfg->save();
            } else return false;
        }
        return true;
    }

    public function removeGroup(Group $group) {
        $cfg = $this->getGroupsConfig();
        $cfg->remove($group->getName());
        $cfg->save();

        (new GroupRemoveEvent($group))->call();

        if (isset($this->groups[$group->getName()])) unset($this->groups[$group->getName()]);
    }

    private function createDefaults() {
        if (!$this->isGroupExisting($this->getConfig()->get("DefaultGroup"))) $this->createGroup(new Group($this->getConfig()->get("DefaultGroup")));
    }

    public function getGroupByName(string $name): ?Group {
        foreach ($this->groups as $group) if ($group->getName() == $name) return $group;
        return null;
    }

    public function getDefaultGroup(): ?Group {
        return $this->getGroupByName($this->getConfig()->get("DefaultGroup"));
    }

    public function isPlayerExisting(string $name): bool {
        return self::getPlayersConfig()->exists($name);
    }

    public function isGroupExisting(string $name): bool {
        return self::getGroupsConfig()->exists($name);
    }

    public function setGroup(Player|string $player, Group $group) {
        $player = $player instanceof Player ? $player->getName() : $player;

        $cfg = $this->getPlayersConfig();
        $cfg->set($player, $group->getName());
        $cfg->save();

        (new GroupSetEvent($player, $group))->call();

        if (($player = Server::getInstance()->getPlayerByPrefix($player)) !== null) {
            $player->sendMessage(Utils::parse("groupChanged", [$group->getColorCode() . $group->getName()]));
            $this->updatePlayer($player);
        }
    }

    public function getGroup(Player|string $player): ?Group {
        $player = $player instanceof Player ? $player->getName() : $player;
        return ($this->isPlayerExisting($player) ? $this->getGroupByName($this->getPlayersConfig()->get($player)) : null);
    }

    public function setAttachment(Player $player) {
        if (!isset($this->attachments[$player->getName()])) $this->attachments[$player->getName()] = $player->addAttachment(GroupSystem::getInstance());
    }

    public function getAttachment(Player $player): ?PermissionAttachment {
        if (isset($this->attachments[$player->getName()])) return $this->attachments[$player->getName()];
        return null;
    }

    public function updatePlayer(Player $player) {
        if (($group = $this->getGroup($player)) !== null) {
            $player->setNameTag(str_replace(["{name}"], $player->getName(), $group->getNameTag()));
            $player->setDisplayName(str_replace(["{name}"], $player->getName(), $group->getDisplayName()));
            $this->updatePermissions($player);
        }
    }

    public function updatePermissions(Player $player) {
        if (($group = $this->getGroup($player)) !== null) {
            if (($attachment = $this->getAttachment($player)) !== null) {
                $attachment->clearPermissions();
                foreach ($group->getPermissions() as $permission) {
                    $attachment->setPermission(new Permission($permission), true);
                }
            }
        }
    }

    public function getAttachments(): array {
        return $this->attachments;
    }

    public function getGroups(): array {
        return $this->groups;
    }

    public function getPlayersConfig(): Config {
        return new Config(GroupSystem::getInstance()->getDataFolder() . "players.yml", 2);
    }

    public function getGroupsConfig(): Config {
        return new Config(GroupSystem::getInstance()->getDataFolder() . "groups.yml", 2);
    }

    public function getConfig(): Config {
        return new Config(GroupSystem::getInstance()->getDataFolder() . "config.yml", 2);
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