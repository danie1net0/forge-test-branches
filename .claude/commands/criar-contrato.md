# Criar Contrato de Teste

Você vai criar contratos de teste com cenários variados para facilitar o desenvolvimento e testes da aplicação usando as calculadoras corretas de amortização.

## Objetivo

Criar contratos realistas com diferentes características usando a Action `CreateLoanContractAction`, permitindo testar diversos cenários com cálculos corretos de IOF, CET, parcelas e amortização.

## Instruções

### 1. Identificar o Cenário

Pergunte ao usuário qual cenário ele deseja criar. Se ele não especificar, mostre as opções disponíveis:

**Cenários de Status:**

- `registrado` - Contrato já registrado na CRDC (padrão)
- `iniciado` - Contrato iniciado mas não registrado
- `em_preenchimento` - Contrato ainda sendo preenchido (padrão para criação)
- `registro_pendente` - Aguardando resposta da CRDC
- `quitado` - Contrato totalmente quitado

**Cenários de Inadimplência:**

- `3-parcelas-atrasadas` - Contrato com 3 parcelas vencidas e não pagas
- `1-parcela-atrasada` - Contrato com 1 parcela vencida
- `todas-em-dia` - Todas as parcelas pagas em dia (padrão)
- `parcialmente-pago` - Algumas parcelas pagas, outras em atraso

**Cenários de Tipo de Amortização:**

- `price` - Sistema PRICE (padrão)
- `sac` - Sistema SAC
- `americano` - Sistema Americano
- `pagamento-unico-simples` - Pagamento único com juros simples
- `pagamento-unico-composto` - Pagamento único com juros compostos

**Cenários de Características Especiais:**

- `carencia-principal` - Permite pagamento de carência do principal
- `multa-1-porcento` - Multa de 1% no atraso (padrão é 2%)
- `sem-garantia` - Sem garantias
- `com-garantias` - Com garantias (imóvel, veículo, etc)
- `alto-risco` - Risco de inadimplência alto
- `muitas-parcelas` - Contrato com 12+ parcelas
- `quinzenal` - Pagamento quinzenal
- `trimestral` - Pagamento trimestral

### 2. Identificar a Empresa (ESC)

Pergunte ao usuário para qual empresa criar o contrato. Se não especificar, use ESC 1 (company_id = 2).

**Empresas disponíveis:**

- ESC 1 (ID: 2) - email: esc1@el.com
- ESC 2 (ID: 3) - email: esc2@el.com (se existir)
- Admin (ID: 1) - NÃO usar para contratos de teste

### 3. Criar o Contrato

Use o Tinker para criar o contrato com as características solicitadas usando a Action correta.

⚠️ **IMPORTANTE**: Todo contrato DEVE ter pelo menos:

- 1 representante da empresa / devedor solidário (pessoa física)
- ContractDefaultRules
- Usar `CreateLoanContractAction` para garantir cálculos corretos

#### Template Base (PRICE, Em Preenchimento)

```php
use App\Actions\Contracts\CreateLoanContractAction;
use App\Actions\Contracts\Data\ContractData\LoanData;
use App\Enums\Operations\{AmortizationType, OperationType, PaymentFrequency};
use App\Enums\Contracts\ContractStatus;
use App\Enums\Operations\GuaranteeType;
use App\ValueObjects\{Money, PercentageRate};
use Carbon\CarbonImmutable;

$company = \App\Models\Company::find(2); // ESC 1

// 1. Criar pessoa física (representante/devedor solidário)
$person = \App\Models\Person::factory()->create([
    'company_id' => $company->id,
    'name' => 'João Silva',
    'cpf' => '123.456.789-00',
    'rg' => '12.345.678-9',
    'phone' => '(11) 98765-4321',
]);

// 2. Criar dados para o contrato
$loanData = new LoanData(
    amount: Money::create(10000), // Valor do empréstimo
    interestRate: PercentageRate::create(1.5), // 1.5% a.m.
    contractSignatureDate: CarbonImmutable::parse('2025-01-01'),
    firstPaymentDate: CarbonImmutable::parse('2025-02-01'),
    installments: 6,
    amortizationType: AmortizationType::PRICE,
    paymentFrequency: PaymentFrequency::MONTHLY,
    operationType: OperationType::LOAN,
    guarantees: [GuaranteeType::ENDORSER->value], // Avalista
    isSimplesNacional: false,
    hasIof: true,
    companyId: $company->id,
    contractStatus: ContractStatus::IN_FILLING, // Status inicial
);

// 3. Criar o contrato usando a Action (calcula tudo automaticamente)
$action = app(CreateLoanContractAction::class);
$contractId = $action->execute($loanData);

$contract = \App\Models\Contract::find($contractId);

// 4. Adicionar garantia pessoal (OBRIGATÓRIO)
\App\Models\ContractGuarantee::create([
    'contract_id' => $contract->id,
    'type' => 'devedor_solidario',
    'value' => 0,
    'data' => [
        'name' => $person->name,
        'cpf' => $person->cpf,
        'rg' => $person->rg,
        'rg_issuer' => $person->rg_issuer,
        'phone' => $person->phone,
        'email' => $person->email,
        'monthly_income' => $person->monthly_income ?? 0,
        'marital_status' => $person->marital_status ?? 'solteiro',
        'property_regime' => $person->property_regime,
    ],
]);

// 5. Atualizar regras de inadimplência
$contract->defaultRules->update([
    'late_payment_interest_rate' => 2.00,
    'penalty_fee_rate' => 2.00,
    'adjustment_index' => 'IPCA',
    'estimated_default_risk' => 'baixo',
    'allows_interest_only_payment' => false,
]);

return "Contrato {$contract->number} criado com sucesso! ID: {$contract->id}";
```

