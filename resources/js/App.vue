<script setup>
import { ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from './api';
import { session, toasts } from './store';

const route = useRoute();
const router = useRouter();
const menuOpen = ref(false);
watch(() => route.fullPath, () => { menuOpen.value = false; });

const links = [
    { to: '/', label: 'Panoramica', icon: '▦', exact: true },
    { to: '/ordini', label: 'Ordini', icon: '▤' },
    { to: '/prodotti', label: 'Prodotti', icon: '◫' },
    { to: '/clienti', label: 'Clienti', icon: '◉' },
    { to: '/magazzino', label: 'Movimenti', icon: '⇅' },
];

async function logout() {
    await api.post('/logout');
    session.user = null;
    router.push({ name: 'login' });
}
</script>

<template>
  <router-view v-if="route.meta.guest" />

  <div v-else class="shell" :class="{ 'menu-open': menuOpen }">
    <aside class="side">
      <router-link to="/" class="brand"><span class="brand-mark">✓</span> Ordinato</router-link>
      <nav class="side-nav" aria-label="Menu">
        <router-link v-for="l in links" :key="l.to" :to="l.to" :class="{ exact: l.exact }" :exact-active-class="l.exact ? 'active' : ''" :active-class="l.exact ? '' : 'active'">
          <span class="ico">{{ l.icon }}</span>{{ l.label }}
        </router-link>
        <router-link v-if="session.user?.role === 'admin'" to="/utenti" active-class="active"><span class="ico">⚙</span>Utenti</router-link>
      </nav>
      <router-link to="/ordini/nuovo" class="btn btn-block">+ Nuovo ordine</router-link>
      <div class="me">
        <div class="avatar">{{ session.user?.name?.charAt(0) }}</div>
        <div class="me-info"><strong>{{ session.user?.name }}</strong><span>{{ session.user?.role === 'admin' ? 'Amministratore' : 'Operatore' }}</span></div>
        <button class="icon-btn" title="Esci" @click="logout">⎋</button>
      </div>
    </aside>

    <header class="topbar">
      <button class="icon-btn" aria-label="Menu" @click="menuOpen = !menuOpen">☰</button>
      <router-link to="/" class="brand"><span class="brand-mark">✓</span> Ordinato</router-link>
      <router-link to="/ordini/nuovo" class="btn btn-sm">+ Ordine</router-link>
    </header>
    <div class="scrim" @click="menuOpen = false"></div>

    <main class="content">
      <router-view />
    </main>
  </div>

  <div class="toasts" aria-live="polite">
    <transition-group name="toast">
      <div v-for="t in toasts" :key="t.id" class="toast" :class="t.type">{{ t.text }}</div>
    </transition-group>
  </div>
</template>
