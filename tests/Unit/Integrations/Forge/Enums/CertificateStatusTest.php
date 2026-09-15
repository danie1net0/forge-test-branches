<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Integrations\Forge\Enums\CertificateStatus;

test('identifica os status de falha do certificado', function (CertificateStatus $status, bool $expected): void {
    expect($status->hasFailed())->toBe($expected);
})->with([
    'falhou' => [CertificateStatus::FAILED, true],
    'falha desconhecida' => [CertificateStatus::FAILED_UNKNOWN, true],
    'falha do runner' => [CertificateStatus::FAILED_RUNNER, true],
    'instalando' => [CertificateStatus::INSTALLING, false],
    'instalado' => [CertificateStatus::INSTALLED, false],
    'renovando' => [CertificateStatus::RENEWING, false],
    'removendo' => [CertificateStatus::REMOVING, false],
]);

test('mapeia o valor bruto da API e ignora status desconhecido', function (): void {
    expect(CertificateStatus::from('installed'))->toBe(CertificateStatus::INSTALLED)
        ->and(CertificateStatus::tryFrom('some-future-status'))->toBeNull();
});
