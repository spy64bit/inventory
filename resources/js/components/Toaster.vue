<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { Icon } from '@iconify/vue';
import { toasts, dismiss, type ToastItem } from '@/lib/toast';

const mounted = ref(false);
onMounted(() => {
    mounted.value = true;
});

type Position =
    | 'top-left'
    | 'top-center'
    | 'top-right'
    | 'bottom-left'
    | 'bottom-center'
    | 'bottom-right';

const props = withDefaults(
    defineProps<{
        position?: Position;
        direction?: 'up' | 'down';
    }>(),
    {
        position: 'top-right',
        direction: 'down',
    },
);

const positionClass = computed(() => {
    const map: Record<Position, string> = {
        'top-left': 'top-4 left-4 items-start',
        'top-center': 'top-4 left-1/2 -translate-x-1/2 items-center',
        'top-right': 'top-4 right-4 items-end',
        'bottom-left': 'bottom-4 left-4 items-start',
        'bottom-center': 'bottom-4 left-1/2 -translate-x-1/2 items-center',
        'bottom-right': 'bottom-4 right-4 items-end',
    };
    return map[props.position];
});

const orderedToasts = computed<ToastItem[]>(() =>
    props.direction === 'up' ? [...toasts].reverse() : [...toasts],
);

function iconFor(type: ToastItem['type']): string {
    if (type === 'success') return 'mdi:check-circle';
    if (type === 'error') return 'mdi:close-circle';
    return 'mdi:information';
}

function iconColor(type: ToastItem['type']): string {
    if (type === 'success') return 'text-green-500';
    if (type === 'error') return 'text-red-500';
    return 'text-gray-500';
}
</script>

<template>
    <Teleport v-if="mounted" to="body">
        <div :class="['pointer-events-none fixed z-100 flex flex-col gap-2', positionClass]">
            <TransitionGroup name="toast">
                <div v-for="t in orderedToasts" :key="t.id" role="status"
                    class="pointer-events-auto flex min-w-60 max-w-sm cursor-pointer items-center gap-3 rounded-lg bg-white px-4 py-3 shadow-lg ring-1 ring-black/5"
                    @click="dismiss(t.id)">
                    <Icon :icon="iconFor(t.type)" :class="['shrink-0', iconColor(t.type)]" width="20" height="20" />
                    <span class="text-sm text-gray-800">{{ t.message }}</span>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: opacity 200ms ease, transform 200ms ease;
}

.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}

.toast-move {
    transition: transform 200ms ease;
}
</style>
