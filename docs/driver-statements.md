# Extratos por motorista e portagens

## Portagens

Em Toll Payment, o campo anteriormente designado `Card` aceita o **Código
interno** (`drivers.code`) ou o **Nome completo** (`drivers.name`) do cadastro.
O campo CSV continua a chamar-se `card`, pelo que o mapeamento existente se
mantém. Códigos de cartões antigos também são aceites quando identificam um
único motorista. Espaços nas extremidades e maiúsculas/minúsculas são ignorados.

Uma identificação desconhecida ou com vários motoristas possíveis é rejeitada;
não se dá prioridade silenciosa ao código, nome ou cartão. A importação de cada
ficheiro é transacional: uma linha inválida impede a gravação de todo esse
ficheiro. Ficheiros válidos de uma importação múltipla podem ser gravados.

Novos pagamentos guardam `driver_id`, mantendo a associação quando o motorista
muda de nome, cartão ou empresa. Pagamentos antigos não são reescritos: os que
não têm `driver_id` são resolvidos durante o cálculo. Portagens antigas não
resolvidas geram um aviso com o ID do pagamento e impedem a gravação do extrato
até serem corrigidas. Não é possível inferir uma atribuição histórica de cartão
apenas a partir do cadastro atual.

## Faturação e extratos

O extrato individual procura todas as atividades da semana pelos identificadores
Uber/Bolt do motorista, independentemente da empresa atual. Identificadores vazios
não associam atividades. A discriminação usa a empresa registada na atividade
importada, não a empresa atual do motorista. Identificadores de plataforma devem
continuar a identificar a mesma pessoa; não se tenta juntar cadastros diferentes.

O escalão contratual é escolhido sobre a faturação semanal combinada. Descontos,
gorjetas, abastecimentos, portagens, ajustes e taxa administrativa são aplicados
uma vez no extrato. A tabela «Faturação por empresa» discrimina valores **já
incluídos** nos ganhos e gorjetas; não acrescenta créditos. A visão «Todos» e os
relatórios de empresa mantêm o âmbito da empresa selecionada.

Ecrã, PDF individual, impressão em lote e gravação utilizam o mesmo resultado.
Os períodos disponíveis incluem faturação de empresas anteriores dos motoristas
da empresa selecionada.

## Histórico e ajustes manuais

Extratos já gravados continuam a apresentar o snapshot original, sem recálculo
automático. Novos snapshots têm âmbito motorista/semana e são reutilizados após
uma mudança de empresa. Antes de gravar um consolidado, a existência de um
snapshot antigo noutra empresa da mesma semana exige revisão para evitar um
segundo pagamento. Não é feita qualquer migração dos snapshots antigos.

Ajustes manuais existentes são mantidos. Quando há faturação importada de outras
empresas e créditos manuais, aparece um aviso: é necessário rever os créditos
que compensavam faturação anteriormente omitida. O sistema não pode determinar
essa correspondência apenas pelo texto ou pelo montante do ajuste.

## Instalação e validação

Aplicar a migração aditiva `2026_09_08_220000_add_driver_to_toll_payments.php`
antes de usar o código. Foi aplicada à sandbox e, com autorização explícita,
à produção em 8 de setembro de 2026, após backup. Os 17 340 pagamentos existentes
foram verificados sem alterações às colunas originais. Não correr
`migrate:fresh` numa base com dados.

Testes isolados em SQLite em memória:

```sh
php vendor/phpunit/phpunit/phpunit tests/Feature/DriverStatementTest.php
```

Cobrem uma empresa, mudança de empresa, várias empresas, escalão combinado,
valores acima de 1 000 €, identificadores vazios, código/nome/cartão, ambiguidades,
importação atómica, ajustes manuais, snapshots, PDF e prevenção de duplicações.
