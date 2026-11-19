<script setup>
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api, { csrf, errorMessage } from '../api';
import { session } from '../store';

const router = useRouter();
const route = useRoute();
const form = reactive({ email: 'admin@ordinato.test', password: 'password' });
const error = ref('');
const loading = ref(false);

async function submit() {
    error.value = '';
    loading.value = true;
    try {
        await csrf();
        session.user = (await api.post('/login', form)).data.data;
        router.push(route.query.redirect || '/');
    } catch (e) {
        error.value = errorMessage(e);
    } finally {
        loading.value = false;
    }
}

function demo(email) {
    form.email = email;
    form.password = 'password';
    submit();
}
</script>

<template>
  <div class="login">
    <section class="login-side">
      <div class="brand light"><span class="brand-mark">✓</span> Ordinato</div>
      <div>
        <h1>Ordini, magazzino e clienti.<br><span>In un unico posto.</span></h1>
        <ul class="login-points">
          <li>Lo stock si aggiorna da solo quando confermi un ordine</li>
          <li>Avvisi per i prodotti sotto scorta</li>
          <li>PDF dell'ordine in un clic</li>
        </ul>
      </div>
      <p class="small">Progetto demo di Ayoub Balhouh · Laravel + Vue</p>
    </section>

    <section class="login-main">
      <form class="login-card" @submit.prevent="submit">
        <h2>Accedi</h2>
        <p class="muted">Usa un account demo per provare l'app.</p>
        <label>Email<input v-model="form.email" type="email" autocomplete="username" required></label>
        <label>Password<input v-model="form.password" type="password" autocomplete="current-password" required></label>
        <p v-if="error" class="error">{{ error }}</p>
        <button class="btn btn-block btn-lg" :disabled="loading">{{ loading ? 'Accesso…' : 'Entra' }}</button>
        <div class="demo-accounts">
          <span class="muted small">Accesso rapido:</span>
          <button type="button" class="chip" @click="demo('admin@ordinato.test')">Amministratore</button>
          <button type="button" class="chip" @click="demo('operatore@ordinato.test')">Operatore</button>
        </div>
      </form>
    </section>
  </div>
</template>
