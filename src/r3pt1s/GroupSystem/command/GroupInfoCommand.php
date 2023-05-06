<?php

namespace r3pt1s\GroupSystem\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use pocketmine\plugin\PluginOwned;
use r3pt1s\GroupSystem\GroupSystem;
use r3pt1s\GroupSystem\player\PlayerGroupManager;
use r3pt1s\GroupSystem\util\Utils;

class GroupInfoCommand extends Command implements PluginOwned {

    public function __construct(string $name, Translatable|string $description = "", Translatable|string|null $usageMessage = null, array $aliases = []) {
        parent::__construct($name, $description, $usageMessage, $aliases);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if ($sender instanceof Player) {
            $currentGroup = PlayerGroupManager::getInstance()->getGroup($sender->getName());
            $expireString = Utils::parse("raw_never");
            if ($currentGroup->getExpireDate() instanceof \DateTime) $expireString = Utils::diffString(new \DateTime("now"), $currentGroup->getExpireDate());
            else if (is_string($currentGroup->getExpireDate())) $expireString = Utils::diffString(new \DateTime("now"), Utils::convertStringToDateFormat($currentGroup->getExpireDate()));
            $sender->sendMessage(Utils::parse("group_info", [$currentGroup->getGroup()->getColorCode() . $currentGroup->getGroup()->getName(), $expireString]));
        }
        return true;
    }

    public function getOwningPlugin(): GroupSystem {
        return GroupSystem::getInstance();
    }
}