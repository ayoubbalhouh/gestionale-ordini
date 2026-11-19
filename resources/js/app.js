import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import api from './api';
import { session } from './store';

// Prima di montare l'app controlliamo se l'utente ha già una sessione
async function boot() {
    try {
        session.user = (await api.get('/me')).data.data;
    } catch {
        session.user = null;
    }
    session.ready = true;
    createApp(App).use(router).mount('#app');
}

boot();
