<?php

namespace r3pt1s\groupsystem\provider;

use LogicException;
use r3pt1s\groupsystem\provider\impl\JSONProvider;
use r3pt1s\groupsystem\provider\impl\MySQLProvider;
use r3pt1s\groupsystem\provider\impl\YAMLProvider;

final class ProviderRegistry {

    private static array $providers = [];

    public static function registerDefault(): void {
        self::register("json", JSONProvider::class);
        self::register("mysql", MySQLProvider::class);
        self::register("yml", YAMLProvider::class);
    }

    public static function register(string $id, string $providerClass): void {
        if (!is_subclass_of($providerClass, Provider::class)) throw new LogicException("Provider class $providerClass must be subclass of " . Provider::class);
        self::$providers[strtolower($id)] = $providerClass;
    }

    public static function get(string $id): ?Provider {
        if (!isset(self::$providers[strtolower($id)])) return null;
        return new (self::$providers[strtolower($id)])();
    }

    public static function has(string $id): bool {
        return isset(self::$providers[strtolower($id)]);
    }
}