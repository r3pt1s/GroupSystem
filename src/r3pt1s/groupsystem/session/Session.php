<?php

namespace r3pt1s\groupsystem\session;

use Closure;
use JetBrains\PhpStorm\Pure;
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
use r3pt1s\groupsystem\player\PlayerGroup;
use r3pt1s\groupsystem\player\PlayerRemainingGroup;

class Session {

    private bool $loaded = false;
    private bool $loadedSuccessful = false;
    private array $completionClosures = [];
    private ?PlayerGroup $currentGroup = null;
    private array $groups = [];
    private array $permissions = [];
    private ?PermissionAttachment $attachment = null;

    public function __construct(private readonly string $username) {
        $this->loadData();
    }

    public function setGroup(PlayerGroup $group): void {
        GroupSystem::getInstance()->getProvider()->setGroup($this->username, $group);
        (new GroupSetEvent($this->username, $group))->call();
        $this->currentGroup = $group;
        $this->update();
    }

    public function addGroup(PlayerRemainingGroup $group, ?Closure $completion = null): void {
        GroupSystem::getInstance()->getProvider()->addGroupToPlayer($this->username, $group)->onCompletion(
            function(bool $canAdd) use($group, $completion): void {
                if ($canAdd) $this->groups[$group->getGroup()->getName()] = $group;
                if ($completion !== null) $completion($canAdd);
            },
            function() use($completion): void {
                if ($completion !== null) $completion(false);
            }
        );
    }

    public function removeGroup(PlayerRemainingGroup|Group $group, ?Closure $completion = null): void {
        GroupSystem::getInstance()->getProvider()->removeGroupFromPlayer($this->username, $group)->onCompletion(
            function(bool $canRemove) use($group, $completion): void {
                $group = $group instanceof PlayerRemainingGroup ? $group->getGroup()->getName() : $group->getName();
                if ($canRemove && isset($this->groups[$group])) unset($this->groups[$group]);
                if ($completion !== null) $completion($canRemove);
            },
            function() use($completion): void {
                if ($completion !== null) $completion(false);
            }
        );
    }

    #[Pure] public function hasGroup(PlayerGroup|Group|string $group): bool {
        $group = $group instanceof PlayerGroup ? $group->getGroup()->getName() : ($group instanceof Group ? $group->getName() : $group);
        return isset($this->groups[$group]);
    }

    public function nextGroup(): void {
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

    public function addPermission(string $permission): void {
        if (in_array($permission, $this->permissions)) return;
        GroupSystem::getInstance()->getProvider()->addPermission($this->username, $permission);
        (new PermissionAddEvent($this->username, $permission))->call();
        $this->permissions[] = $permission;
        $this->reloadPermissions();
    }

    public function removePermission(string $permission): void {
        if (!in_array($permission, $this->permissions)) return;
        GroupSystem::getInstance()->getProvider()->removePermission($this->username, $permission);
        (new PermissionRemoveEvent($this->username, $permission))->call();
        unset($this->permissions[array_search($permission, $this->permissions)]);
        $this->reloadPermissions();
    }

    public function loadData(): void {
        GroupSystem::getInstance()->getProvider()->getGroupOfPlayer($this->username)->onCompletion(
            function(PlayerGroup $group): void {
                $this->currentGroup = $group;
                GroupSystem::getInstance()->getProvider()->getGroupsOfPlayer($this->username, true)->onCompletion(
                    function(array $groups): void {
                        $this->groups = $groups;
                        GroupSystem::getInstance()->getProvider()->getPermissions($this->username)->onCompletion(
                            function(array $permissions): void {
                                $this->permissions = $permissions;
                                $this->loaded = true;
                                $this->loadedSuccessful = true;
                                $this->invokeLoadClosures();
                            },
                            function(): void {
                                GroupSystem::getInstance()->getLogger()->emergency("§cFailed to load permissions from §e" . $this->username);
                                $this->loaded = true;
                                $this->invokeLoadClosures();
                            }
                        );
                    },
                    function(): void {
                        GroupSystem::getInstance()->getLogger()->emergency("§cFailed to load groups from §e" . $this->username);
                        $this->loaded = true;
                        $this->invokeLoadClosures();
                    }
                );
            },
            function(): void {
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
        $this->completionClosures[] = $closure;
        if ($this->loaded) {
            $closure($this->currentGroup, $this->groups, $this->permissions);
        }
    }

    public function update(): void {
        if ($this->currentGroup === null) return;
        $player = $this->getPlayer();
        if ($player !== null) {
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
        $this->attachment->clearPermissions();
        foreach ($this->currentGroup->getGroup()->getPermissions() as $permission) $this->attachment->setPermission(new Permission($permission), true);
        foreach ($this->permissions as $permission) $this->attachment->setPermission(new Permission($permission), true);
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