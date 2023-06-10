<?php

namespace r3pt1s\groupsystem\update;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\Internet;
use r3pt1s\groupsystem\GroupSystem;
use r3pt1s\groupsystem\util\Utils;

class AsyncUpdateCheckTask extends AsyncTask {

    public function onRun(): void {
        try {
            $curl = Internet::simpleCurl("https://raw.githubusercontent.com/r3pt1s/GroupSystem/main/plugin.yml");
            $data = yaml_parse($curl->getBody());
            if ($data == false) {
                $this->setResult([false]);
            } else {
                if (isset($data["version"])) {
                    $this->setResult([$data["version"]]);
                } else {
                    $this->setResult([false]);
                }
            }
        } catch (\Exception $e) {
            $this->setResult([false]);
        }
    }

    public function onCompletion(): void {
        if ($this->getResult()[0] == false) {
            GroupSystem::getInstance()->getLogger()->error(Utils::parse("updater_error"));
            Server::getInstance()->getPluginManager()->disablePlugin(GroupSystem::getInstance());
        } else {
            $current = explode(".", UpdateChecker::getInstance()->getCurrentVersion());
            $latest = explode(".", $this->getResult()[0]);
            $outdated = false;

            $i = 0;
            foreach ($current as $number) {
                if (intval($latest[$i]) > intval($number)) {
                    $outdated = true;
                    break;
                }
                $i++;
            }

            UpdateChecker::getInstance()->setData(["outdated" => $outdated, "newest_version" => $this->getResult()[0]]);

            if ($outdated) {
                Server::getInstance()->getLogger()->warning(Utils::parse("updater_outdated"));
                Server::getInstance()->getLogger()->warning(Utils::parse("updater_outdated_2", [UpdateChecker::getInstance()->getCurrentVersion(), $this->getResult()[0]]));
            } else {
                Server::getInstance()->getLogger()->info(Utils::parse("updater_uptodate"));
            }
        }
    }
}