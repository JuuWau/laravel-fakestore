# Laravel FakeStore Integration

API construída com Laravel 12 + Nginx + PostgreSQL (via Docker Compose), integrando com a Fake Store API.
Oferece endpoints para sincronização, catálogo, estatísticas e logs estruturados com rastreamento por request ID.

## Funcionalidades principais

* **Sync**: `POST /api/integracoes/fakestore/sync` — importa categorias e produtos da Fake Store API, atualiza/evita duplicatas, trata falhas individualmente e utiliza cache com invalidação.
* **Catálogo**: `GET /api/products` e `GET /api/products/{id}` — filtros por categoria, preço, busca, ordenação, paginação e busca por ID interno.
* **Estatísticas**: `GET /api/statistics` — total de produtos, total por categoria, preço médio, top 5 mais caros (usando SQL puro).
* **Middleware de integração**: `X-Client-Id` obrigatório, logs de entrada/saída com request ID, medição de tempo de execução e rate-limit por cliente.
* **Logs estruturados**: JSON com request ID para correlação entre middleware e controller.

---

## Como rodar (Docker)

**Requisitos:** Docker e Docker Compose.

```bash
git clone git@github.com:JuuWau/laravel-fakestore.git
cd laravel-fakestore
cp .env.example .env
docker compose up -d --build
docker compose exec app php artisan migrate
```

A aplicação estará disponível em [http://localhost:8000](http://localhost:8000).

Exemplo de endpoint: [http://localhost:8000/api/integracoes/fakestore/sync](http://localhost:8000/api/integracoes/fakestore/sync)

O banco PostgreSQL está configurado automaticamente via Docker.

---

## Estrutura do Banco de Dados

O projeto utiliza PostgreSQL com as seguintes tabelas principais:

### categories

| Coluna       | Tipo      | Descrição                         |
| ------------ | --------- | --------------------------------- |
| id           | BIGINT    | PK, auto increment                |
| external_id  | VARCHAR   | ID da categoria na Fake Store API |
| name         | VARCHAR   | Nome da categoria                 |
| created_at   | TIMESTAMP | Timestamp de criação              |
| updated_at   | TIMESTAMP | Timestamp de atualização          |

### products

| Coluna       | Tipo      | Descrição                       |
| ------------ | --------- | ------------------------------- |
| id           | BIGINT    | PK, auto increment              |
| external_id  | BIGINT    | ID do produto na Fake Store API |
| title        | VARCHAR   | Nome do produto                 |
| description  | TEXT      | Descrição do produto            |
| price        | DECIMAL   | Preço do produto                |
| image        | VARCHAR   | URL da imagem                   |
| category_id  | BIGINT    | FK -> categories.id             |
| created_at   | TIMESTAMP | Timestamp de criação            |
| updated_at   | TIMESTAMP | Timestamp de atualização        |

**Relacionamentos:**

* **Do ponto de vista do produto:** `products.category_id` → `categories.id` (um-para-um, 1:1)
* **Do ponto de vista da categoria:** `categories.id` → `products.category_id` (um-para-muitos, 1:N)

Cada produto pertence a uma categoria; cada categoria pode ter vários produtos.

---

## Endpoints da API

| Método | Rota                            | Descrição                                                     |
| ------ | ------------------------------- | ------------------------------------------------------------- |
| POST   | /api/integracoes/fakestore/sync | Sincroniza produtos e categorias da Fake Store API            |
| GET    | /api/products                   | Lista produtos — permite filtros, paginação e ordenação       |
| GET    | /api/products/{id}              | Detalhes de produto por ID interno                            |
| GET    | /api/statistics                 | Estatísticas (total, média, total por categoria, top 5 caros) |

**Headers obrigatórios:**

* `X-Client-Id`: qualquer identificador

---

## Logs estruturados & Request ID

Os logs são salvos em formato JSON no arquivo `storage/logs/laravel.log`, seguindo esse padrão:

```json
{
  "message": "Request started",
  "context": {
    "request_id": "UUID",
    "method": "POST",
    "url": "...",
    "client_id": "..."
  },
  "level_name": "INFO",
  "datetime": "..."
}
```

O middleware gera um `request_id` único por requisição e inclui nos logs.
Erros durante sincronização (produtos, categorias) também são logados com o mesmo `request_id`, facilitando o trace.
Ideal para integração com ferramentas de observabilidade ou logs centralizados.

---

## Testes Unitários

Execute o seguinte comando:

```bash
docker compose exec app php artisan test
```

* **IntegrationControllerTest** — verifica sucesso e falhas da sincronização.
* **StatisticsControllerTest** — valida estrutura e valores corretos das estatísticas.
* **ProductApiTest** — garante que a API de produtos só funciona para clientes autenticados via header, responde corretamente e aplica limite de requisições para evitar abuso.

Testes IntegrationControllerTest e StatisticsControllerTest utilizam factories e `RefreshDatabase`.



