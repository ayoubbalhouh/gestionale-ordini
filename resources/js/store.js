import { reactive } from 'vue';

// Stato condiviso: utente collegato e notifiche
export const session = reactive({ user: null, ready: false });

export const toasts = reactive([]);
let id = 0;
export function toast(text, type = 'ok') {
    const t = { id: ++id, text, type };
    toasts.push(t);
    setTimeout(() => toasts.splice(toasts.indexOf(t), 1), 3200);
}