#### Sistema SAC com 12 Parcelas

```php
use App\Actions\Contracts\CreateLoanContractAction;
use App\Actions\Contracts\Data\ContractData\LoanData;
use App\Enums\Operations\{AmortizationType, OperationType, PaymentFrequency};
use App\Enums\Contracts\ContractStatus;
use App\Enums\Operations\GuaranteeType;
use App\ValueObjects\{Money, PercentageRate};
use Carbon\CarbonImmutable;

$company = \App\Models\Company::find(2);

// 1. Criar pessoa física
$person = \App\Models\Person::factory()->create([
    'company_id' => $company->id,
]);

// 2. Criar dados do contrato SAC
$loanData = new LoanData(
    amount: Money::create(50000),
    interestRate: PercentageRate::create(1.8),
    contractSignatureDate: CarbonImmutable::now()->subDays(15),
    firstPaymentDate: CarbonImmutable::now()->addDays(15),
    installments: 12,
    amortizationType: AmortizationType::SAC, // Sistema SAC
    paymentFrequency: PaymentFrequency::MONTHLY,
    operationType: OperationType::LOAN,
    guarantees: [GuaranteeType::JOINT_DEBTOR->value],
    isSimplesNacional: false,
    hasIof: true,
    companyId: $company->id,
    contractStatus: ContractStatus::IN_FILLING,
);

// 3. Criar contrato
$action = app(CreateLoanContractAction::class);
$contractId = $action->execute($loanData);

$contract = \App\Models\Contract::find($contractId);

// 4. Adicionar garantia pessoal
\App\Models\ContractGuarantee::create([
    'contract_id' => $contract->id,
    'type' => 'devedor_solidario',
    'value' => 0,
    'data' => [
        'name' => $person->name,
        'cpf' => $person->cpf,
        'rg' => $person->rg,
        'rg_issuer' => $person->rg_issuer,
        'phone' => $person->phone,
        'email' => $person->email,
    ],
]);

$contract->defaultRules->update([
    'late_payment_interest_rate' => 2.00,
    'penalty_fee_rate' => 2.00,
    'adjustment_index' => 'IPCA',
    'estimated_default_risk' => 'medio',
]);

return "Contrato SAC {$contract->number} criado com 12 parcelas! ID: {$contract->id}";
```

#### Sistema Americano (Juros no Final)

