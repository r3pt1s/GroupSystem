<?php

namespace r3pt1s\groupsystem\provider;

use Closure;
use pocketmine\promise\Promise;
use r3pt1s\groupsystem\group\Group;
use r3pt1s\groupsystem\player\perm\PlayerPermission;
use r3pt1s\groupsystem\player\PlayerGroup;
use r3pt1s\groupsystem\player\PlayerRemainingGroup;

interface Provider {

    public function tryMigrate(): void;

    public function createGroup(Group $group): void;

    public function removeGroup(Group $group): void;

    public function editGroup(Group $group, array $data): void;

    public function checkGroup(string $name): Promise;

    public function getGroupByName(string $name): Promise;

    public function getAllGroups(): Promise;

    public function createPlayer(string $username, ?Closure $completion = null, ?array $customData = null): void;

    public function setGroup(string $username, PlayerGroup $group): void;

    public function addGroupToPlayer(string $username, PlayerRemainingGroup $group): Promise;

    public function removeGroupFromPlayer(string $username, PlayerRemainingGroup|Group $group): Promise;

    public function hasGroup(string $username, PlayerRemainingGroup|Group|string $group): Promise;

    public function getGroupOfPlayer(string $username): Promise;

    public function getGroupsOfPlayer(string $username, bool $asInstance = false): Promise;

    public function addPermission(string $username, PlayerPermission $permission): void;

    public function removePermission(string $username, PlayerPermission|string $permission): void;

    public function getPermissions(string $username, bool $asInstance = false): Promise;

    public function checkPlayer(string $username): Promise;
}