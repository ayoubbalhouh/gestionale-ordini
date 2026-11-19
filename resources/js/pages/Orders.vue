<script setup>
import { onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../api';
import { euro, date, STATI } from '../format';
import StatusBadge from '../components/StatusBadge.vue';
import Pager from '../components/Pager.vue';

const route = useRoute();
const router = useRouter();
const rows = ref([]);
const meta = ref(null);
const counts = ref({});
const loading = ref(true);
const q = ref(route.query.q || '');
const stato = ref(route.query.stato || '');

async function load(page = 1) {
    loading.value = true;
    const res = await api.get('/orders', { params: { page, q: q.value || undefined, stato: stato.value || undefined } });
    rows.value = res.data.data;
    meta.value = res.data.meta;
    counts.value = res.data.conteggi;
    loading.value = false;
}

let timer;
watch(q, () => { clearTimeout(timer); timer = setTimeout(() => load(), 300); });
watch(stato, () => { router.replace({ query: { ...route.query, stato: stato.value || undefined } }); load(); });
onMounted(load);
const total = () => Object.values(counts.value).reduce((a, b) => a + b, 0);
</script>

<template>
  <div>
    <header class="page-head">
      <div><p class="eyebrow">Vendite</p><h1>Ordini</h1></div>
      <router-link to="/ordini/nuovo" class="btn">+ Nuovo ordine</router-link>
    </header>

    <div class="toolbar">
      <div class="chips">
        <button class="chip" :class="{ active: !stato }" @click="stato = ''">Tutti <em>{{ total() }}</em></button>
        <button v-for="(s, key) in STATI" :key="key" class="chip" :class="{ active: stato === key }" @click="stato = key">{{ s.label }} <em>{{ counts[key] || 0 }}</em></button>
      </div>
      <input v-model="q" type="search" placeholder="Cerca cliente o numero…" aria-label="Cerca ordini">
    </div>

    <div class="card table-card" :class="{ loading }">
      <table class="table">
        <thead><tr><th>Numero</th><th>Cliente</th><th class="hide-sm">Data</th><th class="hide-sm">Consegna</th><th class="num">Totale</th><th>Stato</th></tr></thead>
        <tbody>
          <tr v-for="o in rows" :key="o.id" class="clickable" tabindex="0" @click="router.push('/ordini/' + o.id)" @keydown.enter="router.push('/ordini/' + o.id)">
            <td class="mono">{{ o.numero }}</td>
            <td><strong>{{ o.customer.ragione_sociale }}</strong><span class="sub">{{ o.items_count }} prodotti · {{ o.customer.citta }}</span></td>
            <td class="hide-sm">{{ date(o.created_at) }}</td>
            <td class="hide-sm">{{ date(o.data_consegna) }}</td>
            <td class="num"><strong>{{ euro(o.totale_cents) }}</strong></td>
            <td><StatusBadge :stato="o.stato" /></td>
          </tr>
          <tr v-if="!loading && !rows.length"><td colspan="6" class="empty">Nessun ordine trovato.</td></tr>
        </tbody>
      </table>
      <Pager :meta="meta" @go="load" />
    </div>
  </div>
</template>
