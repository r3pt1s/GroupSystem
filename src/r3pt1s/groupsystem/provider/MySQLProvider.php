<?php

namespace r3pt1s\groupsystem\provider;

use Closure;
use pocketmine\promise\Promise;
use pocketmine\promise\PromiseResolver;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use poggit\libasynql\SqlError;
use r3pt1s\groupsystem\convert\ConfigToMySQLConverter;
use r3pt1s\groupsystem\group\GroupManager;
use r3pt1s\groupsystem\GroupSystem;
use r3pt1s\groupsystem\util\Configuration;
use r3pt1s\groupsystem\group\Group;
use r3pt1s\groupsystem\player\PlayerGroup;
use r3pt1s\groupsystem\player\PlayerRemainingGroup;
use r3pt1s\groupsystem\util\Utils;

final class MySQLProvider implements Provider {

    private DataConnector $connector;

    public function __construct() {
        $mysql = Utils::renameAndRemove(Configuration::getInstance()->getMysql(), ["database"], ["schema"]);
        $this->connector = libasynql::create(GroupSystem::getInstance(), [
            "type" => "mysql",
            "mysql" => $mysql
        ], [
            "mysql" => "mysql.sql"
        ], true);

        $this->connector->executeGeneric("table.groups");
        $this->connector->executeGeneric("table.players");
        $this->connector->waitAll();
    }

    public function tryConvert(): void {
        (new ConfigToMySQLConverter())->convert();
    }

    public function createGroup(Group $group): void {
        $this->connector->executeInsert("groups.add", $group->buildMysqlInsertArgs());
    }

    public function removeGroup(Group $group): void {
        $this->connector->executeGeneric("groups.remove", ["name" => $group->getName()]);
    }

    public function editGroup(Group $group, array $data): void {
        $this->connector->executeChange("groups.edit", $group->buildMysqlInsertArgs());
    }

    public function checkGroup(string $name): Promise {
        $resolver = new PromiseResolver();
        $this->connector->executeSelect("groups.check", [
            "name" => $name,
        ], fn (array $rows) => $resolver->resolve((bool) array_values($rows[0])[0]), fn(SqlError $error) => $resolver->reject());
        return $resolver->getPromise();
    }

    public function getGroupByName(string $name): Promise {
        $resolver = new PromiseResolver();

        $this->connector->executeSelect("groups.get", [
            "name" => $name
        ], function (array $rows) use($resolver): void {
            if (count($rows) == 0) {
                $resolver->reject();
                return;
            }

            $rows[0]["permissions"] = json_decode($rows[0]["permissions"], true);
            if (($group = Group::fromArray($rows[0])) !== null) {
                $resolver->resolve($group);
            } else $resolver->reject();
        }, fn(SqlError $error) => $resolver->reject());

        return $resolver->getPromise();
    }

    public function getAllGroups(): Promise {
        $resolver = new PromiseResolver();

        $this->connector->executeSelect("groups.getAll", [], function (array $rows) use($resolver): void {
            if (count($rows) == 0) {
                $resolver->resolve([]);
                return;
            }

            $groups = [];
            foreach ($rows as $data) {
                $data["permissions"] = json_decode($data["permissions"], true);
                if (($group = Group::fromArray($data)) !== null) {
                    $groups[$group->getName()] = $group;
                }
            }

            $resolver->resolve($groups);
        }, fn(SqlError $error) => $resolver->reject());

        return $resolver->getPromise();
    }

    public function createPlayer(string $username, ?Closure $completion = null, ?array $customData = null): void {
        $this->connector->executeInsert("player.create", [
            "username" => $username,
            "group" => $customData["group"] ?? GroupManager::getInstance()->getDefaultGroup()->getName(),
            "expire" => $customData["expire"] ?? null,
            "groups" => json_encode($customData["permissions"] ?? []),
            "permissions" => json_encode($customData["permissions"] ?? []),
        ], fn() => $completion(true), fn(SqlError $error) => $completion(false));
    }

    public function setGroup(string $username, PlayerGroup $group): void {
        $this->connector->executeChange("player.setGroup", array_merge(
            ["username" => $username], $group->toArray()
        ));
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
            $this->connector->executeChange("player.updateGroups", [
                "username" => $username, "groups" => json_encode($groups)
            ], fn (int $affectedRows) => $resolver->resolve(true), fn(SqlError $error) => $resolver->reject());
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
            $this->connector->executeChange("player.updateGroups", [
                "username" => $username, "groups" => json_encode($groups)
            ], fn (int $affectedRows) => $resolver->resolve(true), fn(SqlError $error) => $resolver->reject());
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

        $this->connector->executeSelect("player.getGroup", [
            "username" => $username
        ], function (array $rows) use($resolver): void {
            if (count($rows) == 0) {
                $resolver->reject();
                return;
            }

            if (($group = PlayerGroup::fromArray($rows[0])) !== null) {
                $resolver->resolve($group);
            } else $resolver->reject();
        }, fn(SqlError $error) => $resolver->reject());

        return $resolver->getPromise();
    }

    public function getGroupsOfPlayer(string $username, bool $asInstance = false): Promise {
        $resolver = new PromiseResolver();

        $this->connector->executeSelect("player.getGroups", [
            "username" => $username
        ], function (array $rows) use($resolver, $asInstance): void {
            if (count($rows) == 0) {
                $resolver->resolve([]);
                return;
            }

            $data = json_decode($rows[0]["groups"], true);
            $groups = [];
            foreach ($data as $groupData) {
                if (($group = PlayerRemainingGroup::fromArray($groupData)) !== null) {
                    $groups[$group->getGroup()->getName()] = ($asInstance ? $group : $groupData);
                }
            }

            $resolver->resolve($groups);
        }, fn(SqlError $error) => $resolver->reject());

        return $resolver->getPromise();
    }

    public function addPermission(string $username, string $permission): void {
        $permissions = $this->getPermissions($username);
        $permissions->onCompletion(function(array $permissions) use($username, $permission): void {
            if (!in_array($permission, $permissions)) $permissions[] = $permission;
            $this->connector->executeChange("player.updatePermissions", [
                "username" => $username, "permissions" => json_encode($permissions)
            ]);
        }, function(): void {});
    }

    public function removePermission(string $username, string $permission): void {
        $permissions = $this->getPermissions($username);
        $permissions->onCompletion(function(array $permissions) use($username, $permission): void {
            if (in_array($permission, $permissions)) unset($permissions[array_search($permission, $permissions)]);
            $this->connector->executeChange("player.updatePermissions", [
                "username" => $username, "permissions" => json_encode($permissions)
            ]);
        }, function(): void {});
    }

    public function getPermissions(string $username): Promise {
        $resolver = new PromiseResolver();

        $this->connector->executeSelect("player.getPermissions", [
            "username" => $username
        ], function (array $rows) use($resolver): void {
            if (count($rows) == 0) {
                $resolver->resolve([]);
                return;
            }

            $resolver->resolve(json_decode($rows[0]["permissions"], true));
        });

        return $resolver->getPromise();
    }

    public function checkPlayer(string $username): Promise {
        $resolver = new PromiseResolver();
        $this->connector->executeSelect("player.check", [
            "username" => $username,
        ], fn (array $rows) => $resolver->resolve((bool) array_values($rows[0])[0]), fn(SqlError $error) => $resolver->reject());
        return $resolver->getPromise();
    }
}