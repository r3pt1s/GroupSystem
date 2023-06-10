<?php

namespace r3pt1s\groupsystem\command;

use JetBrains\PhpStorm\Pure;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\PluginOwned;
use r3pt1s\groupsystem\GroupSystem;
use r3pt1s\groupsystem\session\Session;
use r3pt1s\groupsystem\util\Utils;

class GroupInfoCommand extends Command implements PluginOwned {

    public function __construct(string $name, Translatable|string $description = "", Translatable|string|null $usageMessage = null, array $aliases = []) {
        parent::__construct($name, $description, $usageMessage, $aliases);
        $this->setPermission(DefaultPermissions::ROOT_USER);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if ($sender instanceof Player) {
            $currentGroup = Session::get($sender)->getGroup();
            $expireString = Utils::parse("raw_never");
            if ($currentGroup->getExpireDate() instanceof \DateTime) $expireString = Utils::diffString(new \DateTime("now"), $currentGroup->getExpireDate());
            $sender->sendMessage(Utils::parse("group_info", [$currentGroup->getGroup()->getColorCode() . $currentGroup->getGroup()->getName(), $expireString]));
        }
        return true;
    }

    #[Pure] public function getOwningPlugin(): GroupSystem {
        return GroupSystem::getInstance();
    }
}