```php
use App\Actions\Contracts\CreateLoanContractAction;
use App\Actions\Contracts\Data\ContractData\LoanData;
use App\Enums\Operations\{AmortizationType, OperationType, PaymentFrequency};
use App\Enums\Contracts\ContractStatus;
use App\Enums\Operations\GuaranteeType;
use App\ValueObjects\{Money, PercentageRate};
use Carbon\CarbonImmutable;

$company = \App\Models\Company::find(2);

$person = \App\Models\Person::factory()->create([
    'company_id' => $company->id,
]);

// Sistema Americano: principal + juros na última parcela
$loanData = new LoanData(
    amount: Money::create(30000),
    interestRate: PercentageRate::create(2.0),
    contractSignatureDate: CarbonImmutable::now()->subDays(30),
    firstPaymentDate: CarbonImmutable::now()->addDays(60),
    installments: 6,
    amortizationType: AmortizationType::AMERICAN,
    paymentFrequency: PaymentFrequency::MONTHLY,
    operationType: OperationType::LOAN,
    guarantees: [GuaranteeType::BUILDING->value],
    isSimplesNacional: false,
    hasIof: true,
    companyId: $company->id,
    contractStatus: ContractStatus::IN_FILLING,
);

$action = app(CreateLoanContractAction::class);
$contractId = $action->execute($loanData);

$contract = \App\Models\Contract::find($contractId);

\App\Models\ContractGuarantee::create([
    'contract_id' => $contract->id,
    'type' => 'devedor_solidario',
    'value' => 0,
    'data' => [
        'name' => $person->name,
        'cpf' => $person->cpf,
        'rg' => $person->rg,
        'phone' => $person->phone,
    ],
]);

// Adicionar garantia de imóvel
\App\Models\BuildingGuarantee::create([
    'contract_id' => $contract->id,
    'value' => 80000,
    'notary_office' => '1º Cartório de Notas de São Paulo',
    'registration_number' => '12345',
    'description' => 'Apartamento residencial com 60m²',
    'owner' => $person->name,
]);

\App\Models\ContractGuarantee::create([
    'contract_id' => $contract->id,
    'type' => 'imovel',
    'value' => 80000,
    'data' => [
        'description' => 'Apartamento residencial com 60m²',
        'owner' => $person->name,
        'notary_office' => '1º Cartório de Notas de São Paulo',
        'registration_number' => '12345',
    ],
]);

$contract->defaultRules->update([
    'late_payment_interest_rate' => 2.50,
    'penalty_fee_rate' => 2.00,
    'estimated_default_risk' => 'medio',
]);

return "Contrato Americano {$contract->number} criado! ID: {$contract->id}";
```

#### Pagamento Quinzenal (PRICE)

```php
use App\Actions\Contracts\CreateLoanContractAction;
use App\Actions\Contracts\Data\ContractData\LoanData;
use App\Enums\Operations\{AmortizationType, OperationType, PaymentFrequency};
use App\Enums\Contracts\ContractStatus;
use App\Enums\Operations\GuaranteeType;
use App\ValueObjects\{Money, PercentageRate};
use Carbon\CarbonImmutable;

$company = \App\Models\Company::find(2);

$person = \App\Models\Person::factory()->create([
    'company_id' => $company->id,
]);

// Pagamento quinzenal
$loanData = new LoanData(
    amount: Money::create(20000),
    interestRate: PercentageRate::create(1.5),
    contractSignatureDate: CarbonImmutable::now(),
    firstPaymentDate: CarbonImmutable::now()->addDays(15),
    installments: 12, // 12 quinzenas = 6 meses
    amortizationType: AmortizationType::PRICE,
    paymentFrequency: PaymentFrequency::BIWEEKLY, // Quinzenal
    operationType: OperationType::LOAN,
    guarantees: [GuaranteeType::ENDORSER->value],
    isSimplesNacional: false,
    hasIof: true,
    companyId: $company->id,
    contractStatus: ContractStatus::IN_FILLING,
);

$action = app(CreateLoanContractAction::class);
$contractId = $action->execute($loanData);

$contract = \App\Models\Contract::find($contractId);

\App\Models\ContractGuarantee::create([
    'contract_id' => $contract->id,
    'type' => 'avalista',
    'value' => 0,
    'data' => [
        'name' => $person->name,
        'cpf' => $person->cpf,
        'phone' => $person->phone,
    ],
]);

$contract->defaultRules->update([
    'late_payment_interest_rate' => 2.00,
    'penalty_fee_rate' => 2.00,
    'estimated_default_risk' => 'baixo',
]);

return "Contrato quinzenal {$contract->number} criado! ID: {$contract->id}";
```

#### Contrato com Alto Risco e Garantias (Imóvel + Veículo)

