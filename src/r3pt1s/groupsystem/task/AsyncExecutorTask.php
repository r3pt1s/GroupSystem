<?php

namespace r3pt1s\groupsystem\task;

use JetBrains\PhpStorm\Pure;
use pocketmine\scheduler\AsyncTask;
use pocketmine\thread\NonThreadSafeValue;
use r3pt1s\groupsystem\util\Configuration;
use r3pt1s\groupsystem\util\Database;

class AsyncExecutorTask extends AsyncTask {

    private NonThreadSafeValue $mysql;

    public function __construct(private \Closure $closure, private ?\Closure $completion = null) {
        $this->mysql = new NonThreadSafeValue(Configuration::getInstance()->getMysql());
    }

    public function onRun(): void {
        $result = ($this->closure)($this, new Database($this->mysql->deserialize()));
        if (!$result instanceof \PDOStatement) $this->setResult($result);
    }

    public function onCompletion(): void {
        if ($this->completion !== null) {
            ($this->completion)($this->getResult());
        }
    }

    #[Pure] public static function new(\Closure $closure, ?\Closure $completion = null): self {
        return new self($closure, $completion);
    }
}