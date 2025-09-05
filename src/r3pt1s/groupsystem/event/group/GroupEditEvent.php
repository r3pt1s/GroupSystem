<?php

namespace r3pt1s\groupsystem\event\group;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;
use r3pt1s\groupsystem\group\Group;

/**
 * This event is called when a group gets edited.
 */
final class GroupEditEvent extends Event implements Cancellable {
    use CancellableTrait;

    public function __construct(
        private readonly Group $group,
        private readonly array $oldData,
        private readonly array $newData
    ) {}

    public function getGroup(): Group {
        return $this->group;
    }

    public function getOldData(): array {
        return $this->oldData;
    }

    public function getNewData(): array {
        return $this->newData;
    }
}