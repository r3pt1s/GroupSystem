<?php

namespace r3pt1s\groupsystem\player;

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use r3pt1s\groupsystem\group\Group;
use r3pt1s\groupsystem\group\GroupManager;
use r3pt1s\groupsystem\util\Utils;

class PlayerRemainingGroup {

    public function __construct(
        private Group $group,
        private ?string $time = null
    ) {}

    public function getGroup(): Group {
        return $this->group;
    }

    public function getTime(): ?string {
        return $this->time;
    }

    public function toPlayerGroup(): PlayerGroup {
        $expire = ($this->time === null ? null : Utils::convertStringToDateFormat($this->time));
        return new PlayerGroup(
            $this->group,
            $expire
        );
    }

    #[Pure] #[ArrayShape(["group" => "string", "time" => "null|string"])] public function toArray(): array {
        return [
            "group" => $this->group->getName(),
            "time" => $this->time
        ];
    }

    #[Pure] public static function fromArray(array $data): ?self {
        if (isset($data["group"])) {
            if (($group = GroupManager::getInstance()->getGroupByName($data["group"])) !== null) {
                return new self(
                    $group,
                    is_string(($data["time"] ?? null)) ? $data["time"] : null
                );
            }
        }
        return null;
    }
}