<?php

namespace r3pt1s\groupsystem\migration;

interface Migrator {

    public function migrate(): void;
}