# Database Schema Documentation

Guida ai 111 modelli e loro relazioni nel database Gestiio.

## Core Tables (Top 20 Critical Models)

### Users & Authentication
- **users**: User accounts (customers)
  - id, email, password, name, phone, created_at
  - Relations: hasMany(Contratto), hasMany(Ticket), hasMany(VisuraCamerale)

- **agenti**: Staff agents
  - user_id (FK), nome, cognome, email, telefono, stato
  - Relations: belongsTo(User), hasMany(Contratto)

- **role_has_permissions**: Spatie Permission junction
  - Caches user roles and permissions

### Contracts & Services
- **contratti**: Main contract model
  - user_id, numero_contratto, data_inizio, data_fine, importo, stato
  - Relations: belongsTo(User), hasMany(AllegatoContratto)

- **contratto_energia**: Energy contracts (extends contratti)
  - contratto_id, prodotto_id, kWh, fascia_oraria
  - Relations: belongsTo(Contratto), belongsTo(ProdottoEnergiaEgea)

- **attivazione_sim**: SIM activation contracts
  - user_id, operatore, numero_sim, data_attivazione
  - Relations: hasMany(EsitoAttivazioneSimm)

- **servizi**: Service types
  - nome, descrizione, tipo (energia, sim, visura, caf), attivo
  - Relations: hasMany(Contratto)

### Compliance & Visuras
- **visura_camerale**: Chamber of Commerce records
  - user_id, partita_iva, ragione_sociale, numero_visura, stato
  - Relations: belongsTo(User), hasMany(EsitoVisura)

- **caf_patronato**: CAF/Patronato services
  - user_id, tipo_pratica, data_richiesta, stato
  - Relations: belongsTo(User), hasMany(EsitoCafPatronato)

- **comparasemplice**: Comparison services
  - user_id, nome, cognome, email, priorita
  - Relations: belongsTo(User)

### Messaging & Notifications
- **chat_threads**: Internal chat conversations
  - titolo, creato_da_user_id
  - Relations: hasMany(ChatMessage), hasMany(ChatParticipant)

- **chat_messages**: Individual messages
  - chat_thread_id, user_id, contenuto, created_at
  - Relations: belongsTo(ChatThread), belongsTo(User), hasMany(ChatMessageAttachment)

- **tickets**: Support tickets (customer)
  - user_id, numero_ticket, titolo, descrizione, priorita, stato
  - Relations: belongsTo(User), hasMany(ChatMessage)

- **causale_ticket**: Ticket categories
  - nome, descrizione, ordine
  - Relations: hasMany(Ticket)

### Products & Services
- **prodotto_windtre**: Windtre SIM products
  - nome, descrizione, prezzo, minuti, giga, costo_mensile

- **prodotto_energia_egea**: Energy products
  - nome, fascia_consumo, prezzo_kWh, tasse

- **licenza**: Software licenses
  - utente_id, nome_licenza, data_inizio, data_fine, attiva

### File Management
- **cartella_files**: File folders/directories
  - user_id, nome, descrizione
  - Relations: hasMany(Allegato)

- **file_audit_log**: Audit trail for files
  - file_id, user_id, azione (created, modified, deleted), created_at

### Esiti (Outcomes)
- **esito_segnalazione**: Complaint outcomes
  - segnalazione_id, descrizione, data_elaborazione, risolto

- **esito_visura**: Visura outcomes
  - visura_id, numero_visura, data_ricezione, contenuto_pdf

- **esito_caf_patronato**: CAF outcomes
  - caf_id, numero_pratica, data_ricezione, importo

## Index Strategy

| Table | Index | Why |
|-------|-------|-----|
| users | email (UNIQUE) | Login queries |
| contratti | user_id, stato | Filter by user, status |
| ticket | user_id, stato | Filter by user, status |
| chat_messages | chat_thread_id, created_at | Timeline queries |
| visura_camerale | partita_iva (UNIQUE) | Duplicate prevention |
| allegati | parent_id, tipo | File lookups |

## Performance Considerations

### N+1 Query Prevention
Always eager-load relationships:

```php
// ❌ Bad - N+1 queries
foreach (User::all() as $user) {
    echo $user->contratti->count();  // Query per user
}

// ✅ Good - Single query with count
$users = User::withCount('contratti')->get();
```

### Caching Strategy
- User permissions: 1 hour TTL
- Contract list: 15 minutes TTL
- Ticket list: 5 minutes TTL
- Static products: 1 week TTL

### Query Optimization
- Use `select()` to limit columns
- Paginate large result sets
- Use `chunk()` for bulk operations

## Data Types

| Type | Usage | Example |
|------|-------|---------|
| VARCHAR(255) | Emails, names, short text | email, nome |
| TEXT | Long descriptions | descrizione |
| DECIMAL(10,2) | Money | importo, prezzo |
| DATE | Dates | data_inizio, data_fine |
| DATETIME | Timestamps | created_at, updated_at |
| ENUM | Fixed options | stato, tipo |
| BOOLEAN | Flags | attivo, risolto |

## Backup Strategy

- **Daily**: Database snapshot (`php artisan db-snapshots:create`)
- **Weekly**: Full backup to S3 (`php artisan backup:run`)
- **Retention**: 30 days
- **Restore**: `php artisan db-snapshots:load {name}`

## Constraints

- ALL foreign keys have `onDelete('cascade')`
- User deletion removes all associated data
- Contract deletion removes all attachments/outcomes
- Unique constraints on: email, numero_visura, numero_contratto

