<?php

namespace GroupSystem\group;

class Group {

    private string $name;
    private string $nameTag;
    private string $displayName;
    private string $chatFormat;
    private string $colorCode;
    private array $permissions;

    public function __construct(string $name, string $nameTag = "", string $displayName = "", string $chatFormat = "", string $colorCode = "", array $permissions = []) {
        $this->name = $name;
        $this->nameTag = ($nameTag == "" ? "§7§l" . $name . " §r§8| §7{name}" : $nameTag);
        $this->displayName = ($displayName == "" ? "§l§7{name}" : $displayName);
        $this->chatFormat = ($chatFormat == "" ? "§7§l" . $name . " §r§8| §7{name} §r§8» §r§f{msg}" : $chatFormat);
        $this->colorCode = ($colorCode == "" ? "§7" : $colorCode);
        $this->permissions = $permissions;
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
}