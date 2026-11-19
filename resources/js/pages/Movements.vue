<script setup>
import { onMounted, ref, watch } from 'vue';
import api from '../api';
import { dateTime } from '../format';
import Pager from '../components/Pager.vue';

const rows = ref([]);
const meta = ref(null);
const tipo = ref('');
const TIPI = { carico: 'Carico', ordine: 'Ordine', annullo: 'Annullo', rettifica: 'Rettifica' };

async function load(page = 1) {
    const res = await api.get('/movimenti', { params: { page, tipo: tipo.value || undefined } });
    rows.value = res.data.data;
    meta.value = res.data.meta;
}
watch(tipo, () => load());
onMounted(load);
</script>

<template>
  <div>
    <header class="page-head"><div><p class="eyebrow">Magazzino</p><h1>Movimenti</h1></div></header>
    <div class="toolbar">
      <div class="chips">
        <button class="chip" :class="{ active: !tipo }" @click="tipo = ''">Tutti</button>
        <button v-for="(l, k) in TIPI" :key="k" class="chip" :class="{ active: tipo === k }" @click="tipo = k">{{ l }}</button>
      </div>
    </div>
    <div class="card table-card">
      <table class="table">
        <thead><tr><th>Data</th><th>Prodotto</th><th>Tipo</th><th class="num">Quantità</th><th class="hide-sm">Riferimento</th></tr></thead>
        <tbody>
          <tr v-for="m in rows" :key="m.id">
            <td class="nowrap">{{ dateTime(m.created_at) }}</td>
            <td><strong>{{ m.prodotto.nome }}</strong><span class="sub mono">{{ m.prodotto.sku }}</span></td>
            <td><span class="tag" :class="'t-' + m.tipo">{{ TIPI[m.tipo] }}</span></td>
            <td class="num"><strong :class="m.quantita > 0 ? 'pos' : 'neg'">{{ m.quantita > 0 ? '+' : '' }}{{ m.quantita }}</strong></td>
            <td class="hide-sm">
              <router-link v-if="m.ordine" :to="'/ordini/' + m.ordine.id" class="link">{{ m.ordine.numero }}</router-link>
              <span v-else class="muted">{{ m.nota || '—' }}</span>
              <span v-if="m.utente" class="sub">{{ m.utente }}</span>
            </td>
          </tr>
        </tbody>
      </table>
      <Pager :meta="meta" @go="load" />
    </div>
  </div>
</template>
