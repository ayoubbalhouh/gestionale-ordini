<script setup>
import { onMounted, reactive, ref } from 'vue';
import api, { errorMessage } from '../api';
import { session, toast } from '../store';
import Modal from '../components/Modal.vue';

const users = ref([]);
const adding = ref(false);
const form = reactive({ name: '', email: '', role: 'operatore', password: '' });
const errors = ref({});

const load = async () => { users.value = (await api.get('/users')).data.data; };
onMounted(load);

async function save() {
    errors.value = {};
    try {
        await api.post('/users', form);
        toast('Utente creato');
        adding.value = false;
        Object.assign(form, { name: '', email: '', role: 'operatore', password: '' });
        load();
    } catch (e) {
        errors.value = e.response?.data?.errors || {};
    }
}

async function remove(u) {
    if (!confirm(`Eliminare ${u.name}?`)) return;
    try { await api.delete('/users/' + u.id); toast('Utente eliminato'); load(); } catch (e) { toast(errorMessage(e), 'error'); }
}
const err = (k) => errors.value[k]?.[0];
</script>

<template>
  <div>
    <header class="page-head">
      <div><p class="eyebrow">Accessi</p><h1>Utenti</h1></div>
      <button class="btn" @click="adding = true">+ Nuovo utente</button>
    </header>
    <div class="users">
      <article v-for="u in users" :key="u.id" class="card user">
        <div class="avatar lg">{{ u.name.charAt(0) }}</div>
        <div class="user-info"><strong>{{ u.name }}</strong><span class="muted small">{{ u.email }}</span></div>
        <span class="tag" :class="u.role === 'admin' ? 't-carico' : 't-ordine'">{{ u.role === 'admin' ? 'Amministratore' : 'Operatore' }}</span>
        <button v-if="u.id !== session.user?.id" class="icon-btn danger" title="Elimina" @click="remove(u)">×</button>
      </article>
    </div>
    <p class="muted small">Gli operatori gestiscono ordini e clienti. Gli amministratori anche listino, magazzino e utenti.</p>

    <Modal v-if="adding" title="Nuovo utente" @close="adding = false">
      <form class="form" @submit.prevent="save">
        <label>Nome<input v-model="form.name" required><span v-if="err('name')" class="error">{{ err('name') }}</span></label>
        <label>Email<input v-model="form.email" type="email" required><span v-if="err('email')" class="error">{{ err('email') }}</span></label>
        <label>Ruolo<select v-model="form.role"><option value="operatore">Operatore</option><option value="admin">Amministratore</option></select></label>
        <label>Password<input v-model="form.password" type="password" minlength="8" required><span v-if="err('password')" class="error">{{ err('password') }}</span></label>
        <div class="form-actions"><button type="button" class="btn btn-ghost" @click="adding = false">Annulla</button><button class="btn">Crea utente</button></div>
      </form>
    </Modal>
  </div>
</template>
