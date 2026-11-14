# Cleide Luna Studio — Sistema de Agendamento

Salão de beleza em João Pessoa/PB · *"Realçando a sua beleza"*

Dois módulos num único monólito Laravel:

1. **Site público** (portfólio + agendamento, sem login): a cliente escolhe serviço, profissional, dia e horário; o pedido é **gravado no banco** e, em seguida, a cliente envia os dados ao WhatsApp do salão por um link `wa.me` com a mensagem pronta.
2. **Painel interno** (`/painel`) com papéis **admin** (dona do salão) e **profissional**.

Sem API do WhatsApp, sem filas, sem mensagens automáticas.

---

## Stack

- PHP 8.2+ (testado em 8.2; compatível com 8.3) · Laravel 12
- PostgreSQL 16+ (testado em 18) com extensões `btree_gist` e `pg_trgm` (criadas pela migration)
- Blade + Tailwind CSS 3 + Alpine.js (`@alpinejs/intersect`, `@alpinejs/mask`) via Vite
- Laravel Breeze (Blade) só para autenticação — **sem rota pública de registro**
- `barryvdh/laravel-dompdf` (PDF) e `maatwebsite/excel` (Excel)
- Timezone `America/Sao_Paulo` · locale `pt_BR` · moeda BRL

## Instalação

```bash
composer install
npm install && npm run build

cp .env.example .env
php artisan key:generate
# edite no .env: DB_* (PostgreSQL) e ADMIN_PASSWORD (senha do admin criado pelo seeder)

php artisan migrate --seed      # em APP_ENV=local também cria ~30 clientes e ~120 agendamentos de exemplo
php artisan storage:link
php artisan serve
```

Acessos criados pelo seeder (senha = `ADMIN_PASSWORD`):

| Papel | E-mail |
|---|---|
| Admin | `admin@cleideluna.com.br` |
| Profissional (exemplo) | `ana@cleideluna.com.br` |
| Profissional (exemplo) | `bia@cleideluna.com.br` |

> Troque as senhas em **Meu perfil** e remova/edite as profissionais de exemplo em **Profissionais** antes de ir para produção.

### Cron (rotinas internas)

```
* * * * * cd /caminho/do/projeto && php artisan schedule:run >> /dev/null 2>&1
```

| Comando | Frequência | O que faz |
|---|---|---|
| `agendamentos:expirar-pendentes` | a cada 30 min | se `expirar_pendente_horas > 0`, cancela pendentes não confirmados (`cancelado_por = sistema`) |
| `lista-espera:expirar` | diário 00:10 | expira entradas com data passada |
| `clientes:recalcular` | semanal (seg 03:00) e manual | recalcula colunas desnormalizadas das clientes |

Nenhuma rotina envia mensagens: tudo é só banco de dados.

### Testes

```bash
# banco separado para testes (veja phpunit.xml): cleide_salao_test
php artisan test
```

81 testes cobrem: disponibilidade (expediente, intervalo, bloqueios, conflito, serviço longo no fim do dia, antecedência), transições de status, conclusão com pagamento dividido e comissão, estorno, faltas e bloqueio automático, reagendamento (cadeia, limite, prazo), **concorrência real com duas conexões ao PostgreSQL** provando que a constraint impede agendamento duplo, cliente nunca duplicada pelo telefone, mesclagem, `WhatsAppLink` (mensagem e encoding), permissões por papel e abertura de todas as telas do painel com `preventLazyLoading` ativo (sem N+1).

## Logo e fotos

- `public/images/logo.png` é um **placeholder** — substitua pelo logo oficial.
- A galeria vem com placeholders (`public/images/placeholders`). Envie as fotos reais em **Painel → Site → Galeria** (ficam em `storage/app/public/galeria`). Nada é copiado do Instagram.

---

## Estrutura

```
app/
  Enums/            PapelUsuario, StatusAgendamento (podeIrPara), FormaPagamento, OrigemCliente, OrigemAgendamento, CanceladoPor
  Services/         DisponibilidadeService, AgendamentoService, ReagendamentoService, ClienteService, FinanceiroService, ListaEsperaService
  Support/          Telefone, Setting (cache), WhatsAppLink (mensagens + links wa.me), CalendarioLinks (Google + .ics), Dinheiro, Atividade
  Models/Concerns/  RegistraAtividade (log de atividades), UsaTimestampTz
  Policies/         AgendamentoPolicy, ClientePolicy
  Http/Controllers/ Site/*, Painel/Admin/*, Painel/Profissional/*, Painel/Concerns/AcoesAgendamento
  Console/Commands/ ExpirarPendentes, RecalcularClientes, ExpirarListaEspera
  Exports/          Relatorio (Excel/PDF)
```

