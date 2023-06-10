<?php

namespace r3pt1s\groupsystem\player;

use JetBrains\PhpStorm\ArrayShape;
use r3pt1s\groupsystem\group\Group;
use r3pt1s\groupsystem\group\GroupManager;
use r3pt1s\groupsystem\util\Utils;

class PlayerGroup {

    public function __construct(
        private Group $group,
        private \DateTime|null $expireDate = null
    ) {}

    public function hasExpired(): bool {
        if ($this->expireDate instanceof \DateTime) return $this->expireDate <= new \DateTime("now");
        return false;
    }

    public function getGroup(): Group {
        return $this->group;
    }

    public function getExpireDate(): \DateTime|null {
        return $this->expireDate;
    }

    public function toRemainingGroup(): PlayerRemainingGroup {
        return new PlayerRemainingGroup(
            $this->group,
            ($this->expireDate === null ? null : Utils::diffString(new \DateTime("now"), $this->expireDate, true))
        );
    }

    #[ArrayShape(["group" => "string", "expire" => "null|string"])] public function toArray(): array {
        return [
            "group" => $this->group->getName(),
            "expire" => ($this->expireDate?->format("Y-m-d H:i:s"))
        ];
    }

    public static function fromArray(array $data): ?self {
        if (isset($data["group"])) {
            if (($group = GroupManager::getInstance()->getGroupByName($data["group"])) !== null) {
                return new self(
                    $group,
                    is_string(($data["expire"] ?? null)) ? new \DateTime($data["expire"]) : null
                );
            }
        }
        return null;
    }
}