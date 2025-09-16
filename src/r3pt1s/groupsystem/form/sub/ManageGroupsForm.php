<?php

namespace r3pt1s\groupsystem\form\sub;

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
use r3pt1s\groupsystem\util\Message;

final class ManageGroupsForm extends MenuForm {

    public function __construct(string $message = "") {
        parent::__construct(
            Message::MANAGE_GROUPS_UI_TITLE(),
            $message,
            [
                new MenuOption(Message::MANAGE_GROUPS_UI_CREATE_GROUP()),
                new MenuOption(Message::MANAGE_GROUPS_UI_REMOVE_GROUP()),
                new MenuOption(Message::MANAGE_GROUPS_UI_EDIT_GROUP()),
                new MenuOption(Message::MANAGE_GROUPS_UI_SEE_GROUPS()),
                new MenuOption(Message::MANAGE_GROUPS_UI_RELOAD_GROUPS()),
                new MenuOption(Message::MANAGE_GROUPS_UI_BACK())
            ],
            function(Player $player, int $data): void {
                if ($data == 0) {
                    $player->sendForm(new CustomForm(
                        Message::CREATE_GROUP_UI_TITLE(),
                        [
                            new Label("text", Message::CREATE_GROUP_UI_TEXT()),
                            new Input("name", Message::CREATE_GROUP_UI_NAME()),
                            new Input("nameTag", Message::CREATE_GROUP_UI_NAME_TAG()),
                            new Input("displayName", Message::CREATE_GROUP_UI_DISPLAYNAME()),
                            new Input("colorCode", Message::CREATE_GROUP_UI_COLOR_CODE()),
                            new Input("chatFormat", Message::CREATE_GROUP_UI_CHATFORMAT()),
                            new Input("permissions", Message::CREATE_GROUP_UI_PERMISSIONS())
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
                                    $player->sendForm(new self(Message::GROUP_CREATED()->parse([$group->getColorCode() . $group->getName()])));
                                } else $player->sendForm(new self(Message::GROUP_ALREADY_EXISTS()->parse([$name])));
                            } else $player->sendForm(new self(Message::PROVIDE_GROUP_NAME()));
                        }, function(Player $player): void {
                        $player->sendForm(new self());
                    }
                    ));
                } else if ($data == 1) {
                    $groups = array_values(array_map(function(Group $group): string { return $group->getColorCode() . $group->getName(); }, GroupManager::getInstance()->getGroups()));
                    $player->sendForm(new CustomForm(
                        Message::DELETE_GROUP_UI_TITLE(),
                        [new Dropdown("group", Message::DELETE_GROUP_UI_CHOOSE_GROUP(), $groups)],
                        function(Player $player, CustomFormResponse $response) use($groups): void {
                            $group = GroupManager::getInstance()->getGroupByName(TextFormat::clean($groups[$response->getInt("group")]));
                            if ($group !== null) {
                                GroupManager::getInstance()->removeGroup($group);
                                $player->sendForm(new self(Message::GROUP_DELETED()->parse([$group->getColorCode() . $group->getName()])));
                            } else $player->sendForm(new self(Message::GROUP_DOESNT_EXISTS()->parse([TextFormat::clean($groups[$response->getInt("group")])])));
                        }, function(Player $player): void {
                        $player->sendForm(new self());
                    }
                    ));
                } else if ($data == 2) {
                    $groups = array_values(array_map(fn(Group $group) => $group->getColorCode() . $group->getName(), GroupManager::getInstance()->getGroups()));
                    $player->sendForm(new CustomForm(
                        Message::EDIT_GROUP_UI_TITLE(),
                        [
                            new Label("text", Message::EDIT_GROUP_UI_TEXT()),
                            new Dropdown("group", Message::EDIT_GROUP_UI_CHOOSE_GROUP(), $groups),
                            new Input("nameTag", Message::EDIT_GROUP_UI_NAME_TAG()),
                            new Input("displayName", Message::EDIT_GROUP_UI_DISPLAYNAME()),
                            new Input("colorCode", Message::EDIT_GROUP_UI_COLOR_CODE()),
                            new Input("chatFormat", Message::EDIT_GROUP_UI_CHATFORMAT()),
                            new Input("permissions", Message::EDIT_GROUP_UI_PERMISSIONS())
                        ],
                        function(Player $player, CustomFormResponse $response) use($groups): void {
                            $group = GroupManager::getInstance()->getGroupByName(TextFormat::clean($groups[$response->getInt("group")]));
                            if ($group !== null) {
                                $nameTag = ($response->getString("nameTag") == "" ? $group->getNameTag() : $response->getString("nameTag"));
                                $displayName = ($response->getString("displayName") == "" ? $group->getDisplayName() : $response->getString("displayName"));
                                $colorCode = ($response->getString("colorCode") == "" ? $group->getColorCode() : $response->getString("colorCode"));
                                $chatFormat = ($response->getString("chatFormat") == "" ? $group->getChatFormat() : $response->getString("chatFormat"));
                                $permissions = ($response->getString("permissions") == "" ? $group->getPermissions() : explode(";", $response->getString("permissions")));
                                GroupManager::getInstance()->editGroup($group, $nameTag, $displayName, $chatFormat, $colorCode, $permissions);
                                $player->sendForm(new self(Message::GROUP_EDITED()->parse([TextFormat::clean($groups[$response->getInt("group")])])));
                            } else $player->sendForm(new self(Message::GROUP_DOESNT_EXISTS()->parse([TextFormat::clean($groups[$response->getInt("group")])])));
                        }, function(Player $player): void {
                        $player->sendForm(new self());
                    }
                    ));
                } else if ($data == 3) {
                    $groups = array_values(array_map(fn(Group $group) => $group->getColorCode() . $group->getName(), GroupManager::getInstance()->getGroups()));
                    $player->sendForm(new MenuForm(
                        Message::SEE_AVAILABLE_GROUPS_UI_TITLE(),
                        Message::SEE_AVAILABLE_GROUPS_UI_TEXT()->parse([count($groups)]),
                        array_map(fn(string $group) => new MenuOption($group), $groups),
                        function(Player $player, int $data) use($groups): void {
                            $group = GroupManager::getInstance()->getGroupByName(TextFormat::clean($groups[$data]));
                            if ($group !== null) {
                                $player->sendForm(new MenuForm(
                                    Message::SEE_AVAILABLE_GROUP_TITLE()->parse([$group->getColorCode() . $group->getName()]),
                                    Message::SEE_AVAILABLE_GROUP_TEXT()->parse([$group->getColorCode() . $group->getName(), $group->getNameTag(), $group->getDisplayName(), $group->getChatFormat(), "- " . implode("\n- ", $group->getPermissions())]),
                                    [new MenuOption(Message::SEE_AVAILABLE_GROUP_BACK())],
                                    function(Player $player, int $data): void {
                                        $player->sendForm(new self());
                                    }, function(Player $player): void {
                                    $player->sendForm(new self());
                                }
                                ));
                            } else $player->sendForm(new self(Message::GROUP_DOESNT_EXISTS()->parse([TextFormat::clean($groups[$data])])));
                        }, function(Player $player): void {
                        $player->sendForm(new self());
                    }
                    ));
                } else if ($data == 4) {
                    GroupManager::getInstance()->reload();
                    $player->sendForm(new self(Message::GROUPS_RELOADED()));
                } else {
                    $player->sendForm(new MainForm());
                }
            },
            function(Player $player): void {
                $player->sendForm(new MainForm());
            }
        );
    }

    private function buildEditGroupForm(): MenuForm {
        // Form to select whether you want to edit the group's metadata or their permissions
        return new MenuForm("", "", [], function (Player $player, int $data): void {});
    }
}