## Rotas principais

- Públicas: `/`, `GET /horarios-disponiveis`, `POST /agendar` (throttle 5/min), `POST /lista-espera`, `/agendamento/confirmado/{token}`, `/agendamento/{token}/calendario.ics`
- Painel: `/painel/admin/*` (role:admin) e `/painel/profissional/*` (role:profissional). Após o login, cada papel vai para sua área.

---

## Decisões de arquitetura

- **Telefone como identidade da cliente**: sem login, é o dado mais confiável que a cliente sempre informa; normalizado (`55` + DDD + número) e `UNIQUE`, criado com `createOrFirst` (savepoint), então a cliente nunca é duplicada pelo site, nem com requisições simultâneas. O nome **nunca é sobrescrito** automaticamente — divergências viram alerta (“Atualizar cadastro” / “Manter”). O site nunca revela se o telefone já é cliente (LGPD). Mesma pessoa com dois números é resolvida pela lista de **possíveis duplicadas** (`pg_trgm`, similaridade ≥ 0,6) + mesclagem; agendar depois com o número antigo cai na cliente principal.
- **Exclusion constraint no PostgreSQL** (`agendamentos_sem_conflito`, `btree_gist` sobre `profissional_id` + `periodo tstzrange`): garante zero conflito mesmo com duas clientes clicando no mesmo horário; a validação na aplicação é só UX. A violação (SQLSTATE `23P01`) vira “Esse horário acabou de ser reservado. Escolha outro.”. `cancelado`, `reagendado` e `nao_compareceu` ficam fora da constraint e liberam o horário.
- **Colunas desnormalizadas na cliente** (visitas, gasto, faltas…): listagem e filtros rápidos; consistência por transação + `clientes:recalcular` (fonte da verdade: `agendamentos` + `pagamentos`).
- **Um serviço por agendamento**: simplifica o MVP; dois serviços = dois agendamentos sequenciais. Revisitar (`agendamento_servicos`) quando houver demanda.
- **Comissão como snapshot**: o percentual é copiado na conclusão; mudar o percentual da profissional não altera comissões passadas.
- **Monólito Blade**: menor custo de manutenção para um único salão; sem API separada até existir app mobile.
- **Grava primeiro, envia depois**: a cliente pode não clicar em enviar o WhatsApp; o agendamento nunca depende disso e o salão o vê no painel (alerta “agendaram pelo site mas não enviaram o WhatsApp”, via `navigator.sendBeacon`). `wa.me` = custo zero, integração zero.
- **Reagendamento cria um novo registro** (`CL-XXXXX-R1`, `-R2`…) ligado ao antigo por `reagendado_de_id`/`reagendado_para_id`: auditoria completa, o antigo sai da constraint e libera o horário.
- **Datas em `timestamptz`** gravadas com offset explícito; a sessão do PostgreSQL usa o mesmo fuso da aplicação.

### Colunas adicionadas além do modelo original

- `agendamentos.reagendado_por` e `agendamentos.motivo_reagendamento` — registram quem pediu e o motivo do reagendamento (requisito 7.1).
- `lista_espera.vaga_liberada_em` — marca a entrada quando um horário daquele dia é liberado (destaque nos alertas).
- Configuração `profissional_pode_cancelar` (tabela 7: cancelamento pela profissional “se configurado”) e `sobre` (texto editável da seção Sobre).

### Preparado para o futuro (não implementado)

Sinal via Pix para serviços longos, múltiplos serviços por agendamento, pacotes/fidelidade, estoque e cursos. A separação em Services, o `StatusAgendamento` e a tabela `pagamentos` (n por agendamento) permitem adicioná-los sem reescrever o núcleo.

## Observação sobre emojis no WhatsApp

A mensagem é montada no backend com `rawurlencode` (UTF-8 correto, coberto por teste). Links com texto usam `https://api.whatsapp.com/send?phone=...&text=...` direto, porque o redirecionamento do `wa.me` troca emojis por `�` no WhatsApp Web/Desktop. Links sem texto continuam em `wa.me`.
