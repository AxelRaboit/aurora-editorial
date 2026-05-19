<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from "vue";
import { ChevronDown, Check } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";

const props = defineProps({
    currentLocale: { type: String, required: true },
    locales: {
        type: Array,
        required: true,
        // [{ code: 'fr', name: 'Français', flagCode: 'fr', url: '/fr/...' }, ...]
    },
});

const open = ref(false);
const dropdownRef = ref(null);

const current = computed(() => props.locales.find((l) => l.code === props.currentLocale) ?? props.locales[0]);

function toggle() {
    open.value = !open.value;
}

function select(locale) {
    open.value = false;
    if (locale.code !== props.currentLocale) {
        window.location.href = locale.url;
    }
}

function onClickOutside(event) {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        open.value = false;
    }
}

onMounted(() => document.addEventListener("click", onClickOutside));
onBeforeUnmount(() => document.removeEventListener("click", onClickOutside));
</script>

<template>
    <div ref="dropdownRef" class="relative">
        <AppButton
            type="button"
            variant="front-ghost"
            size="none"
            :class="'inline-flex items-center gap-2 px-2 py-1.5 rounded-md transition-colors hover:opacity-80'"
            :aria-label="current?.name"
            v-on:click="toggle"
        >
            <span :class="`fi fi-${current?.flagCode}`" class="block w-5 h-4 rounded-sm shadow-sm" />
            <ChevronDown class="w-3.5 h-3.5 shrink-0 transition-transform" :class="{ 'rotate-180': open }" :stroke-width="2.5" />
        </AppButton>

        <transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="opacity-0 scale-95 -translate-y-1"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95 -translate-y-1"
        >
            <div
                v-if="open"
                class="absolute right-0 mt-2 min-w-45 rounded-lg border shadow-xl z-50 overflow-hidden origin-top-right"
                style="background-color: var(--th-surface); border-color: var(--color-border);"
            >
                <AppButton
                    v-for="locale in locales"
                    :key="locale.code"
                    type="button"
                    variant="front-primary"
                    size="none"
                    :class="['w-full flex items-center gap-3 px-3 py-2.5 text-sm text-left transition-colors', locale.code === currentLocale ? 'font-semibold' : 'hover:bg-surface-2']"
                    v-on:click="select(locale)"
                >
                    <span :class="`fi fi-${locale.flagCode}`" class="block w-5 h-4 rounded-sm shadow-sm shrink-0" />
                    <span class="flex-1">{{ locale.name }}</span>
                    <Check v-if="locale.code === currentLocale" class="w-4 h-4 shrink-0" :stroke-width="2.5" style="color: var(--th-accent);" />
                </AppButton>
            </div>
        </transition>
    </div>
</template>
