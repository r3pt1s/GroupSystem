<?php

namespace r3pt1s\groupsystem\session;

use Closure;
use JetBrains\PhpStorm\Pure;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionAttachment;
use pocketmine\player\Player;
use pocketmine\Server;
use r3pt1s\groupsystem\event\GroupSetEvent;
use r3pt1s\groupsystem\event\PermissionAddEvent;
use r3pt1s\groupsystem\event\PermissionRemoveEvent;
use r3pt1s\groupsystem\event\PlayerUpdateEvent;
use r3pt1s\groupsystem\group\Group;
use r3pt1s\groupsystem\group\GroupManager;
use r3pt1s\groupsystem\GroupSystem;
use r3pt1s\groupsystem\player\perm\PlayerPermission;
use r3pt1s\groupsystem\player\PlayerGroup;
use r3pt1s\groupsystem\player\PlayerRemainingGroup;
use r3pt1s\groupsystem\util\Configuration;

class Session {

    private bool $loaded = false;
    private bool $loadedSuccessful = false;
    private array $completionClosures = [];
    private ?PlayerGroup $currentGroup = null;
    /** @var array<PlayerRemainingGroup> */
    private array $groups = [];
    /** @var array<PlayerPermission> */
    private array $permissions = [];
    private ?PermissionAttachment $attachment = null;

    public function __construct(private readonly string $username) {
        $this->loadData();
    }

    public function debug(string $message, ...$params): void {
        if (!Configuration::getInstance()->isInGameDebugging()) return;
        foreach (array_filter(Server::getInstance()->getOnlinePlayers(), fn(Player $player) => $player->hasPermission(DefaultPermissions::ROOT_OPERATOR)) as $p) {
            $p->sendMessage("§8[§6DEBUG§7/§c" . $this->username . "§8] §f" . sprintf($message, ...$params));
        }
    }

    public function setGroup(PlayerGroup $group): void {
        $this->debug("Setting the group to %s", $group->getName());
        GroupSystem::getInstance()->getProvider()->setGroup($this->username, $group);
        (new GroupSetEvent($this->username, $group))->call();
        $this->currentGroup = $group;
        $this->update();
    }

    public function addGroup(PlayerRemainingGroup $group, ?Closure $completion = null): void {
        $this->debug("Adding group %s", $group->getName());
        GroupSystem::getInstance()->getProvider()->addGroupToPlayer($this->username, $group)->onCompletion(
            function(bool $canAdd) use($group, $completion): void {
                if ($canAdd) {
                    $this->groups[$group->getGroup()->getName()] = $group;
                    $this->debug("Added group %s", $group->getName());
                } else {
                    $this->debug("Failed to add group %s", $group->getName());
                }

                if ($completion !== null) $completion($canAdd);
            },
            function() use($completion, $group): void {
                if ($completion !== null) $completion(false);
                $this->debug("Something went wrong while adding the group %s", $group->getName());
            }
        );
    }

    public function removeGroup(PlayerRemainingGroup|Group $group, ?Closure $completion = null): void {
        $this->debug("Removing group %s", $group->getName());
        GroupSystem::getInstance()->getProvider()->removeGroupFromPlayer($this->username, $group)->onCompletion(
            function(bool $canRemove) use($group, $completion): void {
                $group = $group instanceof PlayerRemainingGroup ? $group->getName() : $group->getName();
                if ($canRemove && isset($this->groups[$group])) {
                    unset($this->groups[$group]);
                    $this->debug("Removed group %s", $group);
                } else {
                    $this->debug("Failed to remove group %s", $group);
                }

                if ($completion !== null) $completion($canRemove);
            },
            function() use($completion, $group): void {
                if ($completion !== null) $completion(false);
                $this->debug("Something went wrong while removing the group %s", $group->getName());
            }
        );
    }

    public function hasGroup(PlayerGroup|Group|string $group): bool {
        $group = $group instanceof PlayerGroup ? $group->getName() : ($group instanceof Group ? $group->getName() : $group);
        return isset($this->groups[$group]);
    }

    public function nextGroup(): void {
        $this->debug("Skipping current group");
        if (count($this->groups) == 0) {
            $this->setGroup(new PlayerGroup(GroupManager::getInstance()->getDefaultGroup()));
        } else {
            $group = $this->getNextHighestGroup();
            if ($group !== null) {
                $this->removeGroup($group);
                $this->setGroup($group->toPlayerGroup());
                $this->update();
            } else {
                $this->setGroup(new PlayerGroup(GroupManager::getInstance()->getDefaultGroup(), null));
            }
        }
    }

    public function getNextHighestGroup(): ?PlayerRemainingGroup {
        if (count($this->groups) == 0) return null;
        $groups = array_values($this->groups);
        usort($groups, function(PlayerRemainingGroup $a, PlayerRemainingGroup $b): int {
            if ($a->getGroup()->isHigher($b->getGroup())) return 1;
            else return -1;
        });
        return $groups[0] ?? null;
    }

    public function addPermission(PlayerPermission $permission): void {
        $this->debug("Adding permission %s", $permission->getPermission());
        if (in_array($permission, $this->permissions)) {
            $this->debug("Failed to add permission %s", $permission->getPermission());
            return;
        }

        $this->debug("Added permission %s", $permission);
        GroupSystem::getInstance()->getProvider()->addPermission($this->username, $permission);
        (new PermissionAddEvent($this->username, $permission))->call();
        $this->permissions[] = $permission;
        $this->reloadPermissions();
    }

