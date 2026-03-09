# Cadastro de Empreendimento

## Overview

Implementar o cadastro de **Empreendimento** (loteamento/projeto imobiliário) como entidade pai dos lotes. Um empreendimento agrupa vários lotes e serve como ponto de entrada para a gestão do mapa de lotes.

## Domain Model

### Empreendimento
| Campo         | Tipo           | Notas                              |
|---------------|----------------|------------------------------------|
| id            | bigint PK      |                                    |
| name          | string         | obrigatório, único                 |
| description   | text nullable  |                                    |
| address       | string nullable|                                    |
| city          | string nullable|                                    |
| state         | string(2) nullable | sigla UF                      |
| total_area    | decimal(12,2) nullable | área total em m²          |
| status        | string         | active / inactive (default active) |
| timestamps    |                |                                    |

### EmpreendimentoStatus Enum
- `Active` = 'active' — cor: green
- `Inactive` = 'inactive' — cor: zinc

### Alteração em Lot
- Adicionar coluna `empreendimento_id` (FK → empreendimentos, nullable com set null on delete)

## Relacionamentos
- `Empreendimento` hasMany `Lot`
- `Lot` belongsTo `Empreendimento` (nullable por enquanto para não quebrar os lotes existentes)

## Pages / Routes

| Route                              | Name                        | Descrição                       |
|------------------------------------|-----------------------------|---------------------------------|
| GET /empreendimentos               | empreendimentos.index       | Listar empreendimentos          |
| GET /empreendimentos/{emp}         | empreendimentos.show        | Ver/editar empreendimento       |

## Livewire Pages

### `pages::empreendimentos.index`
- Listagem em tabela com busca por nome/cidade
- Filtro por status
- Modal inline para criar novo empreendimento
- Campos: nome, descrição, endereço, cidade, estado, área total, status
- Botão delete com confirmação
- Link para a página show

### `pages::empreendimentos.show`
- Exibir e editar o empreendimento
- Seção "Lotes" listando os lotes vinculados (com contagem por status)
- Filtro/busca nos lotes
- Possibilidade de vincular lotes existentes ao empreendimento

## Alterações nos Lotes
- `lots.index`: Adicionar coluna "Empreendimento" na tabela; filtro por empreendimento
- `lots.show`: Adicionar campo de seleção de empreendimento no formulário de edição

## Sidebar
- Adicionar item "Empreendimentos" (ícone: `building-office`) entre Dashboard e Clients

## Implementation Todos
1. Criar migration `empreendimentos` table
2. Criar migration `add_empreendimento_id_to_lots_table`
3. Criar Enum `EmpreendimentoStatus`
4. Criar Model `Empreendimento` com relacionamentos e casts
5. Atualizar Model `Lot` (belongsTo, fillable)
6. Criar Factory `EmpreendimentoFactory`
7. Adicionar rotas no `web.php`
8. Criar Livewire page: `empreendimentos.index`
9. Criar Livewire page: `empreendimentos.show`
10. Atualizar `lots.index` — coluna empreendimento + filtro
11. Atualizar `lots.show` — campo empreendimento no form
12. Atualizar sidebar
13. Escrever testes Pest (feature tests: CRUD empreendimento, vínculo com lote)
14. Rodar Pint formatter
