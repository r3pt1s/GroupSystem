<?php

namespace r3pt1s\groupsystem\session;

use Closure;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionAttachment;
use pocketmine\player\Player;
use pocketmine\Server;
use PrefixedLogger;
use r3pt1s\groupsystem\event\player\PlayerGroupAddEvent;
use r3pt1s\groupsystem\event\player\PlayerGroupSetEvent;
use r3pt1s\groupsystem\event\player\PlayerGroupSkipEvent;
use r3pt1s\groupsystem\event\player\PlayerPermissionGrantEvent;
use r3pt1s\groupsystem\event\player\PlayerPermissionRevokeEvent;
use r3pt1s\groupsystem\event\player\PlayerUpdateEvent;
use r3pt1s\groupsystem\group\Group;
use r3pt1s\groupsystem\group\GroupManager;
use r3pt1s\groupsystem\GroupSystem;
use r3pt1s\groupsystem\player\perm\PlayerPermission;
use r3pt1s\groupsystem\player\PlayerGroup;
use r3pt1s\groupsystem\player\PlayerRemainingGroup;
use r3pt1s\groupsystem\util\BatchPromise;
use r3pt1s\groupsystem\util\Configuration;

final class Session {

    # todo: revamp this mess wth

    private PrefixedLogger $logger;
    private int $creationTick;
    private int $loadingTimeoutTick;
    private int $nextGroupCheckTick;
    private bool $initialized = false;
    private bool $failed = false;
    private array $completionClosures = [];
    private ?PlayerGroup $currentGroup = null;
    /** @var array<PlayerRemainingGroup> */
    private array $groups = [];
    /** @var array<PlayerPermission> */
    private array $permissions = [];
    private ?PermissionAttachment $attachment = null;

    public function __construct(private readonly string $username) {
        $this->logger = new PrefixedLogger(GroupSystem::getInstance()->getLogger(), $this->username);
        $this->creationTick = Server::getInstance()->getTick();
        $this->loadingTimeoutTick = Server::getInstance()->getTick() + (Configuration::getInstance()->getSessionTimeout() * 20);
        $this->nextGroupCheckTick = $this->creationTick + 20;
        $this->loadData()->then(function (): void {
            $this->debug("Successfully loaded data");
            $this->initialized = true;
            $this->invokeLoadClosures();
        })->failure(fn() => $this->failed = true);
    }

    public function debug(string $message, array $params = [], bool $force = false): void {
        $finalMessage = sprintf($message, ...$params);
        if ($force) $this->logger->notice($finalMessage);
        else $this->logger->debug($finalMessage);

        if (!Configuration::getInstance()->isInGameDebugging()) return;
        foreach (array_filter(Server::getInstance()->getOnlinePlayers(), fn(Player $player) => $player->hasPermission(DefaultPermissions::ROOT_OPERATOR)) as $p) {
            $p->sendMessage("§8[§6DEBUG§7/§c" . $this->username . "§8:§e" . date("H:i:s") . "§8] §f" . $finalMessage);
        }
    }

    public function loadData(): BatchPromise {
        $this->debug("Loading data");
        $promise = new BatchPromise(3);
        GroupSystem::getInstance()->getProvider()->getGroupOfPlayer($this->username)->onCompletion(
            function (PlayerGroup $group) use ($promise): void {
                $this->currentGroup = $group;
                $promise->accept();
            },
            function () use ($promise): void {
                $this->debug("Failed to load group");
                $promise->reject();
            }
        );

        GroupSystem::getInstance()->getProvider()->getGroupsOfPlayer($this->username, true)->onCompletion(
            function (array $groups) use($promise): void {
                $this->groups = $groups;
                $promise->accept();
            },
            function () use ($promise): void {
                $this->debug("Failed to load groups");
                $promise->reject();
            }
        );

        GroupSystem::getInstance()->getProvider()->getPermissions($this->username, true)->onCompletion(
            function (array $permissions) use($promise): void {
                $this->permissions = $permissions;
                $promise->accept();
            },
            function () use ($promise): void {
                $this->debug("Failed to load permissions");
                $promise->reject();
            }
        );

        return $promise;
    }

    public function markAsFailed(): void {
        if ($this->initialized || $this->failed) return;
        $this->failed = true;
        $this->initialized = false;
    }

    private function invokeLoadClosures(): void {
        foreach ($this->completionClosures as $closure) ($closure)($this->currentGroup, $this->groups, $this->permissions);
    }

    public function onLoad(Closure $closure): void {
        if ($this->initialized) {
            $closure($this->currentGroup, $this->groups, $this->permissions);
        } else $this->completionClosures[] = $closure;
    }

    public function setGroup(PlayerGroup $group): void {
        $this->debug("Setting the group to %s", [$group->getName()]);
        GroupSystem::getInstance()->getProvider()->setGroup($this->username, $group);
        ($ev = new PlayerGroupSetEvent($this->username, $group))->call();
        if ($ev->isCancelled()) {
            $this->logger->notice("Cancelled the current group to be set to {$group->getName()}: Event cancelled");
            return;
        }

        $this->currentGroup = $group;
        $this->update();
    }

