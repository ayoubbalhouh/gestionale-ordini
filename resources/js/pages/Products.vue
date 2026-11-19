<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import api, { errorMessage } from '../api';
import { session, toast } from '../store';
import { euro } from '../format';
import Modal from '../components/Modal.vue';
import Pager from '../components/Pager.vue';

const route = useRoute();
const rows = ref([]);
const meta = ref(null);
const q = ref(route.query.q || '');
const sotto = ref(!!route.query.sotto_scorta);
const admin = session.user.role === 'admin';

const editing = ref(null);   // prodotto in modifica (o nuovo)
const loading = ref(null);   // prodotto per il carico
const form = reactive({});
const carico = reactive({ quantita: 10, nota: '' });
const errors = ref({});

async function load(page = 1) {
    const res = await api.get('/products', { params: { page, q: q.value || undefined, sotto_scorta: sotto.value ? 1 : undefined } });
    rows.value = res.data.data;
    meta.value = res.data.meta;
}
let t;
watch(q, () => { clearTimeout(t); t = setTimeout(load, 300); });
watch(sotto, () => load());
onMounted(load);

function openEdit(p) {
    errors.value = {};
    editing.value = p || {};
    Object.assign(form, p
        ? { sku: p.sku, nome: p.nome, descrizione: p.descrizione, prezzo: p.prezzo_cents / 100, iva: p.iva, scorta_minima: p.scorta_minima, attivo: p.attivo }
        : { sku: '', nome: '', descrizione: '', prezzo: '', iva: 22, scorta_minima: 5, giacenza: 0, attivo: true });
}

async function saveProduct() {
    errors.value = {};
    const payload = { ...form, prezzo_cents: Math.round(Number(form.prezzo) * 100) };
    delete payload.prezzo;
    try {
        if (editing.value.id) await api.put('/products/' + editing.value.id, payload);
        else await api.post('/products', payload);
        toast(editing.value.id ? 'Prodotto aggiornato' : 'Prodotto creato');
        editing.value = null;
        load(meta.value?.current_page);
    } catch (e) {
        errors.value = e.response?.data?.errors || {};
        toast(errorMessage(e), 'error');
    }
}

function openLoad(p) { loading.value = p; carico.quantita = Math.max(10, p.scorta_minima * 2 - p.giacenza); carico.nota = ''; errors.value = {}; }

async function saveLoad() {
    try {
        await api.post(`/products/${loading.value.id}/carico`, carico);
        toast(`${carico.quantita > 0 ? 'Caricati' : 'Tolti'} ${Math.abs(carico.quantita)} pz di ${loading.value.nome}`);
        loading.value = null;
        load(meta.value?.current_page);
    } catch (e) {
        errors.value = e.response?.data?.errors || {};
    }
}
const err = (k) => errors.value[k]?.[0];
</script>

<template>
  <div>
    <header class="page-head">
      <div><p class="eyebrow">Listino e magazzino</p><h1>Prodotti</h1></div>
      <button v-if="admin" class="btn" @click="openEdit()">+ Nuovo prodotto</button>
    </header>

    <div class="toolbar">
      <label class="switch"><input v-model="sotto" type="checkbox"> <span>Solo sotto scorta</span></label>
      <input v-model="q" type="search" placeholder="Cerca nome o SKU…" aria-label="Cerca prodotti">
    </div>

    <div class="card table-card">
      <table class="table">
        <thead><tr><th>SKU</th><th>Prodotto</th><th class="num">Prezzo</th><th class="num hide-sm">IVA</th><th class="num">Giacenza</th><th v-if="admin"></th></tr></thead>
        <tbody>
          <tr v-for="p in rows" :key="p.id" :class="{ inactive: !p.attivo }">
            <td class="mono">{{ p.sku }}</td>
            <td><strong>{{ p.nome }}</strong><span v-if="!p.attivo" class="sub">Non attivo</span></td>
            <td class="num">{{ euro(p.prezzo_cents) }}</td>
            <td class="num hide-sm">{{ p.iva }}%</td>
            <td class="num">
              <span class="stock" :class="{ low: p.sotto_scorta }">{{ p.giacenza }}</span>
              <span class="sub">min {{ p.scorta_minima }}</span>
            </td>
            <td v-if="admin" class="row-actions">
              <button class="btn btn-ghost btn-sm" @click="openLoad(p)">⇅ Carico</button>
              <button class="btn btn-ghost btn-sm" @click="openEdit(p)">Modifica</button>
            </td>
          </tr>
          <tr v-if="!rows.length"><td colspan="6" class="empty">Nessun prodotto.</td></tr>
        </tbody>
      </table>
      <Pager :meta="meta" @go="load" />
    </div>

    <Modal v-if="editing" :title="editing.id ? 'Modifica prodotto' : 'Nuovo prodotto'" @close="editing = null">
      <form class="form" @submit.prevent="saveProduct">
        <div class="grid-3">
          <label>SKU<input v-model="form.sku" required><span v-if="err('sku')" class="error">{{ err('sku') }}</span></label>
          <label class="span-2">Nome<input v-model="form.nome" required><span v-if="err('nome')" class="error">{{ err('nome') }}</span></label>
        </div>
        <label>Descrizione<input v-model="form.descrizione"></label>
        <div class="grid-3">
          <label>Prezzo (€)<input v-model="form.prezzo" type="number" step="0.01" min="0" required></label>
          <label>IVA<select v-model.number="form.iva"><option v-for="v in [22, 10, 4, 0]" :key="v" :value="v">{{ v }}%</option></select></label>
          <label>Scorta minima<input v-model.number="form.scorta_minima" type="number" min="0" required></label>
        </div>
        <label v-if="!editing.id">Giacenza iniziale<input v-model.number="form.giacenza" type="number" min="0"></label>
        <label class="check"><input v-model="form.attivo" type="checkbox"> Prodotto attivo (ordinabile)</label>
        <div class="form-actions"><button type="button" class="btn btn-ghost" @click="editing = null">Annulla</button><button class="btn">Salva</button></div>
      </form>
    </Modal>

    <Modal v-if="loading" :title="'Movimento di magazzino'" @close="loading = null">
      <form class="form" @submit.prevent="saveLoad">
        <p><strong>{{ loading.nome }}</strong><br><span class="muted">Giacenza attuale: {{ loading.giacenza }} pz</span></p>
        <label>Quantità <span class="muted small">(negativa per togliere merce)</span><input v-model.number="carico.quantita" type="number" required><span v-if="err('quantita')" class="error">{{ err('quantita') }}</span></label>
        <label>Nota<input v-model="carico.nota" placeholder="Es. DDT fornitore n. 1234"></label>
        <p class="muted small">Nuova giacenza: <strong>{{ loading.giacenza + (Number(carico.quantita) || 0) }}</strong> pz</p>
        <div class="form-actions"><button type="button" class="btn btn-ghost" @click="loading = null">Annulla</button><button class="btn">Registra</button></div>
      </form>
    </Modal>
  </div>
</template>
