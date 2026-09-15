<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Integrations\Forge\Enums\RepositoryStatus;

test('considera pronto apenas o status installed', function (RepositoryStatus $status, bool $expected): void {
    expect($status->isReady())->toBe($expected);
})->with([
    'instalado' => [RepositoryStatus::INSTALLED, true],
    'instalando' => [RepositoryStatus::INSTALLING, false],
    'removendo' => [RepositoryStatus::REMOVING, false],
]);

test('mapeia o valor bruto da API e ignora status desconhecido', function (): void {
    expect(RepositoryStatus::from('installed'))->toBe(RepositoryStatus::INSTALLED)
        ->and(RepositoryStatus::tryFrom('some-future-status'))->toBeNull();
});
