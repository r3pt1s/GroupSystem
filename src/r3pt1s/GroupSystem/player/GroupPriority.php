<?php

namespace r3pt1s\GroupSystem\player;

use pocketmine\utils\RegistryTrait;

/**
 * @method static GroupPriority HIGH
 * @method static GroupPriority MEDIUM
 * @method static GroupPriority LOW
 */
final class GroupPriority {
    use RegistryTrait;

    /** @var array<GroupPriority> */
    private static array $priorities = [];

    protected static function setup(): void {
        self::add("high", new GroupPriority("high", 3));
        self::add("medium", new GroupPriority("medium", 2));
        self::add("low", new GroupPriority("low", 1));
    }

    private static function add(string $key, GroupPriority $priority) {
        self::$priorities[mb_strtoupper($key)] = $priority;
        self::_registryRegister($key, $priority);
    }

    public static function get(string|int $o): GroupPriority {
        if (is_string($o)) return self::$priorities[mb_strtoupper($o)] ?? self::LOW();
        else foreach (self::$priorities as $priority) if ($priority->getLevel() == $o) return $priority;
        return self::LOW();
    }

    public static function getAll(): array {
        return self::$priorities;
    }

    private string $name;
    private int $level;

    public function __construct(string $name, int $level) {
        $this->name = $name;
        $this->level = $level;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getLevel(): int {
        return $this->level;
    }

    public function isHigher(GroupPriority $priority): bool {
        return $this->level > $priority->getLevel();
    }

    public function isLower(GroupPriority $priority): bool {
        return !$this->isHigher($priority);
    }
}