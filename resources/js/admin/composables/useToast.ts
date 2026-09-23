import { reactive } from 'vue';

export interface ToastMessage {
    id: number;
    message: string;
    variant: 'success' | 'error' | 'info';
}

const toasts = reactive<ToastMessage[]>([]);
let nextId = 1;

export function useToast() {
    function push(message: string, variant: ToastMessage['variant'] = 'info') {
        const id = nextId++;
        toasts.push({ id, message, variant });
        setTimeout(() => remove(id), 4000);
    }

    function remove(id: number) {
        const index = toasts.findIndex((t) => t.id === id);
        if (index !== -1) toasts.splice(index, 1);
    }

    return {
        toasts,
        success: (message: string) => push(message, 'success'),
        error: (message: string) => push(message, 'error'),
        info: (message: string) => push(message, 'info'),
        remove,
    };
}