```php
use App\Actions\Contracts\CreateLoanContractAction;
use App\Actions\Contracts\Data\ContractData\LoanData;
use App\Enums\Operations\{AmortizationType, OperationType, PaymentFrequency};
use App\Enums\Contracts\ContractStatus;
use App\Enums\Operations\GuaranteeType;
use App\ValueObjects\{Money, PercentageRate};
use Carbon\CarbonImmutable;

$company = \App\Models\Company::find(2);

$person = \App\Models\Person::factory()->create([
    'company_id' => $company->id,
    'name' => 'Maria Oliveira',
]);

// Contrato de alto risco com múltiplas garantias
$loanData = new LoanData(
    amount: Money::create(80000),
    interestRate: PercentageRate::create(2.5), // Taxa mais alta devido ao risco
    contractSignatureDate: CarbonImmutable::now()->subDays(60),
    firstPaymentDate: CarbonImmutable::now()->subDays(30),
    installments: 6,
    amortizationType: AmortizationType::PRICE,
    paymentFrequency: PaymentFrequency::MONTHLY,
    operationType: OperationType::LOAN,
    guarantees: [
        GuaranteeType::BUILDING->value,
        GuaranteeType::VEHICLE->value,
        GuaranteeType::JOINT_DEBTOR->value,
    ],
    isSimplesNacional: false,
    hasIof: true,
    companyId: $company->id,
    contractStatus: ContractStatus::IN_FILLING,
);

$action = app(CreateLoanContractAction::class);
$contractId = $action->execute($loanData);

$contract = \App\Models\Contract::find($contractId);

// Adicionar garantia pessoal
\App\Models\ContractGuarantee::create([
    'contract_id' => $contract->id,
    'type' => 'devedor_solidario',
    'value' => 0,
    'data' => [
        'name' => $person->name,
        'cpf' => $person->cpf,
        'rg' => $person->rg,
        'phone' => $person->phone,
    ],
]);

// Adicionar garantia de imóvel
\App\Models\BuildingGuarantee::create([
    'contract_id' => $contract->id,
    'value' => 150000,
    'notary_office' => '1º Cartório de Notas de São Paulo',
    'registration_number' => '12345',
    'description' => 'Apartamento residencial com 80m²',
    'owner' => $person->name,
]);

\App\Models\ContractGuarantee::create([
    'contract_id' => $contract->id,
    'type' => 'imovel',
    'value' => 150000,
    'data' => [
        'description' => 'Apartamento residencial com 80m²',
        'owner' => $person->name,
        'notary_office' => '1º Cartório de Notas de São Paulo',
        'registration_number' => '12345',
    ],
]);

// Adicionar garantia de veículo
\App\Models\VehicleGuarantee::create([
    'contract_id' => $contract->id,
    'value' => 50000,
    'manufacturer' => 'Toyota',
    'model' => 'Corolla XEI',
    'manufacturing_year' => 2020,
    'model_year' => 2021,
    'chassis' => '9BWAA05U76P100001',
    'renavam' => '00123456789',
    'license_plate' => 'ABC-1234',
    'color' => 'Preto',
]);

\App\Models\ContractGuarantee::create([
    'contract_id' => $contract->id,
    'type' => 'veiculo',
    'value' => 50000,
    'data' => [
        'manufacturer' => 'Toyota',
        'model' => 'Corolla XEI',
        'manufacturing_year' => 2020,
        'model_year' => 2021,
        'chassis' => '9BWAA05U76P100001',
        'license_plate' => 'ABC-1234',
        'color' => 'Preto',
    ],
]);

$contract->defaultRules->update([
    'late_payment_interest_rate' => 3.00,
    'penalty_fee_rate' => 2.50,
    'adjustment_index' => 'IPCA',
    'estimated_default_risk' => 'alto',
]);

return "Contrato {$contract->number} criado com garantias de imóvel e veículo! ID: {$contract->id}";
```

#### Para Registrar o Contrato na CRDC (Após Criação)

Se você quiser mudar o status para REGISTERED:

```php
$contract = \App\Models\Contract::find({id});

$contract->update([
    'status' => \App\Enums\Contracts\ContractStatus::REGISTERED,
    'crdc_protocol_number' => 'PROT' . now()->timestamp,
    'crdc_nur' => 'NUR' . now()->timestamp,
    'crdc_registration_status' => 'Aprovado',
]);

return "Contrato {$contract->number} registrado na CRDC!";
```

#### Para Criar Parcelas Atrasadas (Após Criação)

```php
$contract = \App\Models\Contract::find({id});
$loan = $contract->loan;

// Atualizar as 3 primeiras parcelas como atrasadas
$installments = $loan->installments()->orderBy('number')->take(3)->get();

foreach ($installments as $installment) {
    $installment->update([
        'due_date' => now()->subDays(30 * $installment->number),
    ]);

    // Atualizar o payment correspondente
    $installment->payment->update([
        'original_due_date' => now()->subDays(30 * $installment->number),
        'status' => \App\Enums\Payments\PaymentStatus::OVERDUE,
    ]);
}

return "3 parcelas marcadas como atrasadas!";
```

### 4. Confirmar Criação

Após criar o contrato, informe ao usuário:

