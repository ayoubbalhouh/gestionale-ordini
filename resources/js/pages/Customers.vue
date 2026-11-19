<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import api, { errorMessage } from '../api';
import { toast } from '../store';
import { euro } from '../format';
import Modal from '../components/Modal.vue';
import Pager from '../components/Pager.vue';

const rows = ref([]);
const meta = ref(null);
const q = ref('');
const editing = ref(null);
const form = reactive({});
const errors = ref({});

async function load(page = 1) {
    const res = await api.get('/customers', { params: { page, q: q.value || undefined } });
    rows.value = res.data.data;
    meta.value = res.data.meta;
}
let t;
watch(q, () => { clearTimeout(t); t = setTimeout(load, 300); });
onMounted(load);

function open(c) {
    errors.value = {};
    editing.value = c || {};
    Object.assign(form, { ragione_sociale: '', piva: '', email: '', telefono: '', indirizzo: '', citta: '', ...(c || {}) });
}

async function save() {
    errors.value = {};
    try {
        if (editing.value.id) await api.put('/customers/' + editing.value.id, form);
        else await api.post('/customers', form);
        toast(editing.value.id ? 'Cliente aggiornato' : 'Cliente aggiunto');
        editing.value = null;
        load(meta.value?.current_page);
    } catch (e) {
        errors.value = e.response?.data?.errors || {};
        toast(errorMessage(e), 'error');
    }
}

async function remove(c) {
    if (!confirm(`Eliminare ${c.ragione_sociale}?`)) return;
    try {
        await api.delete('/customers/' + c.id);
        toast('Cliente eliminato');
        load();
    } catch (e) {
        toast(errorMessage(e), 'error');
    }
}
const err = (k) => errors.value[k]?.[0];
</script>

<template>
  <div>
    <header class="page-head">
      <div><p class="eyebrow">Anagrafica</p><h1>Clienti</h1></div>
      <button class="btn" @click="open()">+ Nuovo cliente</button>
    </header>
    <div class="toolbar"><span></span><input v-model="q" type="search" placeholder="Cerca nome, città o P.IVA…" aria-label="Cerca clienti"></div>

    <div class="card table-card">
      <table class="table">
        <thead><tr><th>Cliente</th><th class="hide-sm">P.IVA</th><th class="num">Ordini</th><th class="num">Fatturato</th><th></th></tr></thead>
        <tbody>
          <tr v-for="c in rows" :key="c.id">
            <td><strong>{{ c.ragione_sociale }}</strong><span class="sub">{{ c.citta }}{{ c.email ? ' · ' + c.email : '' }}</span></td>
            <td class="hide-sm mono">{{ c.piva || '—' }}</td>
            <td class="num"><router-link :to="{ path: '/ordini', query: { q: c.ragione_sociale } }" class="link">{{ c.ordini_count }}</router-link></td>
            <td class="num"><strong>{{ euro(c.fatturato_cents) }}</strong></td>
            <td class="row-actions">
              <button class="btn btn-ghost btn-sm" @click="open(c)">Modifica</button>
              <button v-if="!c.ordini_count" class="icon-btn danger" title="Elimina" @click="remove(c)">×</button>
            </td>
          </tr>
          <tr v-if="!rows.length"><td colspan="5" class="empty">Nessun cliente.</td></tr>
        </tbody>
      </table>
      <Pager :meta="meta" @go="load" />
    </div>

    <Modal v-if="editing" :title="editing.id ? 'Modifica cliente' : 'Nuovo cliente'" @close="editing = null">
      <form class="form" @submit.prevent="save">
        <label>Ragione sociale<input v-model="form.ragione_sociale" required><span v-if="err('ragione_sociale')" class="error">{{ err('ragione_sociale') }}</span></label>
        <div class="grid-2">
          <label>Partita IVA<input v-model="form.piva" maxlength="11" inputmode="numeric" placeholder="11 cifre"><span v-if="err('piva')" class="error">{{ err('piva') }}</span></label>
          <label>Telefono<input v-model="form.telefono" type="tel"></label>
        </div>
        <label>Email<input v-model="form.email" type="email"><span v-if="err('email')" class="error">{{ err('email') }}</span></label>
        <div class="grid-2">
          <label>Indirizzo<input v-model="form.indirizzo"></label>
          <label>Città<input v-model="form.citta"></label>
        </div>
        <div class="form-actions"><button type="button" class="btn btn-ghost" @click="editing = null">Annulla</button><button class="btn">Salva</button></div>
      </form>
    </Modal>
  </div>
</template>