    public function removePermission(PlayerPermission|string $permission): void {
        $permission = $permission instanceof PlayerPermission ? $permission : ($this->getPermission($permission) ?? PlayerPermission::fromString($permission));
        $this->debug("Removing permission %s", $permission->getPermission());
        if (!in_array($permission, $this->permissions)) {
            $this->debug("Failed to remove permission %s", $permission->getPermission());
            return;
        }

        $this->debug("Removed permission %s", $permission);
        GroupSystem::getInstance()->getProvider()->removePermission($this->username, $permission);
        (new PermissionRemoveEvent($this->username, $permission))->call();
        unset($this->permissions[array_search($permission, $this->permissions)]);
        $this->permissions = array_values($this->permissions);
        $this->reloadPermissions();
    }

    public function loadData(): void {
        $this->debug("Loading data");
        GroupSystem::getInstance()->getProvider()->getGroupOfPlayer($this->username)->onCompletion(
            function(PlayerGroup $group): void {
                $this->currentGroup = $group;
                GroupSystem::getInstance()->getProvider()->getGroupsOfPlayer($this->username, true)->onCompletion(
                    function(array $groups): void {
                        $this->groups = $groups;
                        GroupSystem::getInstance()->getProvider()->getPermissions($this->username, true)->onCompletion(
                            function(array $permissions): void {
                                $this->debug("Successfully loaded the data");
                                $this->permissions = $permissions;
                                $this->loaded = true;
                                $this->loadedSuccessful = true;
                                $this->invokeLoadClosures();
                            },
                            function(): void {
                                $this->debug("Failed to load permissions");
                                GroupSystem::getInstance()->getLogger()->emergency("§cFailed to load permissions from §e" . $this->username);
                                $this->loaded = true;
                                $this->invokeLoadClosures();
                            }
                        );
                    },
                    function(): void {
                        $this->debug("Failed to load groups");
                        GroupSystem::getInstance()->getLogger()->emergency("§cFailed to load groups from §e" . $this->username);
                        $this->loaded = true;
                        $this->invokeLoadClosures();
                    }
                );
            },
            function(): void {
                $this->debug("Failed to load group");
                GroupSystem::getInstance()->getLogger()->emergency("§cFailed to load group from §e" . $this->username);
                $this->currentGroup = new PlayerGroup(GroupManager::getInstance()->getDefaultGroup());
                $this->loaded = true;
                $this->invokeLoadClosures();
            }
        );
    }

    private function invokeLoadClosures(): void {
        foreach ($this->completionClosures as $closure) ($closure)($this->currentGroup, $this->groups, $this->permissions);
    }

    public function onLoad(Closure $closure): void {
        if ($this->loaded) {
            $closure($this->currentGroup, $this->groups, $this->permissions);
        } else $this->completionClosures[] = $closure;
    }

    public function update(): void {
        if ($this->currentGroup === null) return;
        $player = $this->getPlayer();
        if ($player !== null) {
            $this->debug("Updating");
            if ($this->attachment === null) $this->attachment = $player->addAttachment(GroupSystem::getInstance());
            ($ev = new PlayerUpdateEvent($player, str_replace(["{name}"], $player->getName(), $this->currentGroup->getGroup()->getNameTag()), str_replace(["{name}"], $player->getName(), $this->currentGroup->getGroup()->getDisplayName())))->call();
            if ($ev->isCancelled()) return;
            $player->setNameTag($ev->getNameTag());
            $player->setDisplayName($ev->getDisplayName());
            $this->reloadPermissions();
        }
    }

    public function reloadPermissions(): void {
        if ($this->attachment === null) return;
        $this->debug("Reloading permissions");
        $this->attachment->clearPermissions();
        foreach ($this->currentGroup->getGroup()->getPermissions() as $permission) $this->attachment->setPermission(new Permission($permission), true);
        foreach ($this->permissions as $permission) {
            if (!$permission->hasExpired()) $this->attachment->setPermission(new Permission($permission->getPermission()), true);
            else $this->removePermission($permission);
        }
    }

    public function tick(): void {
        if (!$this->loaded) return;
        $group = $this->currentGroup;
        if ($group->hasExpired()) {
            $this->nextGroup();
        } else {
            $current = $this->currentGroup;
            $default = GroupManager::getInstance()->getDefaultGroup();
            $next = $this->getNextHighestGroup();
            if (count($this->groups) > 0) {
                if ($current->getGroup()->getName() == $default->getName()) {
                    if ($current->getExpireDate() === null) {
                        if ($next->getGroup()->isHigher($current->getGroup())) {
                            $this->nextGroup();
                        }
                    }
                } else {
                    if ($next->getGroup()->isHigher($current->getGroup())) {
                        $this->removeGroup($next);
                        $this->addGroup($current->toRemainingGroup());
                        $this->setGroup($next->toPlayerGroup());
                    }
                }
            }
        }

        if (count(array_filter($this->permissions, fn(PlayerPermission $permission) => $permission->hasExpired())) > 0) $this->reloadPermissions();
    }

    public function getPermission(string $permission): ?PlayerPermission {
        foreach ($this->permissions as $permissionObj) if ($permissionObj->getPermission() == $permission) return $permissionObj;
        return null;
    }

    public function getPlayer(): ?Player {
        return Server::getInstance()->getPlayerExact($this->username);
    }

    public function isLoaded(): bool {
        return $this->loaded;
    }

    public function getGroup(): ?PlayerGroup {
        return $this->currentGroup;
    }

    public function getGroups(): array {
        return $this->groups;
    }

    public function getPermissions(): array {
        return $this->permissions;
    }

    public function getAttachment(): ?PermissionAttachment {
        return $this->attachment;
    }

    public function getUsername(): string {
        return $this->username;
    }

    public static function get(Player|string $player): Session {
        return SessionManager::getInstance()->get($player);
    }
}