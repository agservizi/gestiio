# Guida utente — Modulo SEND

## Admin

1. Attiva `servizio_send` sul profilo agente (solo admin).
2. Verifica Impostazioni SEND (assegnazione, upload, privacy version, prezzi cliente/agente).
3. Usa Dashboard / Report / elenco per monitoraggio (filtri data e export CSV).

## Agente / operatore sportello

1. Dashboard → CTA **SEND — Nuova**.
2. Seleziona tipologia richiedente e compila avviso + soggetti.
3. Spunta la checklist documenti obbligatori e il **consenso privacy**.
4. Salva bozza, carica allegati dal dettaglio (o Dropzone in creazione).
5. Invia al supervisore (manuale o automatico). Con metodo “solo manuale” la pratica resta in attesa di assegnazione.
6. Se arriva “integrazione richiesta”, integra e reinvia.
7. A pratica **Completata**, registra consegna al cittadino (senza inviare documenti sensibili via email).
8. Dopo consegna puoi scaricare la **ricevuta PDF**.
9. Le bozze si possono **eliminare** (con rimborso plafond se addebitato).

## Supervisore SEND

1. Dashboard supervisore → badge coda SEND, oppure menu **SEND → Coda**.
2. Apri pratica → Prendi in carico → Avvia lavorazione.
3. Lavora con gli strumenti ufficiali SEND per cui sei autorizzato (fuori da Gestiio).
4. Carica l’**allegato SEND** (risultato/ricevuta) nella sezione dedicata: obbligatorio prima di Completa.
5. Completa oppure richiedi integrazione / rifiuta con motivazione (il rifiuto rimborsa il plafond).
6. Admin/supervisore con permesso può **riassegnare** o **riaprire** pratiche rifiutate/annullate/scadute.
7. Note interne restano visibili solo a chi ha `send.notes.view-internal`.

## Cosa non fa Gestiio

- Non accede automaticamente a portali SEND / SPID / CIE (salvo eventuale provider futuro con flag integrazione).
- Non allega scansioni alle email di notifica.
- Non dichiara valore legale alle ricevute interne di consegna.
