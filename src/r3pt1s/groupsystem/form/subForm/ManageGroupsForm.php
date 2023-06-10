<?php

namespace r3pt1s\groupsystem\form\subForm;

use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Dropdown;
use dktapps\pmforms\element\Input;
use dktapps\pmforms\element\Label;
use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use pocketmine\utils\TextFormat;
use r3pt1s\groupsystem\form\MainForm;
use pocketmine\player\Player;
use r3pt1s\groupsystem\group\Group;
use r3pt1s\groupsystem\group\GroupManager;
use r3pt1s\groupsystem\util\Utils;

class ManageGroupsForm extends MenuForm {

    private array $options = [];

    public function __construct(string $message = "") {
        $this->options[] = new MenuOption(Utils::parse("manage_groups_ui_create_group"));
        $this->options[] = new MenuOption(Utils::parse("manage_groups_ui_remove_group"));
        $this->options[] = new MenuOption(Utils::parse("manage_groups_ui_edit_group"));
        $this->options[] = new MenuOption(Utils::parse("manage_groups_ui_see_groups"));
        $this->options[] = new MenuOption(Utils::parse("manage_groups_ui_reload_groups"));
        $this->options[] = new MenuOption(Utils::parse("manage_groups_ui_back"));

        parent::__construct(Utils::parse("manage_groups_ui_title"), $message, $this->options, function(Player $player, int $data): void {
            if ($data == 0) {
                $player->sendForm(new CustomForm(
                    Utils::parse("create_group_ui_title"),
                    [
                        new Label("text", Utils::parse("create_group_ui_text")),
                        new Input("name", Utils::parse("create_group_ui_name")),
                        new Input("nameTag", Utils::parse("create_group_ui_nametag")),
                        new Input("displayName", Utils::parse("create_group_ui_displayname")),
                        new Input("colorCode", Utils::parse("create_group_ui_colorcode")),
                        new Input("chatFormat", Utils::parse("create_group_ui_chatformat")),
                        new Input("permissions", Utils::parse("create_group_ui_permissions"))
                    ],
                    function(Player $player, CustomFormResponse $response): void {
                        $name = $response->getString("name");
                        $nameTag = $response->getString("nameTag");
                        $displayName = $response->getString("displayName");
                        $colorCode = $response->getString("colorCode");
                        $chatFormat = $response->getString("chatFormat");
                        $permissions = explode(";", $response->getString("permissions"));

                        if ($name !== "") {
                            if (!GroupManager::getInstance()->isGroupExisting($name)) {
                                GroupManager::getInstance()->createGroup($group = new Group($name, $nameTag, $displayName, $chatFormat, $colorCode, $permissions));
                                $player->sendForm(new self(Utils::parse("group_created", [$group->getColorCode() . $group->getName()])));
                            } else $player->sendForm(new self(Utils::parse("group_already_exists", [$name])));
                        } else $player->sendForm(new self(Utils::parse("provide_group_name")));
                    }, function(Player $player): void {
                        $player->sendForm(new self());
                    }
                ));
            } else if ($data == 1) {
                $groups = array_values(array_map(function(Group $group): string { return $group->getColorCode() . $group->getName(); }, GroupManager::getInstance()->getGroups()));
                $player->sendForm(new CustomForm(
                    Utils::parse("delete_group_ui_title"),
                    [new Dropdown("group", Utils::parse("delete_group_ui_choose_group"), $groups)],
                    function(Player $player, CustomFormResponse $response) use($groups): void {
                        $group = GroupManager::getInstance()->getGroupByName(TextFormat::clean($groups[$response->getInt("group")], true));
                        if ($group !== null) {
                            GroupManager::getInstance()->removeGroup($group);
                            $player->sendForm(new self(Utils::parse("group_deleted", [$group->getColorCode() . $group->getName()])));
                        } else $player->sendForm(new self(Utils::parse("group_doesnt_exists", [TextFormat::clean($groups[$response->getInt("group")], true)])));
                    }, function(Player $player): void {
                        $player->sendForm(new self());
                    }
                ));
            } else if ($data == 2) {
                $groups = array_values(array_map(function(Group $group): string { return $group->getColorCode() . $group->getName(); }, GroupManager::getInstance()->getGroups()));
                $player->sendForm(new CustomForm(
                    Utils::parse("edit_group_ui_title"),
                    [
                        new Label("text", Utils::parse("edit_group_ui_text")),
                        new Dropdown("group", Utils::parse("edit_group_ui_choose_group"), $groups),
                        new Input("nameTag", Utils::parse("edit_group_ui_nametag")),
                        new Input("displayName", Utils::parse("edit_group_ui_displayname")),
                        new Input("colorCode", Utils::parse("edit_group_ui_colorcode")),
                        new Input("chatFormat", Utils::parse("edit_group_ui_chatformat")),
                        new Input("permissions", Utils::parse("edit_group_ui_permissions"))
                    ],
                    function(Player $player, CustomFormResponse $response) use($groups): void {
                        $group = GroupManager::getInstance()->getGroupByName(TextFormat::clean($groups[$response->getInt("group")], true));
                        if ($group !== null) {
                            $nameTag = ($response->getString("nameTag") == "" ? $group->getNameTag() : $response->getString("nameTag"));
                            $displayName = ($response->getString("displayName") == "" ? $group->getDisplayName() : $response->getString("displayName"));
                            $colorCode = ($response->getString("colorCode") == "" ? $group->getColorCode() : $response->getString("colorCode"));
                            $chatFormat = ($response->getString("chatFormat") == "" ? $group->getChatFormat() : $response->getString("chatFormat"));
                            $permissions = ($response->getString("permissions") == "" ? $group->getPermissions() : explode(";", $response->getString("permissions")));
                            GroupManager::getInstance()->editGroup($group, $nameTag, $displayName, $chatFormat, $colorCode, $permissions);
                            $player->sendForm(new self(Utils::parse("group_edited", [TextFormat::clean($groups[$response->getInt("group")], true)])));
                        } else $player->sendForm(new self(Utils::parse("group_doesnt_exists", [TextFormat::clean($groups[$response->getInt("group")], true)])));
                    }, function(Player $player): void {
                        $player->sendForm(new self());
                    }
                ));
            } else if ($data == 3) {
                $groups = array_values(array_map(function(Group $group): string { return $group->getColorCode() . $group->getName(); }, GroupManager::getInstance()->getGroups()));
                $player->sendForm(new MenuForm(
                    Utils::parse("see_available_groups_ui_title"),
                    Utils::parse("see_available_groups_ui_text", [count($groups)]),
                    array_map(function(string $group): MenuOption {
                        return new MenuOption($group);
                    }, $groups),
                    function(Player $player, int $data) use($groups): void {
                        $group = GroupManager::getInstance()->getGroupByName(TextFormat::clean($groups[$data], true));
                        if ($group !== null) {
                            $player->sendForm(new MenuForm(
                                Utils::parse("see_available_group_title", [$group->getColorCode() . $group->getName()]),
                                Utils::parse("see_available_group_text", [$group->getColorCode() . $group->getName(), $group->getNameTag(), $group->getDisplayName(), $group->getChatFormat(), implode("\n- ", $group->getPermissions())]),
                                [new MenuOption(Utils::parse("see_available_group_back"))],
                                function(Player $player, int $data): void {
                                    $player->sendForm(new self());
                                }, function(Player $player): void {
                                    $player->sendForm(new self());
                                }
                            ));
                        } else $player->sendForm(new self(Utils::parse("group_doesnt_exists", [TextFormat::clean($groups[$data], true)])));
                    }, function(Player $player): void {
                        $player->sendForm(new self());
                    }
                ));
            } else if ($data == 4) {
                GroupManager::getInstance()->reload();
                $player->sendForm(new self(Utils::parse("groups_reloaded")));
            } else {
                $player->sendForm(new MainForm());
            }
        }, function(Player $player): void {
            $player->sendForm(new MainForm());
        });
    }
}