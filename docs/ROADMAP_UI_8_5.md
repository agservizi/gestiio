# Roadmap UI Backend Metronic verso 8.5/10

## Obiettivo
Portare la UI backend a uno standard **8.5/10** senza modificare logica business, controller, policy o query.

## Perimetro
- Solo Blade, CSS, JS di presentazione.
- Nessuna modifica a regole permessi o flussi applicativi.
- Compatibilita con ruolo admin/agente/operatore/supervisore invariata.

## Stato Attuale (stima)
- Coerenza visuale: 6.5/10
- Uniformita componenti Metronic: 6/10
- UX operativa (azioni rapide, feedback, tooltips): 7/10
- Manutenibilita layout frontend: 6/10

## KPI di Arrivo (target 8.5/10)
- 100% pulsanti azione standardizzati (icone, tooltip, stati hover/focus).
- 0 regressioni su apertura modali AJAX.
- 100% tooltip inizializzati anche su contenuti caricati dinamicamente.
- Layout condivisi riducendo duplicazione frontend principale.
- Pagina `/backend/ticket` con dashboard KPI + grafici coerenti Metronic.

## Piano per Sprint

## Sprint 1 - Stabilita UI e standard base
- [x] Normalizzazione trigger modal AJAX (`data-toggle` + fallback `data-toggleZ`).
- [x] Re-init automatico componenti Metronic dopo render AJAX.
- [x] Init tooltip Bootstrap su elementi dinamici (modali e contenuti caricati).
- [ ] Audit e conversione progressiva attributi legacy `data-toggleZ/data-targetZ`.
- [ ] Checklist visual regression su pagine chiave (`documenti`, `ticket`, `dashboard`).

### Criteri di accettazione Sprint 1
- Nessun bottone modal-ajax non funzionante.
- Tooltip visibili su icone azione anche dopo caricamenti AJAX.

## Sprint 2 - Consolidamento layout e design system interno
- [x] Estrarre shell layout comune tra `_main-Sidebar` e `_main-Header`.
- [x] Centralizzare include JS/CSS e init comuni.
- [x] Definire mini design-token CSS (spaziature, badge, pulsanti icona).
- [x] Uniformare toolbar/tabelle/cards con classi helper condivise.

### Criteri di accettazione Sprint 2
- Duplicazione layout ridotta sensibilmente.
- Stesso comportamento visuale in tutte le pagine backend principali.

## Sprint 3 - Upgrade UX operativo
- [x] Revisione completa UI `/backend/ticket` (solo aspetto, no logica).
- [x] KPI cards sopra tabella (aperti/chiusi/SLA/assegnati).
- [x] Grafici ApexCharts coerenti col tema Metronic.
- [x] Filtri e quick-actions con layout responsive e leggibile.
- [x] Stati vuoti, loading, error feedback standardizzati.

### Criteri di accettazione Sprint 3
- `/backend/ticket` percepita come pagina premium e coerente Metronic.
- KPI e grafici leggibili su desktop e mobile.

## Backlog opzionale (post 8.5)
- [ ] Dark mode rifinita su pagine custom.
- [ ] Motion/UI microinterazioni coerenti.
- [ ] Libreria interna componenti Blade riusabili per tabelle azioni.

## Note operative
- Ogni rilascio frontend con screenshot prima/dopo delle viste critiche.
- Verifica manuale su Chrome + Safari, desktop + mobile.
- Nessuna migration DB o modifica API prevista.
