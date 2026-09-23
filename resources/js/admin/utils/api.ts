import axios from 'axios';

const api = axios.create({
    baseURL: '/api/admin',
    withCredentials: true,
    withXSRFToken: true,
    headers: {
        Accept: 'application/json',
    },
});

export async function ensureCsrfCookie(): Promise<void> {
    await axios.get('/sanctum/csrf-cookie', { withCredentials: true });
}

export default api;
