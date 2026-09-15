<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Integrations\Forge\Enums\InstallableResourceStatus;

test('considera instalado apenas o status installed', function (InstallableResourceStatus $status, bool $expected): void {
    expect($status->isInstalled())->toBe($expected);
})->with([
    'instalado' => [InstallableResourceStatus::INSTALLED, true],
    'instalando' => [InstallableResourceStatus::INSTALLING, false],
    'atualizando' => [InstallableResourceStatus::UPDATING, false],
    'removendo' => [InstallableResourceStatus::REMOVING, false],
]);

test('mapeia o valor bruto da API e ignora status desconhecido', function (): void {
    expect(InstallableResourceStatus::from('installed'))->toBe(InstallableResourceStatus::INSTALLED)
        ->and(InstallableResourceStatus::tryFrom('some-future-status'))->toBeNull();
});
