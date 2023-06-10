<?php

namespace r3pt1s\groupsystem\provider;

use pocketmine\promise\Promise;
use pocketmine\promise\PromiseResolver;
use r3pt1s\groupsystem\convert\ConfigToMySQLConverter;
use r3pt1s\groupsystem\group\GroupManager;
use r3pt1s\groupsystem\util\AsyncExecutor;
use r3pt1s\groupsystem\util\Database;
use r3pt1s\groupsystem\group\Group;
use r3pt1s\groupsystem\player\PlayerGroup;
use r3pt1s\groupsystem\player\PlayerRemainingGroup;

final class MySQLProvider implements Provider {

    public function __construct() {
        AsyncExecutor::execute(fn(Database $database) => $database->initializeTable(), fn() => (new ConfigToMySQLConverter())->convert());
    }

    public function createGroup(Group $group): void {
        $groupData = $group->toArray();
        $groupData["permissions"] = json_encode($groupData["permissions"]);
        AsyncExecutor::execute(function(Database $database) use($groupData): void {
            if (!$database->has("groups", ["name" => $groupData["name"]])) {
                $database->insert("groups", $groupData);
            }
        });
    }

    public function removeGroup(Group $group): void {
        $groupData = $group->toArray();
        AsyncExecutor::execute(function(Database $database) use($groupData): void {
            if ($database->has("groups", ["name" => $groupData["name"]])) {
                $database->delete("groups", ["name" => $groupData["name"]]);
            }
        });
    }

    public function editGroup(Group $group, array $data): void {
        $groupData = $group->toArray();
        if (isset($data["permissions"])) $data["permissions"] = json_encode($data["permissions"]);
        AsyncExecutor::execute(function(Database $database) use($groupData, $data): void {
            if ($database->has("groups", ["name" => $groupData["name"]])) {
                $database->update("groups", $data, ["name" => $groupData["name"]]);
            }
        });
    }

    public function checkGroup(string $name): Promise {
        /** @var PromiseResolver<bool> */
        $resolver = new PromiseResolver();
        AsyncExecutor::execute(fn(Database $database) => $database->has("groups", ["name" => $name]), fn(bool $is) => $resolver->resolve($is));
        return $resolver->getPromise();
    }

    public function getGroupByName(string $name): Promise {
        /** @var PromiseResolver<Group> */
        $resolver = new PromiseResolver();

        AsyncExecutor::execute(fn(Database $database) => $database->get("groups", "*", ["name" => $name]), function(?array $data) use($resolver, $name): void {
            if (!is_array($data)) {
                $resolver->reject();
                return;
            }

            $data["permissions"] = json_decode($data["permissions"]);
            if (($group = Group::fromArray($data)) !== null) {
                $resolver->resolve($group);
            } else $resolver->reject();
        });

        return $resolver->getPromise();
    }

    public function getAllGroups(): Promise {
        /** @var PromiseResolver<array<Group>> */
        $resolver = new PromiseResolver();

        AsyncExecutor::execute(fn(Database $database) => $database->select("groups", ["name", "display_name", "name_tag", "chat_format", "color_code", "permissions"], "*"), function(?array $data) use($resolver): void {
            if (!is_array($data)) {
                $resolver->reject();
                return;
            }

            $groups = [];
            foreach ($data as $groupData) {
                $groupData["permissions"] = json_decode($groupData["permissions"], true);
                if (($group = Group::fromArray($groupData)) !== null) {
                    $groups[$group->getName()] = $group;
                }
            }

            $resolver->resolve($groups);
        });

        return $resolver->getPromise();
    }

    public function createPlayer(string $username, ?\Closure $completion = null): void {
        $defaultGroup = GroupManager::getInstance()->getDefaultGroup()->getName();
        AsyncExecutor::execute(fn(Database $database) => $database->insert("players", ["username" => $username, "group" => $defaultGroup, "groups" => json_encode([]), "permissions" => json_encode([])]), fn() => $completion(true));
    }

    public function setGroup(string $username, PlayerGroup $group): void {
        $groupData = $group->toArray();
        AsyncExecutor::execute(fn(Database $database) => $database->update("players", $groupData));
    }

    public function addGroupToPlayer(string $username, PlayerRemainingGroup $group): Promise {
        /** @var PromiseResolver<bool> */
        $resolver = new PromiseResolver();
        $groups = $this->getGroupsOfPlayer($username);
        $groups->onCompletion(function(array $groups) use($username, $group, $resolver): void {
            if (isset($groups[$group->getGroup()->getName()])) {
                $resolver->resolve(false);
                return;
            }

            $groups[$group->getGroup()->getName()] = $group->toArray();
            AsyncExecutor::execute(fn(Database $database) => $database->update("players", ["groups" => json_encode($groups)], ["username" => $username]));
            $resolver->resolve(true);
        }, function() use($resolver): void {
            $resolver->reject();
        });

        return $resolver->getPromise();
    }

