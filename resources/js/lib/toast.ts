import { reactive } from 'vue';

export type ToastType = 'success' | 'error' | 'default';

export type ToastItem = {
    id: number;
    type: ToastType;
    message: string;
};

export const toasts = reactive<ToastItem[]>([]);

let nextId = 0;
const DEFAULT_DURATION = 3000;

export function dismiss(id: number): void {
    const index = toasts.findIndex((t) => t.id === id);
    if (index !== -1) {
        toasts.splice(index, 1);
    }
}

function show(type: ToastType, message: string): number {
    const id = ++nextId;
    toasts.push({ id, type, message });
    setTimeout(() => dismiss(id), DEFAULT_DURATION);
    return id;
}

type ToastFn = ((message: string) => number) & {
    success: (message: string) => number;
    error: (message: string) => number;
    dismiss: (id: number) => void;
};

export const toast: ToastFn = Object.assign(
    (message: string) => show('default', message),
    {
        success: (message: string) => show('success', message),
        error: (message: string) => show('error', message),
        dismiss,
    },
);
