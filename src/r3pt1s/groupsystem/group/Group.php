<?php

namespace r3pt1s\groupsystem\group;

use JetBrains\PhpStorm\ArrayShape;
use r3pt1s\groupsystem\util\Configuration;
use r3pt1s\groupsystem\util\Utils;

final class Group {

    private array $grantedPermissions = [];
    private array $revokedPermissions = [];

    public function __construct(
        private readonly string $name,
        private string $nameTag = "",
        private string $displayName = "",
        private string $chatFormat = "",
        private string $colorCode = "",
        private array $permissions = []
    ) {
        $this->nameTag = ($this->nameTag == "" ? "§7§l" . $this->name . " §r§8| §7{name}" : $this->nameTag);
        $this->displayName = ($this->displayName == "" ? "{name}" : $this->displayName);
        $this->chatFormat = ($this->chatFormat == "" ? "§7§l" . $this->name . " §r§8| §7{name} §r§8» §r§f{msg}" : $this->chatFormat);
        $this->colorCode = ($this->colorCode == "" ? "§7" : $this->colorCode);

        $this->resortPermissions();
    }

    protected function resortPermissions(): void {
        $grantedPermissions = [];
        $revokedPermissions = [];
        foreach ($this->permissions as $permissionString) {
            [$permission, , $granted] = Utils::parsePermissionString($permissionString);
            if ($granted) $grantedPermissions[] = $permission;
            else $revokedPermissions[] = $permission;
        }

        $this->grantedPermissions = $grantedPermissions;
        $this->revokedPermissions = $revokedPermissions;
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
    public function apply(Group|array $data): void {
        if ($data instanceof Group) {
            $this->nameTag = $data->getNameTag();
            $this->displayName = $data->getDisplayName();
            $this->chatFormat = $data->getChatFormat();
            $this->colorCode = $data->getColorCode();
            $this->permissions = $data->getPermissions();
        } else {
            $this->nameTag = $data["name_tag"];
            $this->displayName = $data["display_name"];
            $this->chatFormat = $data["chat_format"];
            $this->colorCode = $data["color_code"];
            $this->permissions = $data["permissions"];
        }

        $this->resortPermissions();
    }

    public function getName(): string {
        return $this->name;
    }

    public function getFancyName(): string {
        return $this->colorCode . $this->name;
    }

    public function setNameTag(string $nameTag): Group {
        $this->nameTag = $nameTag;
        return $this;
    }

    public function getNameTag(): string {
        return $this->nameTag;
    }

    public function setDisplayName(string $displayName): Group {
        $this->displayName = $displayName;
        return $this;
    }

    public function getDisplayName(): string {
        return $this->displayName;
    }

    public function setChatFormat(string $chatFormat): Group {
        $this->chatFormat = $chatFormat;
        return $this;
    }

    public function getChatFormat(): string {
        return $this->chatFormat;
    }

    public function setColorCode(string $colorCode): Group {
        $this->colorCode = $colorCode;
        return $this;
    }

    public function getColorCode(): string {
        return $this->colorCode;
    }

    public function setPermissions(array $permissions): Group {
        $this->permissions = $permissions;
        $this->resortPermissions();
        return $this;
    }

    public function getPermissions(): array {
        return $this->permissions;
    }

    public function getGrantedPermissions(): array {
        return $this->grantedPermissions;
    }

    public function getRevokedPermissions(): array {
        return $this->revokedPermissions;
    }

    public function isHigher(Group $group): bool {
        $index = array_search($this->getName(), array_keys(GroupManager::getInstance()->getGroups()));
        $indexTwo = array_search($group->getName(), array_keys(GroupManager::getInstance()->getGroups()));
        return $index < $indexTwo;
    }

    public function isLower(Group $group): bool {
        return !$this->isHigher($group);
    }

    public function isEquals(Group $group): bool {
        $index = array_search($this->getName(), array_keys(GroupManager::getInstance()->getGroups()));
        $indexTwo = array_search($group->getName(), array_keys(GroupManager::getInstance()->getGroups()));
        return $index == $indexTwo;
    }

    public function isDefault(): bool {
        return Configuration::getInstance()->getDefaultGroup() == $this->name;
    }

    #[ArrayShape(["name" => "string", "display_name" => "string", "name_tag" => "string", "chat_format" => "string", "color_code" => "string", "permissions" => "array"])] public function write(): array {
        return [
            "name" => $this->name,
            "display_name" => $this->displayName,
            "name_tag" => $this->nameTag,
            "chat_format" => $this->chatFormat,
            "color_code" => $this->colorCode,
            "permissions" => $this->permissions
        ];
    }

    public static function read(array $data): self {
        Utils::checkArrayKeysValuesType($data, [
            "name", "display_name", "name_tag", "chat_format", "color_code", "permissions"
        ], [
            "string", "string", "string", "string", "string", "array"
        ]);

        return new self(
            $data["name"],
            $data["name_tag"],
            $data["display_name"],
            $data["chat_format"],
            $data["color_code"],
            $data["permissions"]
        );
    }
}