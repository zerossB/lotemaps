# Proposal System

## Overview
A proposal management system for the lotemaps land-lot application. Allows users to create proposals linking clients to land lots, track status, and manage the sales pipeline.

## Domain Models

- **Client** – prospect/buyer (name, email, phone, document, notes)
- **Lot** – land parcel (code, block, description, area_sqm, price, status)
- **Proposal** – links a client to one or more lots (status, total_price, notes, expires_at)
- **LotStatus** enum: Available, Reserved, Sold
- **ProposalStatus** enum: Draft, Sent, Accepted, Rejected, Expired

## Pages

| Route | Name | Description |
|---|---|---|
| GET /clients | clients.index | List clients |
| GET /clients/create | clients.create | Create client form |
| GET /clients/{client} | clients.show | View/edit client |
| GET /lots | lots.index | List lots |
| GET /lots/create | lots.create | Create lot form |
| GET /lots/{lot} | lots.show | View/edit lot |
| GET /proposals | proposals.index | List all proposals |
| GET /proposals/create | proposals.create | Create new proposal |
| GET /proposals/{proposal} | proposals.show | View/update proposal |

## Implementation Todos
1. Create Client model, migration, factory, seeder
2. Create Lot model, migration, factory, seeder
3. Create Proposal model, migration, factory with pivot table
4. Create LotStatus and ProposalStatus enums
5. Add routes to web.php
6. Build Livewire page: clients index
7. Build Livewire page: client create/show
8. Build Livewire page: lots index
9. Build Livewire page: lot create/show
10. Build Livewire page: proposals index
11. Build Livewire page: proposal create
12. Build Livewire page: proposal show
13. Update sidebar navigation
14. Write Pest feature tests
15. Run Pint formatter
