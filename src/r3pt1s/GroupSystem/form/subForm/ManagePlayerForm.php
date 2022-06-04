<?php

namespace r3pt1s\GroupSystem\form\subForm;

use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Dropdown;
use dktapps\pmforms\element\Input;
use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use r3pt1s\GroupSystem\form\MainForm;
use r3pt1s\GroupSystem\group\Group;
use r3pt1s\GroupSystem\group\GroupManager;
use r3pt1s\GroupSystem\player\GroupPriority;
use r3pt1s\GroupSystem\player\PlayerGroup;
use r3pt1s\GroupSystem\player\PlayerGroupManager;
use r3pt1s\GroupSystem\util\Utils;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class ManagePlayerForm extends MenuForm {

    private array $options = [];

    public function __construct(string $username, string $message = "") {
        $currentGroup = PlayerGroupManager::getInstance()->getGroup($username);
        $expireString = Utils::parse("raw_never");
        if ($currentGroup->getExpireDate() instanceof \DateTime) $expireString = Utils::diffString(new \DateTime("now"), $currentGroup->getExpireDate());
        else if (is_string($currentGroup->getExpireDate())) $expireString = Utils::diffString(new \DateTime("now"), Utils::convertStringToDateFormat($currentGroup->getExpireDate()));

        $message = $message . ($message == "" ? "" : "\n\n") . Utils::parse("manage_players_ui_text", [
            $currentGroup->getGroup()->getColorCode() . $currentGroup->getGroup()->getName(),
            $expireString
        ]);
        $this->options[] = new MenuOption(Utils::parse("manage_players_ui_add_group"));
        $this->options[] = new MenuOption(Utils::parse("manage_players_ui_remove_group"));
        $this->options[] = new MenuOption(Utils::parse("manage_players_ui_skip_group"));
        $this->options[] = new MenuOption(Utils::parse("manage_players_ui_see_groups"));
        $this->options[] = new MenuOption(Utils::parse("manage_players_ui_add_permission"));
        $this->options[] = new MenuOption(Utils::parse("manage_players_ui_remove_permission"));
        $this->options[] = new MenuOption(Utils::parse("manage_players_ui_see_permissions"));
        $this->options[] = new MenuOption(Utils::parse("manage_players_ui_back"));

        parent::__construct(Utils::parse("manage_players_ui_title", [$username]), $message, $this->options, function(Player $player, int $data) use($username): void {
            if ($data == 0) {
                $groups = array_values(array_map(function(Group $group): string {return $group->getColorCode() . $group->getName(); }, GroupManager::getInstance()->getGroups()));
                $priorities = array_values(array_map(function(GroupPriority $priority): string { return $priority->getName(); }, GroupPriority::getAll()));
                $player->sendForm(new CustomForm(
                    Utils::parse("add_group_ui_title"),
                    [
                        new Dropdown("group", Utils::parse("add_group_ui_choose_group"), $groups),
                        new Input("time", Utils::parse("add_group_ui_choose_time"), "1y1m1w1d12h30M30s"),
                        new Dropdown("priority", Utils::parse("add_group_ui_choose_priority"), $priorities)
                    ],
                    function(Player $player, CustomFormResponse $response) use($username, $groups, $priorities): void {
                        $group = GroupManager::getInstance()->getGroupByName(TextFormat::clean($groups[$response->getInt("group")], true));
                        $time = null;
                        if (Utils::convertStringToDateFormat($response->getString("time")) !== null) $time = $response->getString("time");
                        $priority = GroupPriority::get($priorities[$response->getInt("priority")]);
                        if ($group !== null) {
                            if (PlayerGroupManager::getInstance()->addGroup($username, new PlayerGroup($group, $priority, $time))) {
                                $player->sendForm(new self($username, Utils::parse("group_added", [$group->getColorCode() . $group->getName(), $username])));
                            } else {
                                $player->sendForm(new self($username, Utils::parse("group_cant_added", [$group->getColorCode() . $group->getName(), $username])));
                            }
                        } else $player->sendForm(new self($username, Utils::parse("group_doesnt_exists", [TextFormat::clean($groups[$response->getInt("group")], true)])));
                    }, function(Player $player) use($username): void {
                        $player->sendForm(new self($username));
                    }
                ));
            } else if ($data == 1) {
                $groups = array_map(function(PlayerGroup $group): string {return $group->getGroup()->getColorCode() . $group->getGroup()->getName(); }, PlayerGroupManager::getInstance()->getGroups($username, true));
                if (count($groups) == 0) {
                    $player->sendForm(new self($username, Utils::parse("player_has_no_groups", [$username])));
                } else {
                    $player->sendForm(new CustomForm(
                        Utils::parse("remove_group_ui_title"),
                        [
                            new Dropdown("group", Utils::parse("remove_group_ui_choose_group"), $groups),
                        ],
                        function(Player $player, CustomFormResponse $response) use ($username, $groups): void {
                            $group = GroupManager::getInstance()->getGroupByName(TextFormat::clean($groups[$response->getInt("group")], true));
                            if ($group !== null) {
                                if (PlayerGroupManager::getInstance()->removeGroup($username, $group)) {
                                    $player->sendForm(new self($username, Utils::parse("group_removed", [$group->getColorCode() . $group->getName(), $username])));
                                } else {
                                    $player->sendForm(new self($username, Utils::parse("group_cant_removed", [$group->getColorCode() . $group->getName(), $username])));
                                }
                            } else $player->sendForm(new self($username, Utils::parse("group_doesnt_exists")));
                        }, function(Player $player) use ($username): void {
                        $player->sendForm(new self($username));
                    }
                    ));
                }
            } else if ($data == 2) {
                PlayerGroupManager::getInstance()->nextGroup($username);
                $player->sendForm(new self($username, Utils::parse("group_skipped", [$username])));
            } else if ($data == 3) {
                $groups = PlayerGroupManager::getInstance()->getGroups($username, true);
                $player->sendForm(new MenuForm(
                    Utils::parse("see_groups_ui_title"),
                    Utils::parse("see_groups_ui_text", [$username, count($groups)]),
                    array_map(function(PlayerGroup $group): MenuOption {
                        return new MenuOption($group->getGroup()->getColorCode() . $group->getGroup()->getName() . "\n§e" . ($group->getExpireDate() === null ? Utils::parse("raw_never") : "§e" . ($group->getExpireDate() instanceof \DateTime ? $group->getExpireDate()->format("Y-m-d H:i:s") : $group->getExpireDate())));
                    }, $groups),
                    function(Player $player, int $data) use($username): void {
                        $player->sendForm(new self($username));
                    }, function(Player $player) use($username): void {
                        $player->sendForm(new self($username));
                    }
                ));
            } else if ($data == 4) {
                $player->sendForm(new CustomForm(
                    Utils::parse("add_permission_ui_title"),
                    [new Input("permission", Utils::parse("add_permission_ui_which_permission"))],
                    function(Player $player, CustomFormResponse $response) use($username): void {
                        $permission = $response->getString("permission");
                        if ($permission !== "") {
                            PlayerGroupManager::getInstance()->addPermission($username, $permission);
                            $player->sendForm(new self($username, Utils::parse("permission_added", [$username, $permission])));
                        } else $player->sendForm(new self($username, Utils::parse("provide_permission")));
                    }, function(Player $player) use($username): void {
                        $player->sendForm(new self($username));
                    }
                ));
            } else if ($data == 5) {
                $player->sendForm(new CustomForm(
                    Utils::parse("remove_permission_ui_title"),
                    [new Input("permission", Utils::parse("remove_permission_ui_which_permission"))],
                    function(Player $player, CustomFormResponse $response) use($username): void {
                        $permission = $response->getString("permission");
                        if ($permission !== "") {
                            PlayerGroupManager::getInstance()->removePermission($username, $permission);
                            $player->sendForm(new self($username, Utils::parse("permission_removed", [$username, $permission])));
                        } else $player->sendForm(new self($username, Utils::parse("provide_permission")));
                    }, function(Player $player) use($username): void {
                        $player->sendForm(new self($username));
                    }
                ));
            } else if ($data == 6) {
                $permissions = PlayerGroupManager::getInstance()->getPermissions($username);
                $player->sendForm(new MenuForm(
                    Utils::parse("see_permissions_ui_title"),
                    Utils::parse("see_permissions_ui_text", [$username, count($permissions)]),
                    array_map(function(string $permission): MenuOption {
                        return new MenuOption("§e" . $permission);
                    }, $permissions),
                    function(Player $player, int $data) use($username): void {
                        $player->sendForm(new self($username));
                    }, function(Player $player) use($username): void {
                        $player->sendForm(new self($username));
                    }
                ));
            } else {
                $player->sendForm(new MainForm());
            }
        }, function(Player $player): void {
            $player->sendForm(new MainForm());
        });
    }
}