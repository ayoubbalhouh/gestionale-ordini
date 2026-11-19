<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import api, { errorMessage } from '../api';
import { toast } from '../store';
import { euro } from '../format';

const props = defineProps({ id: String });
const router = useRouter();
const customers = ref([]);
const products = ref([]);
const saving = ref(false);
const errors = ref({});
const form = reactive({ customer_id: '', data_consegna: '', note: '', items: [] });
const numero = ref('');

onMounted(async () => {
    const [c, p] = await Promise.all([api.get('/customers', { params: { all: 1 } }), api.get('/products', { params: { all: 1, solo_attivi: 1 } })]);
    customers.value = c.data.data;
    products.value = p.data.data;
    if (props.id) {
        const o = (await api.get('/orders/' + props.id)).data.data;
        if (o.stato !== 'bozza') return router.replace('/ordini/' + o.id);
        numero.value = o.numero;
        Object.assign(form, { customer_id: o.customer.id, data_consegna: o.data_consegna || '', note: o.note || '', items: o.items.map((i) => ({ product_id: i.product_id, quantita: i.quantita })) });
    } else {
        addRow();
    }
});

const byId = computed(() => Object.fromEntries(products.value.map((p) => [p.id, p])));
const lineTotal = (r) => (byId.value[r.product_id]?.prezzo_cents || 0) * (r.quantita || 0);
const totals = computed(() => {
    let imp = 0, iva = 0;
    form.items.forEach((r) => { const p = byId.value[r.product_id]; if (!p) return; imp += lineTotal(r); iva += lineTotal(r) * p.iva / 100; });
    return { imp, iva: Math.round(iva), tot: imp + Math.round(iva) };
});
const usedIds = computed(() => form.items.map((r) => Number(r.product_id)));
const stockWarning = (r) => { const p = byId.value[r.product_id]; return p && r.quantita > p.giacenza; };

function addRow() { form.items.push({ product_id: '', quantita: 1 }); }
function removeRow(i) { form.items.splice(i, 1); }
const err = (key) => errors.value[key]?.[0];

async function save() {
    saving.value = true;
    errors.value = {};
    const payload = { ...form, data_consegna: form.data_consegna || null, items: form.items.filter((r) => r.product_id) };
    try {
        const res = props.id ? await api.put('/orders/' + props.id, payload) : await api.post('/orders', payload);
        toast(props.id ? 'Ordine aggiornato' : `Ordine ${res.data.data.numero} creato`);
        router.push('/ordini/' + res.data.data.id);
    } catch (e) {
        errors.value = e.response?.data?.errors || {};
        toast(errorMessage(e), 'error');
    } finally {
        saving.value = false;
    }
}
</script>

<template>
  <div>
    <header class="page-head">
      <div><router-link :to="id ? '/ordini/' + id : '/ordini'" class="back">← Indietro</router-link><h1>{{ id ? 'Modifica ' + numero : 'Nuovo ordine' }}</h1></div>
    </header>

    <form class="card form" @submit.prevent="save">
      <div class="grid-3">
        <label class="span-2">Cliente
          <select v-model="form.customer_id" :class="{ invalid: err('customer_id') }" required>
            <option value="" disabled>Scegli un cliente…</option>
            <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.ragione_sociale }} · {{ c.citta }}</option>
          </select>
          <span v-if="err('customer_id')" class="error">{{ err('customer_id') }}</span>
        </label>
        <label>Consegna prevista
          <input v-model="form.data_consegna" type="date" :min="new Date().toISOString().slice(0, 10)">
          <span v-if="err('data_consegna')" class="error">{{ err('data_consegna') }}</span>
        </label>
      </div>

      <div class="lines">
        <div class="lines-head"><span>Prodotto</span><span class="num">Disponibili</span><span class="num">Quantità</span><span class="num">Prezzo</span><span class="num">Importo</span><span></span></div>
        <div v-for="(r, i) in form.items" :key="i" class="line">
          <select v-model.number="r.product_id" :class="{ invalid: err('items.' + i + '.product_id') }" aria-label="Prodotto">
            <option value="" disabled>Scegli un prodotto…</option>
            <option v-for="p in products" :key="p.id" :value="p.id" :disabled="usedIds.includes(p.id) && p.id !== r.product_id">{{ p.sku }} · {{ p.nome }}</option>
          </select>
          <span class="num stock" :class="{ low: stockWarning(r) }">{{ byId[r.product_id]?.giacenza ?? '—' }}</span>
          <input v-model.number="r.quantita" type="number" min="1" class="num" aria-label="Quantità">
          <span class="num muted">{{ r.product_id ? euro(byId[r.product_id]?.prezzo_cents) : '—' }}</span>
          <strong class="num">{{ euro(lineTotal(r)) }}</strong>
          <button type="button" class="icon-btn" aria-label="Rimuovi" :disabled="form.items.length === 1" @click="removeRow(i)">×</button>
          <span v-if="stockWarning(r)" class="line-warn">Giacenza insufficiente: potrai salvare la bozza ma non confermarla finché non arriva la merce.</span>
        </div>
        <span v-if="err('items')" class="error">{{ err('items') }}</span>
        <button type="button" class="btn btn-ghost btn-sm add-line" @click="addRow">+ Aggiungi prodotto</button>
      </div>

      <div class="form-bottom">
        <label>Note <textarea v-model="form.note" rows="3" placeholder="Es. consegnare entro le 10, citofono 2"></textarea></label>
        <div class="totals">
          <div><span>Imponibile</span><span>{{ euro(totals.imp) }}</span></div>
          <div><span>IVA</span><span>{{ euro(totals.iva) }}</span></div>
          <div class="grand"><span>Totale</span><span>{{ euro(totals.tot) }}</span></div>
        </div>
      </div>

      <div class="form-actions">
        <router-link :to="id ? '/ordini/' + id : '/ordini'" class="btn btn-ghost">Annulla</router-link>
        <button class="btn" :disabled="saving">{{ saving ? 'Salvataggio…' : 'Salva bozza' }}</button>
      </div>
    </form>
  </div>
</template>
