<script setup>
import { onMounted, ref } from 'vue';
import api from '../api';
import { session } from '../store';
import { euro, date } from '../format';
import BarChart from '../components/BarChart.vue';
import StatusBadge from '../components/StatusBadge.vue';

const d = ref(null);
onMounted(async () => { d.value = (await api.get('/dashboard')).data; });
const saluto = () => { const h = new Date().getHours(); return h < 13 ? 'Buongiorno' : h < 18 ? 'Buon pomeriggio' : 'Buonasera'; };
</script>

<template>
  <div>
    <header class="page-head">
      <div><p class="eyebrow">Panoramica</p><h1>{{ saluto() }}, {{ session.user?.name.split(' ')[0] }}</h1></div>
      <router-link to="/ordini/nuovo" class="btn">+ Nuovo ordine</router-link>
    </header>

    <div v-if="!d" class="skeleton-grid"><div v-for="i in 4" :key="i" class="skeleton"></div></div>
    <template v-else>
      <section class="kpis">
        <article class="kpi"><span>Fatturato del mese</span><strong>{{ euro(d.kpi.fatturato_mese_cents) }}</strong><em>{{ d.kpi.ordini_mese }} ordini confermati</em></article>
        <router-link to="/ordini?stato=confermato" class="kpi kpi-link"><span>Da spedire</span><strong>{{ d.kpi.da_spedire }}</strong><em>ordini confermati →</em></router-link>
        <router-link to="/prodotti?sotto_scorta=1" class="kpi kpi-link" :class="{ alert: d.kpi.sotto_scorta }"><span>Sotto scorta</span><strong>{{ d.kpi.sotto_scorta }}</strong><em>prodotti da riordinare →</em></router-link>
        <router-link to="/ordini?stato=bozza" class="kpi kpi-link"><span>Bozze</span><strong>{{ d.kpi.bozze }}</strong><em>da completare →</em></router-link>
      </section>

      <section class="card">
        <div class="card-head"><h2>Fatturato degli ultimi 6 mesi</h2><span class="muted small">Imponibile degli ordini confermati</span></div>
        <BarChart :data="d.mesi" />
      </section>

      <div class="two-col">
        <section class="card">
          <div class="card-head"><h2>Ultimi ordini</h2><router-link to="/ordini" class="link">Tutti</router-link></div>
          <ul class="list">
            <li v-for="o in d.ultimi_ordini" :key="o.id">
              <router-link :to="'/ordini/' + o.id">
                <div><strong>{{ o.customer.ragione_sociale }}</strong><span class="muted small">{{ o.numero }} · {{ date(o.created_at) }}</span></div>
                <div class="right"><strong>{{ euro(o.totale_cents) }}</strong><StatusBadge :stato="o.stato" /></div>
              </router-link>
            </li>
          </ul>
        </section>
        <section class="card">
          <div class="card-head"><h2>Da riordinare</h2><router-link to="/prodotti?sotto_scorta=1" class="link">Vedi</router-link></div>
          <p v-if="!d.sotto_scorta.length" class="empty">Tutte le giacenze sono sopra la scorta minima. 👍</p>
          <ul v-else class="list">
            <li v-for="p in d.sotto_scorta" :key="p.id">
              <router-link :to="'/prodotti?q=' + p.sku">
                <div><strong>{{ p.nome }}</strong><span class="muted small">{{ p.sku }} · minimo {{ p.scorta_minima }}</span></div>
                <div class="right"><span class="stock low">{{ p.giacenza }} pz</span></div>
              </router-link>
            </li>
          </ul>
        </section>
      </div>
    </template>
  </div>
</template>
