<?php

namespace r3pt1s\groupsystem\event\player;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;
use r3pt1s\groupsystem\group\Group;
use r3pt1s\groupsystem\player\PlayerGroup;
use r3pt1s\groupsystem\player\PlayerRemainingGroup;

/**
 * This event is called when the group of a player gets skipped via '/group skip' or through the direct API call.
 */
final class PlayerGroupSkipEvent extends Event implements Cancellable {
    use CancellableTrait;

    public function __construct(
        private readonly string $username,
        private PlayerGroup $oldGroup,
        private Group|PlayerRemainingGroup $newGroup
    ) {}

    public function getUsername(): string {
        return $this->username;
    }

    public function setOldGroup(PlayerGroup $oldGroup): void {
        $this->oldGroup = $oldGroup;
    }

    public function getOldGroup(): PlayerGroup {
        return $this->oldGroup;
    }

    public function setNewGroup(Group|PlayerRemainingGroup $newGroup): void {
        $this->newGroup = $newGroup;
    }

    public function getNewGroup(): Group|PlayerRemainingGroup {
        return $this->newGroup;
    }
}