    public function addGroup(PlayerRemainingGroup $group, ?Closure $completion = null): void {
        $this->debug("Adding group %s", [$group->getName()]);
        ($ev = new PlayerGroupAddEvent($this->username, $group))->call();
        if ($ev->isCancelled()) {
            $this->logger->notice("Cancelled the addition of the group {$group->getName()} to this session: Event cancelled");
            return;
        }

        GroupSystem::getInstance()->getProvider()->addGroupToPlayer($this->username, $group)->onCompletion(
            function(bool $canAdd) use($group, $completion): void {
                if ($canAdd) {
                    $this->groups[$group->getGroup()->getName()] = $group;
                    $this->debug("Added group %s", [$group->getName()]);
                } else {
                    $this->debug("Failed to add group %s", [$group->getName()]);
                }

                if ($completion !== null) $completion($canAdd);
            },
            function() use($completion, $group): void {
                if ($completion !== null) $completion(false);
                $this->debug("Something went wrong while adding the group %s", [$group->getName()]);
            }
        );
    }

    public function removeGroup(PlayerRemainingGroup|Group $group, ?Closure $completion = null): void {
        $this->debug("Removing group %s", [$group->getName()]);
        ($ev = new PlayerGroupAddEvent($this->username, $group))->call();
        if ($ev->isCancelled()) {
            $this->logger->notice("Cancelled the removal of the group {$group->getName()} from this session: Event cancelled");
            return;
        }

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
                $this->debug("Something went wrong while removing the group %s", [$group->getName()]);
            }
        );
    }

    public function hasGroup(PlayerGroup|Group|string $group): bool {
        $group = $group instanceof PlayerGroup ? $group->getName() : ($group instanceof Group ? $group->getName() : $group);
        return isset($this->groups[$group]);
    }

    public function nextGroup(): void {
        $this->debug("Skipping current group");
        ($ev = new PlayerGroupSkipEvent($this->username, $this->currentGroup, $this->getNextHighestGroup() ?? GroupManager::getInstance()->getDefaultGroup()))->call();
        if ($ev->isCancelled()) {
            $this->logger->notice("Cancelled the skip of current group for this session: Event cancelled");
            return;
        }

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

    public function updatePermission(PlayerPermission $permission): void {
        $this->debug("Updating permission %s", [$permission->getPermission()]);
        foreach ($this->permissions as $i => $actualPermission) {
            if ($actualPermission->getPermission() == $permission->getPermission()) {
                unset($this->permissions[$i]);
            }
        }

        $this->permissions = array_values($this->permissions);

        GroupSystem::getInstance()->getProvider()->updatePermission($this->username, $permission);
        if ($permission->isGranted()) ($ev = new PlayerPermissionGrantEvent($this->username, $permission))->call();
        else ($ev = new PlayerPermissionRevokeEvent($this->username, $permission))->call();
        if ($ev->isCancelled()) {
            $this->logger->notice("Cancelled the addition of permission {$permission->getPermission()} for this session: Event cancelled");
            return;
        }

        $this->permissions[] = $permission;
        $this->reloadPermissions();
    }

    public function removePermission(PlayerPermission|string $permission): void {
        $permission = $permission instanceof PlayerPermission ? $permission : ($this->getPermission($permission) ?? PlayerPermission::read($permission));
        $this->debug("Removing permission %s", [$permission->getPermission()]);
        if (!in_array($permission, $this->permissions)) {
            $this->debug("Failed to remove permission %s", [$permission->getPermission()]);
            return;
        }

        GroupSystem::getInstance()->getProvider()->removePermission($this->username, $permission);
        unset($this->permissions[array_search($permission, $this->permissions)]);
        $this->permissions = array_values($this->permissions);
        $this->reloadPermissions();
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

        foreach ($this->currentGroup->getGroup()->getGrantedPermissions() as $permission) $this->attachment->setPermission(new Permission($permission), true);
        foreach ($this->currentGroup->getGroup()->getRevokedPermissions() as $permission) $this->attachment->setPermission(new Permission($permission), false);

        foreach ($this->permissions as $permission) {
            if (!$permission->hasExpired()) $this->attachment->setPermission(new Permission($permission->getPermission()), $permission->isGranted());
            else $this->removePermission($permission);
        }
    }

    public function tick(): void {
        if (Server::getInstance()->getTick() >= $this->nextGroupCheckTick) {
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
    }

    public function getPermission(string $permission): ?PlayerPermission {
        foreach ($this->permissions as $permissionObj) if ($permissionObj->getPermission() == $permission) return $permissionObj;
        return null;
    }

    public function getPlayer(): ?Player {
        return Server::getInstance()->getPlayerExact($this->username);
    }

    public function isInitialized(): bool {
        return $this->initialized;
    }

    public function getLogger(): PrefixedLogger {
        return $this->logger;
    }

    public function getCreationTick(): int {
        return $this->creationTick;
    }

    public function getLoadingTimeoutTick(): int {
        return $this->loadingTimeoutTick;
    }

    public function getNextGroupCheckTick(): int {
        return $this->nextGroupCheckTick;
    }

    public function hasFailed(): bool {
        return $this->failed;
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