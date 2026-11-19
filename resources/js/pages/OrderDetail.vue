<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import api, { errorMessage } from '../api';
import { toast } from '../store';
import { euro, date, dateTime, STATI } from '../format';
import StatusBadge from '../components/StatusBadge.vue';

const props = defineProps({ id: String });
const router = useRouter();
const o = ref(null);
const busy = ref(false);
const flow = ['bozza', 'confermato', 'spedito', 'consegnato'];

onMounted(async () => { o.value = (await api.get('/orders/' + props.id)).data.data; });

async function move(stato) {
    if (stato === 'annullato' && !confirm(`Annullare l'ordine ${o.value.numero}?` + (o.value.stato !== 'bozza' ? ' La merce tornerà in magazzino.' : ''))) return;
    busy.value = true;
    try {
        o.value = (await api.post(`/orders/${o.value.id}/stato`, { stato })).data.data;
        toast(`Ordine ${STATI[stato].label.toLowerCase()}`);
    } catch (e) {
        toast(errorMessage(e), 'error');
    } finally {
        busy.value = false;
    }
}

async function remove() {
    if (!confirm(`Eliminare la bozza ${o.value.numero}?`)) return;
    await api.delete('/orders/' + o.value.id);
    toast('Bozza eliminata');
    router.push('/ordini');
}

const at = (s) => o.value.storico.find((h) => h.stato === s)?.at;
</script>

<template>
  <div v-if="o">
    <header class="page-head">
      <div><router-link to="/ordini" class="back">← Ordini</router-link><h1>{{ o.numero }} <StatusBadge :stato="o.stato" /></h1></div>
      <div class="head-actions">
        <a :href="`/api/orders/${o.id}/pdf`" target="_blank" class="btn btn-ghost">⇩ PDF</a>
        <template v-if="o.stato === 'bozza'">
          <router-link :to="`/ordini/${o.id}/modifica`" class="btn btn-ghost">Modifica</router-link>
          <button class="btn btn-ghost danger" @click="remove">Elimina</button>
        </template>
        <button v-for="s in o.prossimi_stati.filter((s) => s !== 'annullato')" :key="s" class="btn" :disabled="busy" @click="move(s)">{{ STATI[s].action }}</button>
        <button v-if="o.prossimi_stati.includes('annullato')" class="btn btn-ghost danger" :disabled="busy" @click="move('annullato')">Annulla ordine</button>
      </div>
    </header>

    <ol v-if="o.stato !== 'annullato'" class="steps">
      <li v-for="(s, i) in flow" :key="s" :class="{ done: flow.indexOf(o.stato) >= i, current: o.stato === s }">
        <span class="step-dot">{{ flow.indexOf(o.stato) > i ? '✓' : i + 1 }}</span>
        <div><strong>{{ STATI[s].label }}</strong><span class="muted small">{{ at(s) ? dateTime(at(s)) : '—' }}</span></div>
      </li>
    </ol>
    <p v-else class="alert">Ordine annullato il {{ dateTime(at('annullato')) }}. {{ o.storico.some((h) => h.stato === 'confermato') ? 'La merce è stata rimessa in magazzino.' : '' }}</p>

    <div class="detail-grid">
      <section class="card">
        <div class="card-head"><h2>Prodotti</h2><span class="muted small">{{ o.items.length }} righe</span></div>
        <table class="table plain">
          <thead><tr><th>Prodotto</th><th class="num">Q.tà</th><th class="num">Prezzo</th><th class="num hide-sm">IVA</th><th class="num">Importo</th></tr></thead>
          <tbody>
            <tr v-for="i in o.items" :key="i.id"><td>{{ i.descrizione }}</td><td class="num">{{ i.quantita }}</td><td class="num">{{ euro(i.prezzo_cents) }}</td><td class="num hide-sm">{{ i.iva }}%</td><td class="num"><strong>{{ euro(i.totale_cents) }}</strong></td></tr>
          </tbody>
        </table>
        <div class="totals right-totals">
          <div><span>Imponibile</span><span>{{ euro(o.imponibile_cents) }}</span></div>
          <div><span>IVA</span><span>{{ euro(o.iva_cents) }}</span></div>
          <div class="grand"><span>Totale</span><span>{{ euro(o.totale_cents) }}</span></div>
        </div>
      </section>

      <aside class="side-cards">
        <section class="card">
          <p class="eyebrow">Cliente</p>
          <h3>{{ o.customer.ragione_sociale }}</h3>
          <p class="muted small">{{ o.customer.indirizzo }}<br>{{ o.customer.citta }}</p>
          <p class="small" v-if="o.customer.piva">P.IVA {{ o.customer.piva }}</p>
          <a v-if="o.customer.email" :href="'mailto:' + o.customer.email" class="link small">{{ o.customer.email }}</a>
        </section>
        <section class="card">
          <p class="eyebrow">Dettagli</p>
          <dl class="dl">
            <div><dt>Consegna prevista</dt><dd>{{ date(o.data_consegna) }}</dd></div>
            <div><dt>Creato da</dt><dd>{{ o.creato_da || '—' }}</dd></div>
            <div><dt>Creato il</dt><dd>{{ dateTime(o.created_at) }}</dd></div>
          </dl>
          <p v-if="o.note" class="note">“{{ o.note }}”</p>
        </section>
      </aside>
    </div>
  </div>
  <div v-else class="skeleton" style="height: 300px"></div>
</template>
