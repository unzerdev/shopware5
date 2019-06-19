<?php

namespace HeidelPayment\Installers;

interface InstallerInterface
{
    public function install(): void;

    public function uninstall(): void;

    public function update(string $oldVersion, string $newVersion): void;
}
