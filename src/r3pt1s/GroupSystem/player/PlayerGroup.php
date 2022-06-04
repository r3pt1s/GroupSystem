<?php

namespace r3pt1s\GroupSystem\player;

use r3pt1s\GroupSystem\group\Group;

class PlayerGroup {

    private Group $group;
    private GroupPriority $priority;
    private \DateTime|string|null $expireDate;

    public function __construct(Group $group, GroupPriority $priority, \DateTime|string|null $expireDate = null) {
        $this->group = $group;
        $this->priority = $priority;
        $this->expireDate = $expireDate;
    }

    public function hasExpired(): bool {
        if ($this->expireDate instanceof \DateTime) return $this->expireDate <= new \DateTime("now");
        return false;
    }

    public function getGroup(): Group {
        return $this->group;
    }

    public function getPriority(): GroupPriority {
        return $this->priority;
    }

    public function getExpireDate(): \DateTime|string|null {
        return $this->expireDate;
    }
}