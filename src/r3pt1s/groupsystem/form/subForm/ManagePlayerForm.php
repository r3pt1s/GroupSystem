<?php

namespace r3pt1s\groupsystem\form\subForm;

use DateTime;
use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Dropdown;
use dktapps\pmforms\element\Input;
use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use Exception;
use GlobalLogger;
use r3pt1s\groupsystem\form\MainForm;
use r3pt1s\groupsystem\group\Group;
use r3pt1s\groupsystem\group\GroupManager;
use r3pt1s\groupsystem\player\perm\PlayerPermission;
use r3pt1s\groupsystem\player\PlayerGroup;
use r3pt1s\groupsystem\player\PlayerRemainingGroup;
use r3pt1s\groupsystem\session\Session;
use r3pt1s\groupsystem\util\Message;
use r3pt1s\groupsystem\util\Utils;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class ManagePlayerForm extends MenuForm {

    private array $options = [];

    public function __construct(string $username, private readonly PlayerGroup $currentGroup, string $message = "") {
        $expireString = (string) Message::RAW_NEVER();
        if ($this->currentGroup->getExpireDate() instanceof DateTime) $expireString = Utils::diffString(new DateTime("now"), $this->currentGroup->getExpireDate());

        $message = $message . ($message == "" ? "" : "\n\n") . Message::MANAGE_PLAYERS_UI_TEXT()->parse([
                $this->currentGroup->getGroup()->getColorCode() . $this->currentGroup->getGroup()->getName(),
                $expireString
        ]);

        $this->options[] = new MenuOption(Message::MANAGE_PLAYERS_UI_ADD_GROUP());
        $this->options[] = new MenuOption(Message::MANAGE_PLAYERS_UI_REMOVE_GROUP());
        $this->options[] = new MenuOption(Message::MANAGE_PLAYERS_UI_SKIP_GROUP());
        $this->options[] = new MenuOption(Message::MANAGE_PLAYERS_UI_SEE_GROUPS());
        $this->options[] = new MenuOption(Message::MANAGE_PLAYERS_UI_ADD_PERMISSION());
        $this->options[] = new MenuOption(Message::MANAGE_PLAYERS_UI_REMOVE_PERMISSION());
        $this->options[] = new MenuOption(Message::MANAGE_PLAYERS_UI_SEE_PERMISSIONS());
        $this->options[] = new MenuOption(Message::MANAGE_PLAYERS_UI_BACK());

        parent::__construct(Message::MANAGE_PLAYERS_UI_TITLE()->parse([$username]), $message, $this->options, function(Player $player, int $data) use($username): void {
            if ($data == 0) {
                $groups = array_values(array_map(fn(Group $group) => $group->getColorCode() . $group->getName(), GroupManager::getInstance()->getGroups()));
                $player->sendForm(new CustomForm(
                    Message::ADD_GROUP_UI_TITLE(),
                    [
                        new Dropdown("group", Message::ADD_GROUP_UI_CHOOSE_GROUP(), $groups),
                        new Input("time", Message::ADD_GROUP_UI_CHOOSE_TIME(), "1y1m1w1d12h30M30s")
                    ],
                    function(Player $player, CustomFormResponse $response) use($username, $groups): void {
                        $group = GroupManager::getInstance()->getGroupByName(TextFormat::clean($groups[$response->getInt("group")]));
                        $time = null;
                        if (Utils::convertStringToDateFormat($response->getString("time")) !== null) $time = $response->getString("time");
                        if ($group !== null) {
                            Session::get($username)->addGroup(new PlayerRemainingGroup($group, $time), function(bool $success) use($username, $group, $player): void {
                                if ($success) {
                                    $player->sendForm(new self($username, $this->currentGroup, Message::GROUP_ADDED()->parse([$group->getColorCode() . $group->getName(), $username])));
                                } else {
                                    $player->sendForm(new self($username, $this->currentGroup, Message::GROUP_CANT_ADDED()->parse([$group->getColorCode() . $group->getName(), $username])));
                                }
                            });
                        } else $player->sendForm(new self($username, $this->currentGroup, Message::GROUP_DOESNT_EXISTS()->parse([TextFormat::clean($groups[$response->getInt("group")])])));
                    }, function(Player $player) use($username): void {
                        $player->sendForm(new self($username, $this->currentGroup));
                    }
                ));
            } else if ($data == 1) {
                $nextFormHandler = function(Player $player, array $groups, string $username) {
                    if (count($groups) == 0) {
                        $player->sendForm(new self($username, $this->currentGroup, Message::PLAYER_HAS_NO_GROUPS()->parse([$username])));
                    } else {
                        $player->sendForm(new CustomForm(
                            Message::REMOVE_GROUP_UI_TITLE(),
                            [
                                new Dropdown("group", Message::REMOVE_GROUP_UI_CHOOSE_GROUP(), $groups),
                            ],
                            function(Player $player, CustomFormResponse $response) use ($username, $groups): void {
                                $group = GroupManager::getInstance()->getGroupByName(TextFormat::clean($groups[$response->getInt("group")]));
                                if ($group !== null) {
                                    Session::get($username)->removeGroup($group, function(bool $success) use($username, $group, $player): void {
                                        if ($success) {
                                            $player->sendForm(new self($username, $this->currentGroup, Message::GROUP_REMOVED()->parse([$group->getColorCode() . $group->getName(), $username])));
                                        } else {
                                            $player->sendForm(new self($username, $this->currentGroup, Message::GROUP_CANT_REMOVED()->parse([$group->getColorCode() . $group->getName(), $username])));
                                        }
                                    });
                                } else $player->sendForm(new self($username, $this->currentGroup, Message::GROUP_DOESNT_EXISTS()->parse([TextFormat::clean($groups[$response->getInt("group")])])));
                            }, function(Player $player) use ($username): void {
                            $player->sendForm(new self($username, $this->currentGroup));
                        }
                        ));
                    }
                };

                Session::get($username)->onLoad(function(PlayerGroup $group, array $groups) use($username, $player, $nextFormHandler): void {
                    $nextFormHandler($player, array_values(array_map(fn(PlayerRemainingGroup $group) => $group->getGroup()->getColorCode() . $group->getGroup()->getName(), $groups)), $username);
                });
            } else if ($data == 2) {
                $nextFormHandler = function(Player $player, PlayerGroup $group, string $username): void {
                    $player->sendForm(new self($username, $group, Message::GROUP_SKIPPED()->parse([$username])));
                };

                Session::get($username)->onLoad(function() use($player, $username, $nextFormHandler): void {
                    Session::get($username)->nextGroup();
                    $nextFormHandler($player, Session::get($username)->getGroup(), $username);
                });
            } else if ($data == 3) {
                try {
                    $nextFormHandler = function(Player $player, array $groups, string $username) {
                        $player->sendForm(new MenuForm(
                            Message::SEE_GROUPS_UI_TITLE(),
                            Message::SEE_GROUPS_UI_TEXT()->parse([$username, count($groups)]),
                            array_map(fn(PlayerRemainingGroup $group) => new MenuOption($group->getGroup()->getColorCode() . $group->getGroup()->getName() . "\n§e" . ($group->getTime() === null ? (string) Message::RAW_NEVER() : "§e" . $group->getTime())), $groups),
                            function(Player $player, int $data) use($username): void {
                                $player->sendForm(new self($username, $this->currentGroup));
                            }, function(Player $player) use($username): void {
                            $player->sendForm(new self($username, $this->currentGroup));
                        }
                        ));
                    };

                    Session::get($username)->onLoad(function(PlayerGroup $group, array $groups) use($username, $player, $nextFormHandler): void {
                        $nextFormHandler($player, $groups, $username);
                    });
                } catch (Exception $exception) {
                    GlobalLogger::get()->logException($exception);
                }
            } else if ($data == 4) {
                $player->sendForm(new CustomForm(
                    Message::ADD_PERMISSION_UI_TITLE(),
                    [
                        new Input("permission", Message::ADD_PERMISSION_UI_WHICH_PERMISSION()),
                        new Input("time", Message::ADD_PERMISSION_UI_CHOOSE_TIME(), "1y1m1w1d12h30M30s")
                    ],
                    function(Player $player, CustomFormResponse $response) use($username): void {
                        $permission = $response->getString("permission");
                        $time = null;
                        if (($obj = Utils::convertStringToDateFormat($response->getString("time"))) !== null) $time = $obj;
                        if ($permission !== "") {
                            Session::get($username)->onLoad(fn(PlayerGroup $group, array $groups, array $permissions) => Session::get($username)->addPermission(new PlayerPermission($permission, $time)));
                            $player->sendForm(new self($username, $this->currentGroup, Message::PERMISSION_ADDED()->parse([$username, $permission])));
                        } else $player->sendForm(new self($username, $this->currentGroup, Message::PROVIDE_PERMISSION()));
                    }, function(Player $player) use($username): void {
                        $player->sendForm(new self($username, $this->currentGroup));
                    }
                ));
            } else if ($data == 5) {
                $player->sendForm(new CustomForm(
                    Message::REMOVE_PERMISSION_UI_TITLE(),
                    [new Input("permission", Message::REMOVE_PERMISSION_UI_WHICH_PERMISSION())],
                    function(Player $player, CustomFormResponse $response) use($username): void {
                        $permission = $response->getString("permission");
                        if ($permission !== "") {
                            Session::get($username)->onLoad(fn(PlayerGroup $group, array $groups, array $permissions) => Session::get($username)->removePermission($permission));
                            $player->sendForm(new self($username, $this->currentGroup, Message::PERMISSION_REMOVED()->parse([$username, $permission])));
                        } else $player->sendForm(new self($username, $this->currentGroup, Message::PROVIDE_PERMISSION()));
                    }, function(Player $player) use($username): void {
                        $player->sendForm(new self($username, $this->currentGroup));
                    }
                ));
            } else if ($data == 6) {
                /** @var array<PlayerPermission> $permissions */
                $nextFormHandler = function(Player $player, array $permissions, string $username) {
                    $player->sendForm(new MenuForm(
                        Message::SEE_PERMISSIONS_UI_TITLE(),
                        Message::SEE_PERMISSIONS_UI_TEXT()->parse([$username, count($permissions)]),
                        array_map(fn(PlayerPermission $permission) => new MenuOption("§e" . $permission->getPermission() . ($permission->getExpireDate() !== null ? "\n§c" . $permission->getExpireDate()->format("Y-m-d H:i:s") : "")), $permissions),
                        function(Player $player, int $data) use($username): void {
                            $player->sendForm(new self($username, $this->currentGroup));
                        }, function(Player $player) use($username): void {
                            $player->sendForm(new self($username, $this->currentGroup));
                        }
                    ));
                };

                Session::get($username)->onLoad(fn(PlayerGroup $group, array $groups, array $permissions) => $nextFormHandler($player, $permissions, $username));
            } else {
                $player->sendForm(new MainForm());
            }
        }, function(Player $player): void {
            $player->sendForm(new MainForm());
        });
    }
}