<?php

namespace r3pt1s\groupsystem\provider\impl;

use Closure;
use Exception;
use JsonException;
use pocketmine\promise\Promise;
use pocketmine\promise\PromiseResolver;
use pocketmine\utils\Config;
use r3pt1s\groupsystem\migration\OldToNewConfigMigrator;
use r3pt1s\groupsystem\group\Group;
use r3pt1s\groupsystem\group\GroupManager;
use r3pt1s\groupsystem\GroupSystem;
use r3pt1s\groupsystem\player\perm\PlayerPermission;
use r3pt1s\groupsystem\player\PlayerGroup;
use r3pt1s\groupsystem\player\PlayerRemainingGroup;
use r3pt1s\groupsystem\provider\Provider;
use r3pt1s\groupsystem\util\Configuration;

final class JSONProvider implements Provider {

    private Config $file;

    public function __construct() {
        if (!file_exists(Configuration::getInstance()->getPlayersPath())) mkdir(Configuration::getInstance()->getPlayersPath());
        $this->file = new Config(Configuration::getInstance()->getGroupsPath() . "groups.json", Config::JSON);
        $this->file->enableJsonOption(JSON_UNESCAPED_UNICODE);
    }

    public function tryMigrate(): void {
        try {
            (new OldToNewConfigMigrator())->migrate();
        } catch (Exception) {}
    }

    public function createGroup(Group $group): void {
        if (!$this->file->exists($group->getName())) {
            $this->file->set($group->getName(), $group->toArray());
            try {
                $this->file->save();
            } catch (JsonException $e) {
                GroupSystem::getInstance()->getLogger()->logException($e);
            }
        }
    }

    public function removeGroup(Group $group): void {
        if ($this->file->exists($group->getName())) {
            $this->file->remove($group->getName());
            try {
                $this->file->save();
            } catch (JsonException $e) {
                GroupSystem::getInstance()->getLogger()->logException($e);
            }
        }
    }

    public function editGroup(Group $group, array $data): void {
        if ($this->file->exists($group->getName())) {
            $this->file->set($group->getName(), $data);
            try {
                $this->file->save();
            } catch (JsonException $e) {
                GroupSystem::getInstance()->getLogger()->logException($e);
            }
        }
    }

    public function checkGroup(string $name): Promise {
        $resolver = new PromiseResolver();
        $resolver->resolve($this->file->exists($name));
        return $resolver->getPromise();
    }

    public function getGroupByName(string $name): Promise {
        $resolver = new PromiseResolver();

        if ($this->file->exists($name) && ($group = Group::fromArray($this->file->get($name, []))) !== null) {
            $resolver->resolve($group);
        } else $resolver->reject();

        return $resolver->getPromise();
    }

    public function getAllGroups(): Promise {
        $groups = [];
        $resolver = new PromiseResolver();


        foreach ($this->file->getAll() as $name => $groupData) {
            if (!isset($groupData["group"])) $groupData["group"] = $name;
            if (($group = Group::fromArray($groupData)) !== null) {
                $groups[$group->getName()] = $group;
            }
        }

        $resolver->resolve($groups);
        return $resolver->getPromise();
    }

    public function createPlayer(string $username, ?Closure $completion = null, ?array $customData = null): void {
        $file = $this->getPlayerFile($username);
        $file->setAll([
            "group" => $customData["group"] ?? GroupManager::getInstance()->getDefaultGroup()->getName(),
            "expire" => $customData["expire"] ?? null,
            "groups" => $customData["groups"] ?? [],
            "permissions" => $customData["permissions"] ?? [],
        ]);

        try {
            $file->save();
            $completion(true);
        } catch (JsonException $e) {
            $completion(false);
            GroupSystem::getInstance()->getLogger()->logException($e);
        }
    }

    public function setGroup(string $username, PlayerGroup $group): void {
        $file = $this->getPlayerFile($username);
        foreach ($group->toArray() as $k => $v) $file->set($k, $v);
        try {
            $file->save();
        } catch (JsonException $e) {
            GroupSystem::getInstance()->getLogger()->logException($e);
        }
    }

    public function addGroupToPlayer(string $username, PlayerRemainingGroup $group): Promise {
        $resolver = new PromiseResolver();
        $groups = $this->getGroupsOfPlayer($username);
        $groups->onCompletion(function(array $groups) use($username, $group, $resolver): void {
            if (isset($groups[$group->getGroup()->getName()])) {
                $resolver->resolve(false);
                return;
            }

            $groups[$group->getGroup()->getName()] = $group->toArray();
            $file = $this->getPlayerFile($username);
            $file->set("groups", $groups);
            try {
                $file->save();
                $resolver->resolve(true);
            } catch (JsonException $e) {
                $resolver->resolve(false);
                GroupSystem::getInstance()->getLogger()->logException($e);
            }
        }, function() use($resolver): void {
            $resolver->reject();
        });

        return $resolver->getPromise();
    }

