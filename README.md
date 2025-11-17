# Teste Prático Back-end BeTalent



### Framework escolhido
- [Laravel](https://laravel.com/) 12 PHP 8.2 ✅

## 🧰 Requisitos locais

Certifique-se de ter as ferramentas abaixo antes de iniciar:

- [Git](https://git-scm.com/) para clonar o repositório
- [Docker](https://docs.docker.com/get-docker/) e [Docker Compose](https://docs.docker.com/compose/) (Laravel Sail usa os dois)

- Requisitos que já estão no compose.yml (não precisa instalar)
  - PHP 8.2+ e Composer instalados **ou** apenas o Sail (você pode chamar `composer`, `npm` e Artisan via `./vendor/bin/sail ...`)
  - Node.js 18+ / npm caso queira rodar o Vite no host (opcional para o teste)

## 🚀 Como rodar o projeto

1. **Clonar o repositório**
   ```bash
   git clone https://github.com/harissonmatos/betalent-multigateway.git
   cd betalent-multigateway
   ```
2. **Configurar variáveis**
   ```bash
   cp .env.example .env
   ```
   Ajuste as variáveis de gateway e banco, se necessário.
3. **Instalar dependências PHP/NPM (usando container)**
   ```bash
   ./vendor/bin/sail composer install
   ./vendor/bin/sail npm install
   ```
4. **Subir os containers do Sail (app, MySQL, Redis, mocks)**
   ```bash
   ./vendor/bin/sail up -d
   ```
5. **Executar migrações e seeders**
   ```bash
   ./vendor/bin/sail artisan migrate
   ./vendor/bin/sail artisan db:seed
   ```
6. **Rodar a suíte de testes (TDD)**
   ```bash
   ./vendor/bin/sail test
   ```
7. **Consumir a API**  
   Com os serviços rodando, as rotas estarão disponíveis em `http://localhost/api`. Use a collection `API.postman_collection.json` para facilitar.

> Para derrubar os containers: `./vendor/bin/sail down`.

## 📊 Níveis de implementação

### Nível 3 - Nível Escolhido ✅
Escolha esse nível se você é pleno ou sênior, por exemplo:
- Valor da compra vem de múltiplos produtos e suas quantidades selecionadas e calculada via back ✅
- Gateways com autenticação ✅
- Usuários tem roles:
    - ADMIN - faz tudo ✅
    - MANAGER - pode gerenciar produtos e usuários ✅
    - FINANCE - pode gerenciar produtos e realizar reembolso ✅
    - USER - pode o resto que não foi citado ✅
- Uso de TDD ✅
- Docker compose com MySQL, aplicação e mock dos gateways ✅
  - Usei o laravel sail (por agilidade) e adicionei os gateways no compose.yml mas poderia fazer do zero também ✅

## 🗄 Estrutura do Banco de Dados

O banco de dados deve ser estruturado à sua escolha, mas minimamente deve conter:

- **users** ✅
    - email
    - password
    - role
- **gateways** ✅
    - name
    - is_active
    - priority
- **clients** ✅
    - name
    - email
- **products** ✅
    - name
    - amount
- **transaction_products** ✅
    - transaction_id
    - product_id
    - quantity
- **transactions** ✅
    - client
    - gateway
    - external_id
    - status
    - amount
    - card_last_numbers

## 🛣 Rotas do Sistema

### Rotas Públicas
- Realizar o login ✅
- Realizar uma compra informando o produto ✅
- Adicionei a lista de produtos e detalhes de um produto por entender que seria necessário para a aplicação fazer a compra ✅  

### Rotas Privadas
- Ativar/desativar um gateway ✅
- Alterar a prioridade de um gateway ✅
- CRUD de usuários com validação por roles ✅
- CRUD de produtos com validação por roles ✅
- Listar todos os clientes ✅
- Detalhe do cliente e todas suas compras ✅
- Listar todas as compras ✅
- Detalhes de uma compra ✅
- Realizar reembolso de uma compra junto ao gateway com validação por roles ✅

## 📑 Documentação Detalhada da API

A coleção `API.postman_collection.json`, distribuída neste repositório, pode ser importada no Postman/Insomnia para testar cada rota. Todas as respostas são JSON e, salvo menção em contrário, usam `Content-Type: application/json`. O backend expõe as rotas em `http://localhost/api`.

### Convenções Gerais
- **Autenticação:** o login gera um token Sanctum. Para chamadas autenticadas informe `Authorization: Bearer {{api_token}}`. A collection já usa a variável `api_token` preenchida automaticamente pelo teste da requisição de login.
- **Paginação:** endpoints de listagem (`users`, `products`, `clients`, `transactions`) seguem o formato padrão Laravel (`data`, `links`).
- **Validação:** erros de validação retornam HTTP 422 com `{ "message": "...", "errors": { "campo": ["motivo"] } }`.

### Rotas Públicas

| Método | Caminho | Descrição | Corpo da requisição | Principais respostas |
| --- | --- | --- | --- | --- |
| `POST` | `/login` | Realiza autenticação e cria um token pessoal | `{ "email": "test@example.com", "password": "senha123" }` | `200` com `{ "token": "...", "user": { "id": 1, "email": "...", "role": "ADMIN" } }`; `401` para credenciais inválidas; `422` campos obrigatórios |
| `GET` | `/products` | Lista produtos disponíveis para checkout (pública) | — | `200` com array paginado de produtos (`id`, `name`, `amount`) |
| `GET` | `/products/{product}` | Detalha um produto específico | — | `200` com o registro; `404` inexistente |
| `POST` | `/checkout` | Processa uma compra usando múltiplos gateways com fallback | ```json\n{\n  \"client\": {\"name\": \"Maria\", \"email\": \"maria@example.com\"},\n  \"payment\": {\"cardNumber\": \"4111111111111111\", \"cvv\": \"123\", \"expiry\": \"12/30\"},\n  \"products\": [{\"id\": 1, \"quantity\": 1}, {\"id\": 4, \"quantity\": 3}]\n}\n``` | `200` com `{ \"success\": true, \"transaction\": {...} }` quando algum gateway aprova; `422` para validações (ex.: produto inexistente) |

### Rotas Autenticadas de Sessão

Requerem token do login.

| Método | Caminho | Papel mínimo | Descrição |
| --- | --- | --- | --- |
| `GET` | `/me` | qualquer usuário autenticado | Retorna os dados do usuário autenticado (ex.: `{ "id": 1, "email": "test@example.com", "role": "ADMIN" }`). |
| `POST` | `/logout` | qualquer usuário autenticado | Revoga o token atual e retorna `{ "message": "Deslogado" }`. |

### Gestão de Usuários (roles: `ADMIN` e `MANAGER`)

| Método | Caminho | Corpo (quando aplicável) | Respostas |
| --- | --- | --- | --- |
| `GET` | `/users` | — | `200` paginado com lista de usuários. |
| `GET` | `/users/{user}` | — | `200` com o usuário; `403` se um MANAGER tentar acessar um ADMIN; `404` inexistente. |
| `POST` | `/users` | `{ "name": "João", "email": "novo@teste.com", "password": "123456", "role": "USER" }` | `201` com o registro criado; validações 422 (campos obrigatórios, email único, roles permitidas). |
| `PUT` | `/users/{user}` | Pode atualizar parcialmente `name`, `email`, `password`, `role`. | `200` com dados atualizados; `403` quando um MANAGER tenta alterar ADMIN. |
| `DELETE` | `/users/{user}` | — | `200` e mensagem de sucesso; `403` para restrições de role. |

### Gestão de Produtos

- **Roles:** `ADMIN`, `MANAGER` e `FINANCE` podem criar/atualizar/deletar; listagem e show são públicas (vide tabela de rotas públicas).

| Método | Caminho | Corpo | Respostas |
| --- | --- | --- | --- |
| `POST` | `/products` | `{ "name": "Produto X", "amount": 99.90 }` | `201` com o produto; `422` validação (`name` obrigatório, `amount` numérico ≥ 0). |
| `PUT` | `/products/{product}` | Campos opcionais `name` e/ou `amount`. | `200` com produto atualizado; `404` inexistente. |
| `DELETE` | `/products/{product}` | — | `200` e mensagem de remoção; `404` inexistente. |

### Clientes (roles: `ADMIN`, `MANAGER`, `FINANCE`, `USER`)

| Método | Caminho | Descrição |
| --- | --- | --- |
| `GET` | `/clients` | Lista clientes cadastrados (id, name, email) de forma paginada. |
| `GET` | `/clients/{client}` | Retorna cliente + array `transactions`, cada uma com `id`, `status`, `amount`, gateway utilizado e itens (`products`). |

### Transações e Reembolso

| Método | Caminho | Papel mínimo                          | Descrição |
| --- | --- |---------------------------------------| --- |
| `GET` | `/transactions` | `ADMIN`, `MANAGER`, `FINANCE`, `USER` | Lista transações paginadas com cliente e gateway. |
| `GET` | `/transactions/{transaction}` | `ADMIN`, `MANAGER`, `FINANCE`, `USER`                  | Detalha a transação (gateway + itens). |
| `PUT` | `/transactions/{transaction}/refund` | `ADMIN` ou `FINANCE`                  | Solicita chargeback no gateway original. Respostas: `200` (`status: refunded`), `403` para roles inválidas, `422` se a transação não estiver `paid` ou gateway inativo, `500` em erro no gateway. |

> **Dica:** cada rota acima já está configurada na coleção Postman com exemplos de requisição e resposta (Ex.: “Checkout 201” e “Checkout 422”), facilitando a validação manual.

## 🔧 Requisitos Técnicos

### Obrigatórios
- MySQL como banco de dados ✅
- Respostas devem ser em JSON ✅
- ORM para gestão do banco (Eloquent, Lucid, Knex, Bookshelf etc.) ✅
  - Usado Eloquente do laravel
- Validação de dados (VineJS, etc.)  ✅
- README detalhado com:
    - Requisitos
    - Como instalar e rodar o projeto
    - Detalhamento de rotas ✅
    - Outras informações relevantes
- Implementar TDD ✅
- Docker compose com MySQL, aplicação e mock dos gateways ✅
