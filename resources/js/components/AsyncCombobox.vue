<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, computed } from 'vue'

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
    loading?: boolean
}>()

const emit = defineEmits<{
    'update:modelValue': [value: number | string | null]
    'search': [query: string]
}>()

const search = ref('')
const open = ref(false)
const container = ref<HTMLElement | null>(null)
const triggerRef = ref<HTMLElement | null>(null)
const dropdownStyle = ref<Record<string, string>>({})

const DROPDOWN_HEIGHT = 260

function updatePosition() {
    if (!triggerRef.value) return
    const rect = triggerRef.value.getBoundingClientRect()
    const spaceBelow = window.innerHeight - rect.bottom

    if (spaceBelow < DROPDOWN_HEIGHT) {
        dropdownStyle.value = {
            position: 'fixed',
            bottom: `${window.innerHeight - rect.top + 4}px`,
            left: `${rect.left}px`,
            width: `${rect.width}px`,
        }
    } else {
        dropdownStyle.value = {
            position: 'fixed',
            top: `${rect.bottom + 4}px`,
            left: `${rect.left}px`,
            width: `${rect.width}px`,
        }
    }
}

function toggle() {
    open.value = !open.value
    if (open.value) {
        updatePosition()
        emit('search', '')
    }
}

const selected = computed(() =>
    props.options.find(o => o.id === props.modelValue) ?? null
)

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
    const target = e.target as HTMLElement
    if (
        container.value && !container.value.contains(target) &&
        !target.closest('.async-combobox-dropdown')
    ) {
        open.value = false
        search.value = ''
    }
}

onMounted(() => document.addEventListener('mousedown', handleClickOutside))
onBeforeUnmount(() => document.removeEventListener('mousedown', handleClickOutside))
</script>

<template>
    <div ref="container" class="relative w-full">
        <div ref="triggerRef" class="input input-bordered w-full flex items-center justify-between cursor-pointer gap-2"
            :class="{ 'input-error': error }" @click="toggle">
            <span v-if="selected" class="truncate">{{ selected.name }}</span>
            <span v-else class="text-base-content/40">{{ placeholder ?? 'Select an option' }}</span>
            <button v-if="selected" type="button" class="text-base-content/40 hover:text-base-content"
                @click.stop="clear">
                ✕
            </button>
            <span v-else class="text-base-content/40">▾</span>
        </div>

        <Teleport to="body">
            <div v-if="open"
                class="async-combobox-dropdown z-[9999] bg-base-100 border border-base-300 rounded-box shadow-lg"
                :style="dropdownStyle">
                <div class="p-2">
                    <input v-model="search" type="text" class="input input-bordered input-sm w-full"
                        placeholder="Search..." @click.stop @input="emit('search', search)" autofocus />
                </div>
                <ul class="max-h-48 overflow-y-auto">
                    <li v-if="loading" class="px-4 py-2 text-sm text-base-content/40">
                        Loading...
                    </li>
                    <li v-else-if="options.length === 0" class="px-4 py-2 text-sm text-base-content/40">
                        No results found
                    </li>
                    <li v-for="option in options" :key="option.id"
                        class="px-4 py-2 text-sm cursor-pointer hover:bg-base-200"
                        :class="{ 'bg-base-200 font-medium': option.id === modelValue }" @click="select(option)">
                        {{ option.name }}
                    </li>
                </ul>
            </div>
        </Teleport>

        <p v-if="error" class="mt-1 text-xs text-error">{{ error }}</p>
    </div>
</template>