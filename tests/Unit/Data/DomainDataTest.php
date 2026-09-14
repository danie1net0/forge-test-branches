<?php

declare(strict_types=1);

use Ddr\ForgeTestBranches\Data\DomainData;

test('cria instância a partir dos atributos da API', function (): void {
    $data = DomainData::from([
        'id' => 10,
        'server_id' => 123,
        'site_id' => 456,
        'name' => 'feat.review.example.com',
        'type' => 'primary',
        'status' => 'enabled',
        'www_redirect_type' => 'none',
    ]);

    expect($data)
        ->id->toBe(10)
        ->serverId->toBe(123)
        ->siteId->toBe(456)
        ->name->toBe('feat.review.example.com')
        ->getName()->toBe('feat.review.example.com')
        ->type->toBe('primary')
        ->status->toBe('enabled');
});

test('indica se o domínio está habilitado', function (string $status, bool $expected): void {
    $data = new DomainData(id: 10, serverId: 123, siteId: 456, name: 'feat.review.example.com', type: 'primary', status: $status);

    expect($data->isEnabled())->toBe($expected);
})->with([
    'habilitado' => ['enabled', true],
    'pendente' => ['pending', false],
    'conectando' => ['connecting', false],
    'removendo' => ['removing', false],
]);
