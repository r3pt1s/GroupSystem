<?php

namespace r3pt1s\groupsystem\form;

use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Input;
use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use r3pt1s\groupsystem\form\subForm\ManageGroupsForm;
use r3pt1s\groupsystem\form\subForm\ManagePlayerForm;
use r3pt1s\groupsystem\GroupSystem;
use r3pt1s\groupsystem\player\PlayerGroup;
use r3pt1s\groupsystem\session\Session;
use r3pt1s\groupsystem\util\Message;
use pocketmine\player\Player;

class MainForm extends MenuForm {

    private array $options = [];

    public function __construct(string $message = "") {
        $this->options[] = new MenuOption(Message::MAIN_UI_MANAGE_PLAYERS());
        $this->options[] = new MenuOption(Message::MAIN_UI_MANAGE_GROUPS());

        parent::__construct(Message::MAIN_UI_TITLE(), $message, $this->options, function(Player $player, int $data): void {
            if ($data == 0) {
                $player->sendForm(new CustomForm(
                    Message::SELECT_PLAYER_UI_TITLE(),
                    [new Input("username", Message::SELECT_PLAYER_INPUT_TEXT(), "", $player->getName())],
                    function(Player $player, CustomFormResponse $response): void {
                        $username = $response->getString("username");
                        GroupSystem::getInstance()->getProvider()->checkPlayer($username)->onCompletion(
                            function(bool $exists) use($username, $player): void {
                                if ($exists) {
                                    Session::get($username)->onLoad(function(PlayerGroup $group, array $groups, array $permissions) use($username, $player): void {
                                        $player->sendForm(new ManagePlayerForm($username, $group));
                                    });
                                } else $player->sendForm(new self(Message::PLAYER_NOT_FOUND()->parse([$username])));
                            },
                            function() use($username, $player): void {
                                $player->sendForm(new self(Message::PLAYER_NOT_FOUND()->parse([$username])));
                            }
                        );
                    }, function(Player $player): void {
                        $player->sendForm(new self());
                    }
                ));
            } else if ($data == 1) {
                $player->sendForm(new ManageGroupsForm());
            }
        });
    }
}