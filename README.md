# Meu Campeonato

API REST que simula um campeonato de futebol no formato mata-mata com 8 times: quartas de final, semifinais, disputa de terceiro lugar e final. Os placares de cada jogo não são inventados pela aplicação — vêm do script Python `teste.py` (o mesmo do enunciado), que a API executa como um serviço externo de previsão. Cada time acumula pontos ao longo do torneio (+1 por gol marcado, −1 por gol sofrido) e, quando um jogo termina empatado, essa pontuação acumulada é o primeiro critério de desempate. Ao fim do campeonato, o resultado alimenta um ranking histórico por time.

O projeto foi construído com Laravel 13 sobre uma arquitetura hexagonal com DDD tático: as regras do torneio vivem em PHP puro, sem dependência do framework, e o Laravel entra apenas nas bordas (HTTP, persistência, execução do script).

**Índice:** [Como executar](#como-executar) · [Testes](#como-rodar-os-testes) · [Arquitetura](#arquitetura) · [Decisões](#decisões-de-arquitetura) · [Regras de negócio](#regras-de-negócio) · [Endpoints](#endpoints) · [Extensões possíveis](#extensões-possíveis)

---

## Como executar

O único pré-requisito é **Docker** com Compose v2. PHP, Composer, MySQL e Python ficam todos dentro dos containers.

```bash
git clone <url-do-repositorio> MeuCampeonato
cd MeuCampeonato

cp .env.example .env
docker compose up -d --build
```

O `--build` na primeira execução leva alguns minutos (instala as dependências do Composer dentro da imagem). O que acontece em seguida, sem nenhum passo manual:

1. o container `mysql` sobe e cria os schemas `meu_campeonato` e `meu_campeonato_test` (via `docker/mysql-init/`);
2. o build da imagem `app` já rodou `php artisan key:generate` e `php artisan jwt:secret`, então `APP_KEY` e `JWT_SECRET` estão preenchidos;
3. o entrypoint (`docker/entrypoint.sh`) alinha as variáveis `DB_*` do container com o `.env`, fica tentando `php artisan migrate --force` até o banco aceitar conexões e só então sobe o servidor;
4. o container `worker` (mesma imagem, `CONTAINER_ROLE=worker`) espera o schema ficar acessível e sobe `php artisan queue:work` para consumir a fila — é ele quem atualiza o ranking histórico quando um campeonato termina.

O `cp .env.example .env` do passo acima é para uso local (rodar testes ou `artisan` a partir da máquina host): a imagem gera o próprio `.env` durante o build e não copia o do host.

A API responde em **`http://localhost:8000/api/v1`**. Para conferir que subiu:

```bash
curl -i http://localhost:8000/up          # health check do Laravel
docker compose logs -f app                # acompanhar o boot
```

### Obtendo um token

Todos os endpoints, exceto `register` e `login`, exigem um JWT no header `Authorization: Bearer <token>`.

```bash
# 1. criar a conta (201)
curl -s -X POST http://localhost:8000/api/v1/auth/register \
  -H 'Content-Type: application/json' \
  -d '{"name":"Avaliador","email":"avaliador@irroba.com.br","password":"secret123","password_confirmation":"secret123"}'

# 2. autenticar e guardar o token
TOKEN=$(curl -s -X POST http://localhost:8000/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"avaliador@irroba.com.br","password":"secret123"}' | php -r 'echo json_decode(stream_get_contents(STDIN))->access_token;')

curl -s http://localhost:8000/api/v1/auth/me -H "Authorization: Bearer $TOKEN"
```

### Fluxo completo em 6 passos

```bash
# 8 times
for T in Alfa Bravo Charlie Delta Echo Foxtrot Golf Hotel; do
  curl -s -X POST http://localhost:8000/api/v1/teams \
    -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
    -d "{\"name\":\"$T\"}"
done

# campeonato (id 1, supondo base limpa)
curl -s -X POST http://localhost:8000/api/v1/championships \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{"name":"Copa Irroba"}'

# inscrição, sorteio das quartas, simulação até a final, resultado e ranking
curl -s -X POST http://localhost:8000/api/v1/championships/1/teams \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{"team_ids":[1,2,3,4,5,6,7,8]}'
curl -s -X POST http://localhost:8000/api/v1/championships/1/start    -H "Authorization: Bearer $TOKEN"
curl -s -X POST http://localhost:8000/api/v1/championships/1/simulate -H "Authorization: Bearer $TOKEN"
curl -s      http://localhost:8000/api/v1/rankings                    -H "Authorization: Bearer $TOKEN"
```

### Postman

Em `docs/postman/` estão a collection e o environment (formato v2.1). Importe os dois arquivos, selecione o environment **Meu Campeonato — Local** e execute as pastas na ordem em que aparecem: `Auth` → `Teams` → `Championships` → `Rankings` → `Casos de erro`. O request de login já grava o `access_token` na variável `{{token}}`, então os demais requests funcionam sem copiar nada à mão.

### Rodando sem Docker (opcional)

Precisa de PHP 8.3+ com `pdo_mysql`, Composer, Python 3 e um MySQL 8 acessível:

```bash
composer install
cp .env.example .env && php artisan key:generate && php artisan jwt:secret
php artisan migrate
php artisan serve
```

---

## Como rodar os testes

A suíte roda contra **MySQL de verdade** (schema `meu_campeonato_test`), nunca SQLite — o motivo está em [Decisões](#decisões-de-arquitetura).

```bash
# dentro do container (usa o MySQL do compose)
docker compose exec app php artisan test

# ou da máquina host, com o container do MySQL no ar (porta 3306 exposta)
php artisan test
```

Ferramentas de qualidade, as mesmas que rodam no CI (`.github/workflows/ci.yml`):

```bash
./vendor/bin/pint --test                            # estilo (Laravel preset)
./vendor/bin/phpstan analyse --memory-limit=1G      # Larastan, nível 6
```

A suíte tem 70 testes (159 asserções) divididos em:

- **`tests/Unit/Domain`** — regras do torneio em PHP puro, sem banco e sem HTTP: máquina de estados, sorteio, cadeia de desempate, pontuação, value objects. Rodam em milissegundos porque não tocam em infraestrutura.
- **`tests/Feature`** — endpoints, persistência do agregado, consulta SQL de classificação e a execução real do `teste.py`.

Nos testes de simulação o `ScoreGeneratorInterface` é trocado por um `FakeScoreGenerator` com placares fixos (`tests/Doubles/`). É isso que permite afirmar, com determinismo, que "empate 2×2 na semifinal foi decidido por pontuação acumulada" — algo impossível de testar de forma confiável chamando um script que sorteia números.

---

## Arquitetura

### As três camadas

```
 ┌──────────────────────────────────────────────────────────────────────────┐
 │  app/Http  ·  app/Infrastructure          (adaptadores — dependem de tudo)│
 │                                                                          │
 │   Controllers ─ FormRequests ─ Resources ─ Policies ─ JWT (auth:api)     │
 │   EloquentChampionshipRepository   PythonScoreGenerator   RandomShuffler │
 │   bootstrap/app.php: DomainException → 409 · ScoreGeneration → 502       │
 └───────────────────────────────┬──────────────────────────────────────────┘
                                 │ implementa as portas / chama casos de uso
 ┌───────────────────────────────▼──────────────────────────────────────────┐
 │  app/Application                          (orquestração — sem HTTP)      │
 │                                                                          │
 │   CreateChampionship · EnrollTeams · StartChampionship                   │
 │   SimulatePhase · SimulateChampionship                                   │
 │   abrem a transação, carregam o agregado, salvam, despacham o evento     │
 └───────────────────────────────┬──────────────────────────────────────────┘
                                 │ usa apenas PHP puro
 ┌───────────────────────────────▼──────────────────────────────────────────┐
 │  app/Domain                     (regras — não conhece Laravel nem MySQL) │
 │                                                                          │
 │   Tournament/    Championship (agregado + máquina de estados)            │
 │                  Game · TeamEntry · Phase · DecidedBy · TiebreakerMode   │
 │   Scoring/       Score · PenaltyShootout (VOs) · StandingsCalculator     │
 │                  ScoringPolicyInterface ← StandardScoringPolicy          │
 │   Tiebreaker/    TiebreakerChain: Points → [Penalties] → RegistrationOrder│
 │   Contracts/     ScoreGeneratorInterface · ShufflerInterface             │
 │                  ChampionshipRepositoryInterface          ← as "portas"  │
 │   Events/        ChampionshipFinished                                    │
 └──────────────────────────────────────────────────────────────────────────┘
                                 │
                                 │ evento despachado após o commit
                                 ▼
              app/Listeners/UpdateTeamStatistics  →  tabela team_statistics
                                                     (read model do GET /rankings)
```

A regra é uma só: **as setas de dependência apontam para dentro**. `app/Domain` não tem um único `use Illuminate\...`; o `Championship` não sabe se é carregado de MySQL, de um arquivo ou de um array de teste, e não sabe se o placar veio do Python, de um mock ou de um gerador aleatório.

### Padrões aplicados

| Padrão | Onde | Por quê |
| --- | --- | --- |
| Agregado + máquina de estados | `Domain/Tournament/Championship`, `ChampionshipStatus::next()` | Um único ponto de entrada garante as invariantes: 8 times, uma fase por vez, transição `draft → quarter_finals → semi_finals → finals → finished`. |
| Value Objects | `Score`, `PenaltyShootout` | Validam a si mesmos na construção (gols não negativos, pênaltis nunca empatam) e eliminam a dupla `int $home, int $away` solta pelo código. |
| Strategy + Chain of Responsibility | `Tiebreaker/*` | Cada critério de desempate é uma classe que decide ou devolve `null` e passa adiante. Trocar a ordem ou incluir um critério novo não mexe no agregado. |
| Strategy (pontuação) | `ScoringPolicyInterface` | A fórmula "+1 marcado / −1 sofrido" é uma política, não uma verdade universal. Ver [Extensões possíveis](#extensões-possíveis). |
| Ports & Adapters | `ScoreGeneratorInterface`, `ShufflerInterface`, `ChampionshipRepositoryInterface` | As três dependências voláteis do domínio — script externo, aleatoriedade e banco — são interfaces declaradas pelo domínio e implementadas pela infraestrutura. |
| Domain Event + Read Model | `ChampionshipFinished` → `UpdateTeamStatistics` → `team_statistics` | O ranking histórico é um efeito colateral do fim do campeonato, não responsabilidade da simulação. `GET /rankings` lê uma tabela pronta, sem varrer jogos. O listener é processado em fila (ver [Decisões](#decisões-de-arquitetura)). |
| Repository | `EloquentChampionshipRepository` | Reconstitui o agregado a partir de 3 tabelas e o salva de volta em uma operação. O domínio conversa com uma interface de 3 métodos. |

### O que deliberadamente não usei

Tão importante quanto escolher padrões é saber quando eles custam mais do que entregam:

- **CQRS completo** (buses de comando/consulta, handlers registrados) — os casos de uso aqui são cinco e cada endpoint chama exatamente um. Um bus só acrescentaria indireção. O que aproveitei da ideia foi a separação de leitura: consultas pesadas (classificação, ranking) vão direto ao banco via SQL, sem passar pelo agregado.
- **Event Sourcing** — seria elegante para um torneio (a sequência de jogos é literalmente um log de eventos), mas o requisito é consultar o estado atual do campeonato, não reconstruir o passado nem auditar mudanças. O custo de projeções e versionamento não se paga. A rastreabilidade que o problema pede eu resolvi com uma coluna: `games.decided_by` registra se o jogo saiu no placar, na pontuação, nos pênaltis ou na ordem de inscrição.
- **Múltiplos bounded contexts** — existe um único domínio coeso aqui (o torneio). Times e usuários são entidades de apoio, não subdomínios com linguagem própria. Criar contextos separados com tradução entre eles seria arquitetura de enfeite.
- **Repositório para `Team` e `User`** — não são agregados com invariantes; são CRUD. Eloquent direto no controller é a solução honesta, e insistir em abstrair tudo só por simetria deixaria o código pior.

---

## Decisões de arquitetura

**Por que hexagonal em um teste técnico.** O enunciado tem regras de verdade — sorteio, re-sorteio, pontuação acumulada, cadeia de desempate — e uma dependência externa não determinística (`teste.py`). Isolar isso do framework me deu duas coisas concretas: testes de domínio que rodam sem banco e sem HTTP, e a possibilidade de substituir o gerador de placares por um duplo de teste para provar caminhos que, com números aleatórios, eu só conseguiria observar por sorte.

**Por que agregado com máquina de estados.** Existem transições que o banco não consegue proibir: simular a final antes das quartas, inscrever um time num campeonato já iniciado, simular duas vezes a mesma fase. Concentrar isso no `Championship`, com `ChampionshipStatus::next()` como única forma de avançar, torna o estado ilegal irrepresentável — e a violação vira uma exceção de domínio, não um `if` espalhado por controllers.

**409 vs 422.** Uso 422 para o que está errado *na requisição* (nome faltando, `team_ids` com id inexistente, `tiebreaker_mode` desconhecido) — o cliente corrige o payload e repete. Uso 409 para o que está errado *no estado do recurso* (iniciar com 6 times, inscrever depois do sorteio, simular um campeonato já encerrado) — o payload está perfeito, o pedido é que é incompatível com a situação atual. Na prática: toda `ChampionshipRuleViolation` vira 409 em um único ponto (`bootstrap/app.php`); nenhuma validação de FormRequest sabe da existência do domínio.

**Pênaltis fora da pontuação acumulada.** No futebol, gols de disputa de pênaltis não entram em estatística de gols — e aqui há uma razão a mais: a pontuação acumulada é ela própria um critério de desempate. Se os pênaltis somassem, um empate 3×3 seguido de 5×4 nas penalidades inflaria a pontuação dos dois times e distorceria os desempates seguintes. Os pênaltis são gravados em colunas separadas (`penalty_home`, `penalty_away`), aparecem na resposta da API e ficam fora do `StandingsCalculator`.

**Semifinais re-sorteadas.** O enunciado sorteia os confrontos das quartas e sorteia de novo os confrontos das semifinais entre os quatro vencedores. É diferente do chaveamento fixo, em que o vencedor de QF1 já sabe que enfrenta o vencedor de QF2. Implementei o re-sorteio (`Championship::drawSemiFinals()`), porque é o que está escrito. A disputa de terceiro lugar, essa sim, é determinística: os dois perdedores das semifinais.

**MySQL nos testes, não SQLite.** SQLite em memória deixaria a suíte mais rápida, mas o SQL é critério de avaliação e eu queria que os testes exercitassem o banco que roda em produção. Valeu a pena de forma mensurável: a consulta de classificação e a ordenação do ranking somam e subtraem colunas `UNSIGNED`, e no MySQL `goals_for - goals_against` com resultado negativo estoura em erro de out-of-range — comportamento que o SQLite simplesmente não reproduz. Os dois bugs só apareceram porque o teste rodava em MySQL, e a correção (`CAST(... AS SIGNED)`) está em `EloquentChampionshipRepository::standings()` e em `RankingController`.

**`artisan serve` em vez de nginx + php-fpm.** É uma troca consciente: com `artisan serve` o container é uma imagem só, sem arquivo de configuração de servidor, e quem avalia sobe tudo com um comando. O custo é que ele processa uma requisição por vez e não serve estáticos com eficiência — inaceitável em produção, irrelevante para uma API que será exercitada por curl e Postman. Em produção eu trocaria por nginx + php-fpm (ou FrankenPHP), o que não muda uma linha do código da aplicação.

Tem uma pegadinha nessa escolha que vale registrar: o `serve` repassa ao servidor embutido do PHP apenas uma lista fixa de variáveis de ambiente (`ServeCommand::$passthroughVariables`), então o `DB_HOST=mysql` que o Compose injeta chega ao artisan (as migrations rodavam) mas não às requisições HTTP, que caíam no `DB_HOST` do `.env` da imagem. Por isso o `docker/entrypoint.sh` grava as variáveis `DB_*` do ambiente no `.env` antes de subir o servidor — CLI e HTTP passam a falar com o mesmo banco.

**Modo default fiel ao enunciado, extras opt-in.** O desempate descrito no enunciado é pontuação acumulada e, persistindo o empate, ordem de inscrição. É esse o comportamento padrão. A disputa de pênaltis — mais realista e mais interessante de implementar — existe, mas só entra se o campeonato for criado com `tiebreaker_mode: "penalties"`. Assim eu demonstro a extensibilidade da cadeia de desempate sem que quem avalia precise adivinhar por que o resultado não bate com o enunciado.

**Ranking em fila, não na requisição.** Atualizar o `team_statistics` é consequência do campeonato terminar, não parte da transação de simular: o usuário que chamou `/simulate` não precisa esperar a estatística histórica ser recalculada, e uma falha nesse recálculo não deve derrubar uma simulação que já aconteceu. Por isso o `UpdateTeamStatistics` implementa `ShouldQueue`: o evento `ChampionshipFinished` (que carrega apenas o id do campeonato e as posições finais — payload pequeno e serializável) vira um job na fila, e um container dedicado (`worker`, rodando `queue:work`) o consome com retry automático (`tries=3`, `backoff=5`); o que estourar as tentativas fica em `failed_jobs` para reprocesso. O driver é `database` — fila na própria tabela `jobs` do MySQL, sem adicionar Redis ao compose só para impressionar; como o Laravel abstrai o driver, trocar para Redis/SQS em produção é mudar `QUEUE_CONNECTION`, não código. Nos testes a suíte roda com `QUEUE_CONNECTION=sync` (o job executa inline, mantendo os testes de ranking determinísticos) e um teste com `Queue::fake()` prova que o listener é de fato enfileirado. O custo assumido é consistência eventual: por alguns instantes após o fim do campeonato, `GET /rankings` pode ainda não refletir o título — aceitável para estatística histórica.

**JWT em vez de Sanctum.** A API é stateless e consumida por cliente HTTP puro, sem cookies nem SPA de mesmo domínio. O `php-open-source-saver/jwt-auth` entrega token autocontido com `refresh`, que é exatamente o modelo de sessão que uma API deste tipo precisa.

---

## Regras de negócio

### Ciclo de vida

```
  draft ──────────► quarter_finals ──────────► semi_finals ──────────► finals ──────────► finished
        POST /start                POST /phases/simulate    (re-sorteio)      (3º lugar + final)
   (exige 8 times,               (4 jogos, decide os        (2 jogos entre
    sorteia as quartas)           4 semifinalistas)          os 4 vencedores)
```

- Um campeonato precisa de **exatamente 8 times** para começar. Menos que isso → 409; tentar inscrever o nono → 409; inscrever o mesmo time duas vezes → 409.
- Os times são inscritos em `draft` e recebem uma **ordem de inscrição** (1 a 8) — que é o critério final de desempate, então a ordem importa e é imutável.
- `POST /start` embaralha os 8 times e monta as 4 quartas de final.
- `POST /phases/simulate` joga a fase atual; `POST /simulate` repete isso até `finished`. Na fase `finals` os dois jogos (terceiro lugar e final) acontecem na mesma chamada.
- Quando o campeonato termina, o agregado grava 1º, 2º, 3º e 4º lugares e dispara `ChampionshipFinished`, que atualiza o `team_statistics` de todos os 8 times.

### Placares

Cada jogo chama o `teste.py`, que imprime dois números de 0 a 7 (gols do mandante e do visitante). A pontuação acumulada de um time é `gols marcados − gols sofridos`, somada sobre todos os jogos já disputados no campeonato. Não há pontos por vitória: um 4×0 vale +4 e um 0×4 vale −4.

Se o script não existir, falhar, estourar 2s de timeout ou imprimir algo que não sejam dois inteiros, a API responde **502** com a mensagem do erro — a falha é de um serviço externo, não do cliente.

### Cadeia de desempate

Mata-mata não admite empate, então quando o placar empata a decisão passa por uma cadeia de estratégias, cada uma podendo decidir ou passar adiante:

```
modo "default"    Pontuação acumulada  →  Ordem de inscrição
modo "penalties"  Pontuação acumulada  →  Disputa de pênaltis  →  Ordem de inscrição
```

A disputa de pênaltis usa o mesmo `teste.py` em morte súbita: gera um par de números e, se vierem iguais, gera de novo, até 5 tentativas; se as 5 empatarem, cai para a ordem de inscrição. **Os gols de pênalti nunca somam na pontuação acumulada.**

Todo jogo grava em `decided_by` como foi decidido: `score`, `points`, `penalties` ou `registration_order`.

### Exemplo numérico

Oito times inscritos nesta ordem: 1 Alfa, 2 Bravo, 3 Charlie, 4 Delta, 5 Echo, 6 Foxtrot, 7 Golf, 8 Hotel. As quartas saem no sorteio e o `teste.py` devolve:

| Jogo | Placar | Decisão | Pontuação após o jogo |
| --- | --- | --- | --- |
| QF1 Alfa × Bravo | 3 × 1 | `score` — Alfa | Alfa **+2**, Bravo **−2** |
| QF2 Charlie × Delta | 0 × 2 | `score` — Delta | Charlie **−2**, Delta **+2** |
| QF3 Echo × Foxtrot | 1 × 0 | `score` — Echo | Echo **+1**, Foxtrot **−1** |
| QF4 Golf × Hotel | 2 × 2 | empate → cadeia | Golf **0**, Hotel **0** |

No QF4 a pontuação acumulada de Golf e Hotel é 0 a 0 — o empate 2×2 soma +2−2 para os dois lados e não desempata nada. O `PointsTiebreaker` devolve `null` e passa adiante. No modo default o próximo critério é a ordem de inscrição: **Golf (7) < Hotel (8), Golf classifica** com `decided_by: registration_order`. No modo `penalties`, antes disso entraria a disputa: se o script devolvesse 4×2, Golf venceria com `decided_by: penalties` e `penalty_home: 4, penalty_away: 2` — e a pontuação de ambos continuaria 0, porque pênaltis não somam.

Semifinalistas: **Alfa, Delta, Echo, Golf** — e agora vem o re-sorteio. Suponha que saia SF1 Echo × Alfa e SF2 Golf × Delta:

| Jogo | Placar | Pontuação antes | Decisão |
| --- | --- | --- | --- |
| SF1 Echo × Alfa | 1 × 1 | Echo +1, Alfa +2 | empate → `points` — **Alfa** (2 > 1) |
| SF2 Golf × Delta | 3 × 1 | Golf 0, Delta +2 | `score` — **Golf** |

Aqui o critério do enunciado resolve sozinho: Alfa chegou à semifinal com saldo maior (venceu por 3×1 nas quartas, Echo por 1×0) e leva o empate. Na final, Alfa × Golf; na disputa de terceiro, Echo × Delta.

---

## Endpoints

Base: `http://localhost:8000/api/v1`. Tudo que não é `register`/`login` exige `Authorization: Bearer <token>`. Um usuário só enxerga e manipula os próprios times e campeonatos — acessar recurso de outro usuário devolve **403**.

| Método | Rota | Descrição | Status |
| --- | --- | --- | --- |
| `POST` | `/auth/register` | Cria a conta. | `201` · `422` |
| `POST` | `/auth/login` | Devolve `access_token`, `token_type` e `expires_in`. | `200` · `401` credenciais inválidas · `422` |
| `POST` | `/auth/refresh` | Renova o token do usuário autenticado. | `200` · `401` |
| `GET` | `/auth/me` | Dados do usuário autenticado. | `200` · `401` |
| `GET` | `/teams` | Lista os times do usuário. | `200` · `401` |
| `POST` | `/teams` | Cria um time (nome único por usuário). Devolve header `Location`. | `201` · `401` · `422` |
| `GET` | `/teams/{id}` | Detalhe de um time. | `200` · `401` · `403` · `404` |
| `POST` | `/championships` | Cria em `draft`. Aceita `tiebreaker_mode`: `default` (padrão) ou `penalties`. Devolve header `Location`. | `201` · `401` · `422` |
| `GET` | `/championships` | Lista os campeonatos do usuário. Filtro opcional `?status=draft\|quarter_finals\|semi_finals\|finals\|finished`. | `200` · `401` |
| `GET` | `/championships/{id}` | Campeonato completo: times com ordem de inscrição, jogos em ordem cronológica, `standings` (pontuação acumulada) e `classification` (top 4, preenchido quando `finished`). | `200` · `401` · `403` · `404` |
| `POST` | `/championships/{id}/teams` | Inscreve times (`team_ids`, até 8). | `200` · `401` · `403` · `404` · `409` fora de `draft`, limite de 8 ou time repetido · `422` |
| `POST` | `/championships/{id}/start` | Sorteia as quartas e avança para `quarter_finals`. | `200` · `401` · `403` · `404` · `409` sem 8 times ou já iniciado |
| `POST` | `/championships/{id}/phases/simulate` | Simula apenas a fase atual (em `finals`, joga terceiro lugar e final). | `200` · `401` · `403` · `404` · `409` em `draft`/`finished` · `502` falha do `teste.py` |
| `POST` | `/championships/{id}/simulate` | Simula da fase atual até o fim. | `200` · `401` · `403` · `404` · `409` em `draft`/`finished` · `502` |
| `GET` | `/rankings` | Ranking histórico dos times do usuário: campeonatos disputados, títulos, vices, terceiros lugares, gols pró/contra e saldo. Ordenado por títulos, vices, terceiros e saldo. | `200` · `401` |

Erros seguem sempre a mesma forma: `{"message": "..."}` — acrescido de `errors` nas respostas 422, no formato padrão do Laravel.

---

## Extensões possíveis

**Outra política de pontuação.** A fórmula "+1 marcado / −1 sofrido" está isolada atrás da `ScoringPolicyInterface`, cuja assinatura é `pointsFor(int $goalsScored, int $goalsConceded, bool $playingAway): int`. O parâmetro `playingAway` já está lá justamente por isso: uma `AwayGoalsScoringPolicy`, em que gol fora de casa vale mais, é uma classe nova de três linhas e um binding no container. Nem o `StandingsCalculator`, nem o agregado, nem os controllers mudam. A tabela `championships` inclusive já carrega a coluna `scoring_mode` para tornar a escolha por campeonato, como acontece hoje com `tiebreaker_mode`.

**Outros critérios de desempate.** Mesma mecânica: uma classe implementando `TiebreakerStrategyInterface` (confronto direto, gols marcados no torneio, prorrogação) entra na `TiebreakerChainFactory` na posição desejada.

**Outros formatos de torneio.** Hoje o agregado assume 8 times e chaveamento simples. Grupos ou pontos corridos exigiriam generalizar `Championship::start()` e o avanço de fases — a cadeia de desempate, a política de pontuação e as portas continuariam intactas.

**Simulação assíncrona.** `POST /simulate` executa até 8 jogos, cada um chamando um processo Python. Se o script ficasse lento, a chamada viraria um job em fila devolvendo `202 Accepted` com um recurso de status; o `UpdateTeamStatistics`, que hoje é síncrono de propósito, passaria a implementar `ShouldQueue`.

---

## Stack

Laravel 13 · PHP 8.4 (container) · MySQL 8.4 · Pest 5 · `php-open-source-saver/jwt-auth` 2 · Laravel Pint · Larastan nível 6 · GitHub Actions (pint + phpstan + testes contra MySQL a cada push).