    public function removeGroupFromPlayer(string $username, PlayerRemainingGroup|Group $group): Promise {
        $group = $group instanceof PlayerRemainingGroup ? $group->getGroup()->getName() : $group->getName();
        /** @var PromiseResolver<bool> */
        $resolver = new PromiseResolver();
        $groups = $this->getGroupsOfPlayer($username);
        $groups->onCompletion(function(array $groups) use($username, $group, $resolver): void {
            if (!isset($groups[$group])) {
                $resolver->resolve(false);
                return;
            }

            unset($groups[$group]);
            AsyncExecutor::execute(fn(Database $database) => $database->update("players", ["groups" => json_encode($groups)], ["username" => $username]));
            $resolver->resolve(true);
        }, function() use($resolver): void {
            $resolver->reject();
        });

        return $resolver->getPromise();
    }

    public function hasGroup(string $username, PlayerRemainingGroup|Group|string $group): Promise {
        $group = ($group instanceof PlayerRemainingGroup ? $group->getGroup()->getName() : ($group instanceof Group ? $group->getName() : $group));
        /** @var PromiseResolver<bool> */
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
        /** @var PromiseResolver<PlayerGroup> */
        $resolver = new PromiseResolver();

        AsyncExecutor::execute(fn(Database $database) => $database->get("players", ["group", "expire"], ["username" => $username]), function(?array $data) use($username, $resolver): void {
            if (!is_array($data)) {
                $resolver->reject();
                return;
            }

            if (($group = PlayerGroup::fromArray($data)) !== null) {
                $resolver->resolve($group);
            } else $resolver->reject();
        });

        return $resolver->getPromise();
    }

    public function getGroupsOfPlayer(string $username, bool $asInstance = false): Promise {
        /** @var PromiseResolver<array<PlayerRemainingGroup>> */
        $resolver = new PromiseResolver();

        AsyncExecutor::execute(function(Database $database) use($username): ?array {
            $groups = $database->get("players", ["groups"], ["username" => $username]);
            if (is_array($groups)) {
                if (is_string($groups["groups"])) {
                    if (is_array(($groups = @json_decode($groups["groups"], true)))) return $groups;
                }
            }
            return null;
        }, function(?array $data) use ($username, $resolver, $asInstance): void {
            if (!is_array($data)) {
                $resolver->reject();
                return;
            }

            $groups = [];
            foreach ($data as $groupData) {
                if (($group = PlayerRemainingGroup::fromArray($groupData)) !== null) {
                    $groups[$group->getGroup()->getName()] = ($asInstance ? $group : $groupData);
                }
            }

            $resolver->resolve($groups);
        });

        return $resolver->getPromise();
    }

    public function addPermission(string $username, string $permission): void {
        $permissions = $this->getPermissions($username);
        $permissions->onCompletion(function(array $permissions) use($username, $permission): void {
            if (!in_array($permission, $permissions)) $permissions[] = $permission;
            AsyncExecutor::execute(fn(Database $database) => $database->update("players", ["permissions" => json_encode($permissions)], ["username" => $username]));
        }, function(): void {});
    }

    public function removePermission(string $username, string $permission): void {
        $permissions = $this->getPermissions($username);
        $permissions->onCompletion(function(array $permissions) use($username, $permission): void {
            if (in_array($permission, $permissions)) unset($permissions[array_search($permission, $permissions)]);
            AsyncExecutor::execute(fn(Database $database) => $database->update("players", ["permissions" => json_encode($permissions)], ["username" => $username]));
        }, function(): void {});
    }

    public function getPermissions(string $username): Promise {
        /** @var PromiseResolver<array<string>> */
        $resolver = new PromiseResolver();

        AsyncExecutor::execute(function(Database $database) use($username): ?array {
            $permissions = $database->get("players", ["permissions"], ["username" => $username]);
            if (is_array($permissions)) {
                if (is_string($permissions["permissions"])) {
                    if (is_array(($permissions = @json_decode($permissions["permissions"], true)))) return $permissions;
                }
            }
            return null;
        }, function(?array $permissions) use ($username, $resolver): void {
            if (!is_array($permissions)) {
                $resolver->reject();
                return;
            }

            $resolver->resolve($permissions);
        });

        return $resolver->getPromise();
    }

    public function checkPlayer(string $username): Promise {
        /** @var PromiseResolver<bool> */
        $resolver = new PromiseResolver();
        AsyncExecutor::execute(fn(Database $database) => $database->has("players", ["username" => $username]), fn(bool $is) => $resolver->resolve($is));
        return $resolver->getPromise();
    }
}