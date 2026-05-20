<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'

interface Option {
    id: number | string
    name: string
}

const props = defineProps<{
    modelValue: number | string | null
    options: Option[]
    placeholder?: string
    label?: string
    error?: string
}>()

const emit = defineEmits<{
    'update:modelValue': [value: number | string | null]
}>()

const search = ref('')
const open = ref(false)
const openUpward = ref(false)
const container = ref<HTMLElement | null>(null)

const DROPDOWN_HEIGHT = 260 // approx: search input + up to 6 list items

function toggle() {
    if (!open.value && container.value) {
        const rect = container.value.getBoundingClientRect()
        openUpward.value = window.innerHeight - rect.bottom < DROPDOWN_HEIGHT
    }
    open.value = !open.value
}

const selected = computed(() =>
    props.options.find(o => o.id === props.modelValue) ?? null
)

const filtered = computed(() => {
    if (!search.value) return props.options
    return props.options.filter(o =>
        o.name.toLowerCase().includes(search.value.toLowerCase())
    )
})

function select(option: Option) {
    emit('update:modelValue', option.id)
    search.value = ''
    open.value = false
}

function clear() {
    emit('update:modelValue', null)
    search.value = ''
}

function handleClickOutside(e: MouseEvent) {
    if (container.value && !container.value.contains(e.target as Node)) {
        open.value = false
        openUpward.value = false
        search.value = ''
    }
}

onMounted(() => document.addEventListener('mousedown', handleClickOutside))
onBeforeUnmount(() => document.removeEventListener('mousedown', handleClickOutside))
</script>

<template>
    <div ref="container" class="relative w-full">
        <div class="input input-bordered w-full flex items-center justify-between cursor-pointer gap-2"
            :class="{ 'input-error': error }" @click="toggle">
            <span v-if="selected" class="truncate">{{ selected.name }}</span>
            <span v-else class="text-base-content/40">{{ placeholder ?? 'Select an option' }}</span>
            <button v-if="selected" type="button" class="text-base-content/40 hover:text-base-content"
                @click.stop="clear">
                ✕
            </button>
            <span v-else class="text-base-content/40">▾</span>
        </div>

        <div v-if="open" class="absolute z-50 w-full bg-base-100 border border-base-300 rounded-box shadow-lg"
            :class="openUpward ? 'bottom-full mb-1' : 'top-full mt-1'">
            <div class="p-2">
                <input v-model="search" type="text" class="input input-bordered input-sm w-full" placeholder="Search..."
                    @click.stop autofocus />
            </div>
            <ul class="max-h-48 overflow-y-auto">
                <li v-if="filtered.length === 0" class="px-4 py-2 text-sm text-base-content/40">
                    No results found
                </li>
                <li v-for="option in filtered" :key="option.id"
                    class="px-4 py-2 text-sm cursor-pointer hover:bg-base-200"
                    :class="{ 'bg-base-200 font-medium': option.id === modelValue }" @click="select(option)">
                    {{ option.name }}
                </li>
            </ul>
        </div>

        <p v-if="error" class="mt-1 text-xs text-error">{{ error }}</p>
    </div>
</template>