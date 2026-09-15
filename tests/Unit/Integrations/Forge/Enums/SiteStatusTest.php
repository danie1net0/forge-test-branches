<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Integrations\Forge\Enums\SiteStatus;

test('identifica status pendentes', function (SiteStatus $status, bool $expected): void {
    expect($status->isPending())->toBe($expected);
})->with([
    'criando' => [SiteStatus::CREATING, true],
    'instalando' => [SiteStatus::INSTALLING, true],
    'instalado' => [SiteStatus::INSTALLED, false],
    'em deploy não é pendente' => [SiteStatus::DEPLOYING, false],
    'removendo' => [SiteStatus::REMOVING, false],
    'falhou' => [SiteStatus::FAILED, false],
]);

test('identifica falha, incluindo os status de remoção', function (SiteStatus $status, bool $expected): void {
    expect($status->hasFailed())->toBe($expected);
})->with([
    'falhou' => [SiteStatus::FAILED, true],
    'sendo removido' => [SiteStatus::REMOVING, true],
    'sendo desinstalado' => [SiteStatus::UNINSTALLING, true],
    'instalado' => [SiteStatus::INSTALLED, false],
    'instalando' => [SiteStatus::INSTALLING, false],
    'em deploy' => [SiteStatus::DEPLOYING, false],
]);

test('identifica remoção especificamente, sem incluir falha', function (SiteStatus $status, bool $expected): void {
    expect($status->isBeingRemoved())->toBe($expected);
})->with([
    'sendo removido' => [SiteStatus::REMOVING, true],
    'sendo desinstalado' => [SiteStatus::UNINSTALLING, true],
    'falhou' => [SiteStatus::FAILED, false],
    'instalado' => [SiteStatus::INSTALLED, false],
]);

test('mapeia o valor bruto da API e ignora status desconhecido', function (): void {
    expect(SiteStatus::from('installed'))->toBe(SiteStatus::INSTALLED)
        ->and(SiteStatus::tryFrom('some-future-status'))->toBeNull();
});
