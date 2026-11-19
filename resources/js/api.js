import axios from 'axios';
import { session } from './store';
import router from './router';

// Client HTTP: cookie di sessione + token CSRF (Sanctum SPA)
const api = axios.create({
    baseURL: '/api',
    withCredentials: true,
    withXSRFToken: true,
    headers: { Accept: 'application/json' },
});

api.interceptors.response.use(
    (res) => res,
    (error) => {
        if (error.response?.status === 401 && router.currentRoute.value.name !== 'login') {
            session.user = null;
            router.push({ name: 'login', query: { redirect: router.currentRoute.value.fullPath } });
        }
        return Promise.reject(error);
    },
);

export async function csrf() {
    await axios.get('/sanctum/csrf-cookie', { withCredentials: true });
}

// Primo messaggio di errore leggibile da una risposta dell'API
export function errorMessage(error) {
    const data = error.response?.data;
    if (data?.errors) return Object.values(data.errors).flat()[0];
    if (error.response?.status === 403) return 'Non hai i permessi per questa operazione.';
    return data?.message || 'Si è verificato un errore. Riprova.';
}

export default api;
