# Mapa de Lotes com Leaflet

## Overview

Implementar visualização e edição de lotes em mapa interativo usando **Leaflet + Leaflet.draw** na página do Empreendimento. O usuário pode desenhar polígonos para definir os limites de cada lote, vincular polígonos a lotes existentes ou criar novos lotes diretamente pelo mapa.

## Arquitetura Técnica

### Leaflet via CDN + `@assets` (Livewire 4)
- Leaflet 1.9.4 + Leaflet.draw 1.0.4 carregados via `@assets` no componente Livewire
- JavaScript do mapa via `@script` no mesmo componente (sem bundle Vite)
- OpenStreetMap como tile provider (gratuito, sem API key)
- Comunicação mapa ↔ Livewire via `$wire.call()` em eventos do Leaflet

### Armazenamento
- Geometria de cada lote: coluna `geometry` (JSON nullable) na tabela `lots`, armazenando GeoJSON Polygon
  ```json
  { "type": "Polygon", "coordinates": [[[lng, lat], ...]] }
  ```
- Centro padrão do mapa por empreendimento: colunas `map_lat`, `map_lng`, `map_zoom` na tabela `empreendimentos`

## Mudanças no Banco de Dados

### Migration 1: `add_geometry_to_lots_table`
| Coluna     | Tipo          | Default   |
|------------|---------------|-----------|
| geometry   | JSON nullable | null      |

### Migration 2: `add_map_fields_to_empreendimentos_table`
| Coluna    | Tipo             | Default |
|-----------|------------------|---------|
| map_lat   | decimal(10,7) nullable | null |
| map_lng   | decimal(10,7) nullable | null |
| map_zoom  | tinyint nullable       | null |

## Fluxo de Uso do Mapa

1. Usuário acessa `empreendimentos.show` → vê nova aba/seção **"Mapa"**
2. Mapa abre centralizado nas coordenadas do empreendimento (ou padrão: centro do Brasil -15, -47, zoom 4 se não configurado)
3. Todos os lotes **com geometria** são renderizados como polígonos coloridos por status (verde = disponível, amarelo = reservado, vermelho = vendido)
4. Popup ao clicar no polígono: código, quadra, status, preço + botão "Editar Geometria"
5. **Toolbar de desenho**: ferramenta de polígono + edição
6. Ao finalizar o desenho de um novo polígono → **modal de atribuição**:
   - Opção A: Vincular a lote existente (sem geometria) → dropdown
   - Opção B: Criar novo lote → campos: código, quadra, preço, status
7. Ao editar polígono existente → salva automaticamente ao confirmar edição
8. Botão "Salvar Centro do Mapa" → persiste posição/zoom atual como padrão

## Componentes Livewire Novos/Alterados

### `empreendimentos.show` — novas propriedades e métodos
**Propriedades:**
- `string $mapLat`, `string $mapLng`, `string $mapZoom` — preenchidas no mount
- `bool $showMapAssignModal` — controla modal de atribuição pós-desenho
- `string $pendingGeometry` — GeoJSON do polígono recém-desenhado (JSON string)
- `string $assignMode` — `'link'` ou `'create'`
- `int|null $assignLotId`, campos para criar lote: `$newCode`, `$newBlock`, `$newPrice`, `$newStatus`

**Métodos:**
- `saveGeometry(int $lotId, string $geometry)` — persiste GeoJSON no lote
- `clearGeometry(int $lotId)` — remove geometria do lote
- `assignDrawnPolygon()` — processa o modal: vincula ou cria lote com o `pendingGeometry`
- `saveMapCenter(float $lat, float $lng, int $zoom)` — persiste centro do mapa no empreendimento
- `getLotsGeoJsonProperty()` — computed: retorna GeoJSON FeatureCollection de todos os lotes com geometria

**Dados passados ao JS:** JSON com todos os lotes (código, status, cor, GeoJSON geometry)

## Estrutura do JavaScript (`@script`)

```
map.js (inline @script no componente)
├── initMap(center, zoom)           — inicializa Leaflet map
├── loadLotLayers(lotsData)         — renderiza polígonos com cores
├── onDrawCreated(layer)            — abre modal de atribuição
├── onEditSaved(layers)             — chama $wire.saveGeometry
└── setupDrawControls()             — configura Leaflet.draw toolbar
```

## Implementation Todos
1. Criar migration `add_geometry_to_lots_table`
2. Criar migration `add_map_fields_to_empreendimentos_table`
3. Atualizar Model `Lot` — `geometry` em fillable e cast (array)
4. Atualizar Model `Empreendimento` — campos de mapa em fillable
5. Atualizar `EmpreendimentoFactory` — incluir campos de mapa
6. Atualizar Livewire `empreendimentos.show` — novas propriedades + métodos do mapa
7. Adicionar seção do mapa no template `empreendimentos/⚡show.blade.php` (tabs com `<flux:tabs>`)
8. Adicionar JavaScript do mapa via `@assets` + `@script`
9. Escrever testes Pest (saveGeometry, clearGeometry, assignDrawnPolygon create, assignDrawnPolygon link, saveMapCenter)
10. Rodar Pint formatter
