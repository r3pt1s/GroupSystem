<?php

namespace r3pt1s\groupsystem\player;

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use r3pt1s\groupsystem\group\Group;
use r3pt1s\groupsystem\group\GroupManager;
use r3pt1s\groupsystem\util\Utils;

final class PlayerRemainingGroup {

    public function __construct(
        private readonly Group $group,
        private readonly ?string $time = null
    ) {}

    public function getName(): string {
        return $this->group->getName();
    }

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

    #[Pure] #[ArrayShape(["group" => "string", "time" => "null|string"])] public function write(): array {
        return [
            "group" => $this->group->getName(),
            "time" => $this->time
        ];
    }

    public static function read(array $data): ?self {
        Utils::checkArrayKeysValuesType($data, [
            "group", "time"
        ], [
            "string", "NULL|string"
        ]);

        if (($group = GroupManager::getInstance()->getGroup($data["group"])) !== null) {
            return new self(
                $group,
                is_string(($data["time"] ?? null)) ? $data["time"] : null
            );
        }
        return null;
    }
}