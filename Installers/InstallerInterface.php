<?php

namespace HeidelPayment\Installers;

interface InstallerInterface
{
    public function install();

    public function uninstall();

    public function update(string $oldVersion, string $newVersion);
}
