<?php

namespace r3pt1s\groupsystem\util;

use Closure;

final class BatchPromise {

    private bool $resolved = false;
    private bool $rejected = false;
    private int $acceptations = 0;
    private int $rejections = 0;
    private ?Closure $success = null;
    private ?Closure $failure = null;

    public function __construct(
        private readonly int $requiredAcceptations
    ) {}

    public function accept(): void {
        if ($this->resolved || $this->rejected) return;
        $this->acceptations++;

        if ($this->acceptations == $this->requiredAcceptations) {
            $this->resolved = true;

            if ($this->success !== null) ($this->success)();

            $this->success = null;
            $this->failure = null;
        } else if (($this->acceptations + $this->rejections) == $this->requiredAcceptations) {
            $this->rejected = true;

            if ($this->failure !== null) ($this->failure)();

            $this->success = null;
            $this->failure = null;
        }
    }

    public function reject(): void {
        if ($this->resolved || $this->rejected) return;

        if (($this->acceptations + $this->rejections) == $this->requiredAcceptations) {
            $this->rejected = true;

            if ($this->failure !== null) ($this->failure)();

            $this->success = null;
            $this->failure = null;
        }
    }

    public function then(Closure $closure): self {
        if ($this->resolved && !$this->rejected) {
            ($closure)();
        } else {
            $this->success = $closure;
        }
        return $this;
    }

    public function failure(Closure $closure): self {
        if ($this->rejected) {
            ($closure)();
        } else {
            $this->failure = $closure;
        }
        return $this;
    }
}