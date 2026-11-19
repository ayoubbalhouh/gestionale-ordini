const eur = new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' });

export const euro = (cents) => eur.format((cents || 0) / 100);
export const date = (iso) => (iso ? new Date(iso).toLocaleDateString('it-IT', { day: '2-digit', month: 'short', year: 'numeric' }) : '—');
export const dateTime = (iso) => (iso ? new Date(iso).toLocaleString('it-IT', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) : '—');

export const STATI = {
    bozza: { label: 'Bozza', action: 'Torna in bozza' },
    confermato: { label: 'Confermato', action: 'Conferma ordine' },
    spedito: { label: 'Spedito', action: 'Segna come spedito' },
    consegnato: { label: 'Consegnato', action: 'Segna come consegnato' },
    annullato: { label: 'Annullato', action: 'Annulla ordine' },
};