    public function removeGroupFromPlayer(string $username, PlayerRemainingGroup|Group $group): Promise {
        $group = $group instanceof PlayerRemainingGroup ? $group->getGroup()->getName() : $group->getName();
        $resolver = new PromiseResolver();
        $groups = $this->getGroupsOfPlayer($username);
        $groups->onCompletion(function(array $groups) use($username, $group, $resolver): void {
            if (!isset($groups[$group])) {
                $resolver->resolve(false);
                return;
            }

            unset($groups[$group]);
            $file = $this->getPlayerFile($username);
            $file->set("groups", $groups);
            try {
                $file->save();
                $resolver->resolve(true);
            } catch (JsonException $e) {
                $resolver->resolve(false);
                GroupSystem::getInstance()->getLogger()->logException($e);
            }
        }, function() use($resolver): void {
            $resolver->reject();
        });

        return $resolver->getPromise();
    }

    public function hasGroup(string $username, PlayerRemainingGroup|Group|string $group): Promise {
        $group = ($group instanceof PlayerRemainingGroup ? $group->getGroup()->getName() : ($group instanceof Group ? $group->getName() : $group));
        $resolver = new PromiseResolver();

        $groups = $this->getGroupsOfPlayer($username);
        $groups->onCompletion(function(array $groups) use($username, $group, $resolver): void {
            $resolver->resolve(isset($groups[$group]));
        }, function() use($resolver): void {
            $resolver->reject();
        });

        return $resolver->getPromise();
    }

    public function getGroupOfPlayer(string $username): Promise {
        $resolver = new PromiseResolver();

        $file = $this->getPlayerFile($username);
        if (($group = PlayerGroup::fromArray($file->getAll())) !== null) {
            $resolver->resolve($group);
        } else $resolver->reject();

        return $resolver->getPromise();
    }

    public function getGroupsOfPlayer(string $username, bool $asInstance = false): Promise {
        $groups = [];
        $resolver = new PromiseResolver();

        foreach ($this->getPlayerFile($username)->get("groups", []) as $groupData) {
            if (($group = PlayerRemainingGroup::fromArray($groupData)) !== null) {
                $groups[$group->getGroup()->getName()] = ($asInstance ? $group : $groupData);
            }
        }

        $resolver->resolve($groups);
        return $resolver->getPromise();
    }

    public function addPermission(string $username, PlayerPermission $permission): void {
        $permissions = $this->getPermissions($username);
        $permissions->onCompletion(function(array $permissions) use($username, $permission): void {
            if (!in_array($permission, $permissions)) $permissions[] = $permission->toString();
            $file = $this->getPlayerFile($username);
            $file->set("permissions", array_values($permissions));
            try {
                $file->save();
            } catch (JsonException $e) {
                GroupSystem::getInstance()->getLogger()->logException($e);
            }
        }, function(): void {});
    }

    public function removePermission(string $username, PlayerPermission|string $permission): void {
        $permissions = $this->getPermissions($username);
        $permissions->onCompletion(function(array $permissions) use($username, $permission): void {
            $permission = $permission instanceof PlayerPermission ? $permission->toString() : $permission;
            if (in_array($permission, $permissions)) unset($permissions[array_search($permission, $permissions)]);
            $file = $this->getPlayerFile($username);
            $file->set("permissions", array_values($permissions));
            try {
                $file->save();
            } catch (JsonException $e) {
                GroupSystem::getInstance()->getLogger()->logException($e);
            }
        }, function(): void {});
    }

    public function getPermissions(string $username, bool $asInstance = false): Promise {
        $permissions = [];
        $resolver = new PromiseResolver();

        foreach ($this->getPlayerFile($username)->get("permissions", []) as $permission) {
            $permissions[] = ($asInstance ? PlayerPermission::fromString($permission) : $permission);
        }

        $resolver->resolve($permissions);
        return $resolver->getPromise();
    }

    public function checkPlayer(string $username): Promise {
        $resolver = new PromiseResolver();
        $resolver->resolve(file_exists(Configuration::getInstance()->getPlayersPath() . $username . ".json"));
        return $resolver->getPromise();
    }

    private function getPlayerFile(string $username): Config {
        return new Config(Configuration::getInstance()->getPlayersPath() . $username . ".json", Config::JSON);
    }

    public function getFile(): ?Config {
        return $this->file;
    }
}