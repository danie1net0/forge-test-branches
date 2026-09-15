<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Integrations\Forge\Enums\DomainRecordStatus;

test('considera habilitado apenas o status enabled', function (DomainRecordStatus $status, bool $expected): void {
    expect($status->isEnabled())->toBe($expected);
})->with([
    'habilitado' => [DomainRecordStatus::ENABLED, true],
    'pendente' => [DomainRecordStatus::PENDING, false],
    'conectando' => [DomainRecordStatus::CONNECTING, false],
    'removendo' => [DomainRecordStatus::REMOVING, false],
    'protegendo' => [DomainRecordStatus::SECURING, false],
]);

test('mapeia o valor bruto da API e ignora status desconhecido', function (): void {
    expect(DomainRecordStatus::from('enabled'))->toBe(DomainRecordStatus::ENABLED)
        ->and(DomainRecordStatus::tryFrom('some-future-status'))->toBeNull();
});
