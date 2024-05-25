<?php

namespace r3pt1s\groupsystem\group;

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;

class Group {

    public function __construct(
        private readonly string $name,
        private string $nameTag = "",
        private string $displayName = "",
        private string $chatFormat = "",
        private string $colorCode = "",
        private array $permissions = []
    ) {
        $this->nameTag = ($this->nameTag == "" ? "§7§l" . $this->name . " §r§8| §7{name}" : $this->nameTag);
        $this->displayName = ($this->displayName == "" ? "§l§7{name}" : $this->displayName);
        $this->chatFormat = ($this->chatFormat == "" ? "§7§l" . $this->name . " §r§8| §7{name} §r§8» §r§f{msg}" : $this->chatFormat);
        $this->colorCode = ($this->colorCode == "" ? "§7" : $this->colorCode);
    }

    public function buildMysqlInsertArgs(): array {
        return [
            "name" => $this->name,
            "name_tag" => $this->nameTag,
            "display_name" => $this->displayName,
            "chat_format" => $this->chatFormat,
            "color_code" => $this->colorCode,
            "permissions" => json_encode($this->permissions)
        ];
    }

    /** @internal */
    public function apply(array $data): void {
        $this->nameTag = $data["name_tag"];
        $this->displayName = $data["display_name"];
        $this->chatFormat = $data["chat_format"];
        $this->colorCode = $data["color_code"];
        $this->permissions = $data["permissions"];
    }

    public function getName(): string {
        return $this->name;
    }

    public function getNameTag(): string {
        return $this->nameTag;
    }

    public function getDisplayName(): string {
        return $this->displayName;
    }

    public function getChatFormat(): string {
        return $this->chatFormat;
    }

    public function getColorCode(): string {
        return $this->colorCode;
    }

    public function getPermissions(): array {
        return $this->permissions;
    }

    public function isHigher(Group $group): bool {
        $index = array_search($this->getName(), array_keys(GroupManager::getInstance()->getGroups()));
        $indexTwo = array_search($group->getName(), array_keys(GroupManager::getInstance()->getGroups()));
        return $index < $indexTwo;
    }

    #[ArrayShape(["name" => "string", "display_name" => "string", "name_tag" => "string", "chat_format" => "string", "color_code" => "string", "permissions" => "array"])] public function toArray(): array {
        return [
            "name" => $this->name,
            "display_name" => $this->displayName,
            "name_tag" => $this->nameTag,
            "chat_format" => $this->chatFormat,
            "color_code" => $this->colorCode,
            "permissions" => $this->permissions
        ];
    }

    #[Pure] public static function fromArray(array $data): ?self {
        if (isset($data["name"]) && isset($data["display_name"]) && isset($data["name_tag"]) && isset($data["chat_format"]) && isset($data["color_code"]) && isset($data["permissions"]) && is_array($data["permissions"])) {
            return new self(
                $data["name"],
                $data["name_tag"],
                $data["display_name"],
                $data["chat_format"],
                $data["color_code"],
                $data["permissions"]
            );
        }
        return null;
    }
}