```
✅ Contrato criado com sucesso!

📋 Detalhes:
- ID: {contract_id}
- Número: {contract_number}
- Valor: R$ {amount}
- Status: {status}
- Sistema: {amortization_type}
- Parcelas: {installments}
- IOF Diário: R$ {iof_daily}
- IOF Adicional: R$ {iof_additional}
- CET Mensal: {monthly_cet}%
- CET Anual: {annual_cet}%
- Empresa: ESC {company_name}

🔗 Acesse em:
http://empresta-legal.test/admin/contratos/{contract_id}/visualizar

⚠️ Lembre-se de fazer login com o usuário correto:
- Email: esc1@el.com
- Senha: password
```

### 5. Cenários Combinados

O usuário pode solicitar combinações de cenários. Exemplos:

- "contrato SAC com 3 parcelas atrasadas"
- "pagamento quinzenal com alto risco"
- "sistema americano com garantia de imóvel"

Adapte o código combinando os templates acima.

## Notas Importantes

- **SEMPRE use CreateLoanContractAction** - garante cálculos corretos
- **Calculadoras automáticas** - IOF, CET, amortização calculados corretamente
- **ContractDefaultRules criado automaticamente** pela Action
- **SEMPRE crie pelo menos 1 garantia pessoal** após a criação
- **Use company_id = 2** para ESC 1 (padrão)
- **NÃO use company_id = 1** (admin do sistema)
- **Status inicial** sempre IN_FILLING, depois altere se necessário
- **Tipos de amortização** disponíveis: PRICE, SAC, AMERICAN, SIMPLE_UNIT_PAYMENT, COMPOUND_UNIT_PAYMENT
- **Frequência de pagamento** disponível: MONTHLY, BIWEEKLY, QUARTERLY

## Sistemas de Amortização

### PRICE

- Parcelas fixas
- Juros decrescentes, amortização crescente
- Mais usado no Brasil

### SAC

- Amortização fixa
- Parcelas decrescentes
- Juros decrescentes

### AMERICAN

- Pagamento apenas de juros durante o período
- Principal + último juros na última parcela

### SIMPLE_UNIT_PAYMENT

- Pagamento único no final
- Juros simples

### COMPOUND_UNIT_PAYMENT

- Pagamento único no final
- Juros compostos

## Tipos de Garantias

**Garantias Pessoais (OBRIGATÓRIAS):**

- `devedor_solidario` - Representante da empresa e devedor solidário (pessoa física)
- `avalista` - Avalista (pessoa física)

**Garantias Reais (OPCIONAIS):**

- `BuildingGuarantee` - Imóvel (casa, apartamento, terreno)
- `VehicleGuarantee` - Veículo (carro, moto, caminhão)
- `ContractGuarantee` (type: recebiveis) - Recebíveis/títulos
- `OtherGuarantee` - Outros bens móveis (máquinas, equipamentos)

## Comandos Úteis Após Criação

```php
// Ver detalhes completos do contrato
$contract = \App\Models\Contract::with([
    'loan.installments',
    'defaultRules',
    'guarantees',
])->find({id});

return [
    'id' => $contract->id,
    'numero' => $contract->number,
    'valor' => $contract->amount,
    'iof_diario' => $contract->iof_daily_amount,
    'iof_adicional' => $contract->iof_additional_amount,
    'cet_mensal' => $contract->monthly_effective_cost,
    'cet_anual' => $contract->annual_effective_cost,
    'parcelas' => $contract->loan->installments->count(),
    'sistema' => $contract->loan->amortization_type->value,
];

// Listar parcelas com detalhes
$contract->loan->installments->map(fn($i) => [
    'numero' => $i->number,
    'vencimento' => $i->due_date->format('d/m/Y'),
    'valor' => 'R$ ' . number_format($i->amount, 2, ',', '.'),
    'juros' => 'R$ ' . number_format($i->interest, 2, ',', '.'),
    'amortizacao' => 'R$ ' . number_format($i->amortization, 2, ',', '.'),
    'saldo_devedor' => 'R$ ' . number_format($i->debtor_balance, 2, ',', '.'),
]);

// Listar todas as garantias do contrato
$contract = \App\Models\Contract::find({id});
return [
    'garantias' => $contract->guarantees->map(fn($g) => [
        'tipo' => $g->type,
        'valor' => $g->value,
        'dados' => $g->data,
    ]),
];
```

## Diferenças da Versão Anterior

✅ **Novo:**

- Usa `CreateLoanContractAction` para criação correta
- Cálculos automáticos de IOF, CET, parcelas
- Amortização calculada corretamente por tipo
- Suporte a todos os sistemas de amortização
- Suporte a diferentes frequências de pagamento

❌ **Removido:**

- Criação manual de parcelas com valores fixos
- Cálculos manuais incorretos
- Criação direta de Contract/Loan sem Action
