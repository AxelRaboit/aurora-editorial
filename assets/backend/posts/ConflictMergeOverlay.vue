<script setup>
import { ref, computed, watch } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { X, Merge, Save } from "lucide-vue-next";
import {
    diffBlocks,
    applyMerge,
    countUnresolved,
    countConflicts,
    summarize,
    MergeKind,
} from "@/shared/utils/editor/mergeBlocks.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppTextLinkButton from "@/shared/components/action/AppTextLinkButton.vue";
import MergeBlockEntry from "./MergeBlockEntry.vue";

const { t } = useI18n();

const props = defineProps({
    show: { type: Boolean, default: false },
    base: { type: Object, default: () => ({}) },
    local: { type: Object, default: () => ({}) },
    remote: { type: Object, default: () => ({}) },
    locales: { type: Array, default: () => [] },
});

const emit = defineEmits(["close", "apply"]);

const activeLocale = ref(props.locales[0] ?? "fr");
const entriesByLocale = ref({});
const showUnchanged = ref(false);

function computeEntries() {
    const result = {};
    for (const locale of props.locales) {
        const baseBlocks = props.base?.[locale]?.blocks ?? [];
        const localBlocks = props.local?.[locale]?.blocks ?? [];
        const remoteBlocks = props.remote?.[locale]?.blocks ?? [];
        result[locale] = diffBlocks(baseBlocks, localBlocks, remoteBlocks);
    }
    entriesByLocale.value = result;

    const firstWithConflicts = props.locales.find(
        (l) => countConflicts(result[l]) > 0,
    );
    if (firstWithConflicts) activeLocale.value = firstWithConflicts;
}

watch(
    () => props.show,
    (val) => {
        if (val) {
            computeEntries();
            showUnchanged.value = false;
        }
    },
    { immediate: true },
);

function resolveEntry(entry, resolution) {
    entry.resolution = resolution;
}

function acceptAll(locale, resolution) {
    for (const entry of entriesByLocale.value[locale] ?? []) {
        if (entry.kind === MergeKind.Conflict) entry.resolution = resolution;
    }
}

const totalUnresolved = computed(() =>
    props.locales.reduce(
        (sum, l) => sum + countUnresolved(entriesByLocale.value[l] ?? []),
        0,
    ),
);

function summaryFor(locale) {
    return summarize(entriesByLocale.value[locale] ?? []);
}

function conflictBadgeFor(locale) {
    return countConflicts(entriesByLocale.value[locale] ?? []);
}

function unresolvedBadgeFor(locale) {
    return countUnresolved(entriesByLocale.value[locale] ?? []);
}

const visibleEntries = computed(() => {
    const entries = entriesByLocale.value[activeLocale.value] ?? [];
    if (showUnchanged.value) return entries;
    return entries.filter((e) => e.kind !== MergeKind.Unchanged);
});

function apply() {
    if (totalUnresolved.value > 0) {
        toast.error(t("backend.posts.merge.unresolved_error"));
        return;
    }
    const resolved = {};
    for (const locale of props.locales) {
        resolved[locale] = applyMerge(entriesByLocale.value[locale] ?? []);
    }
    emit("apply", resolved);
}
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-50 flex flex-col bg-bg">
                <div class="flex items-center gap-3 px-6 py-3 border-b border-line bg-surface shrink-0">
                    <Merge class="w-5 h-5 text-amber-600" :stroke-width="2" />
                    <div class="flex-1">
                        <h2 class="text-sm font-semibold text-primary">{{ t("backend.posts.merge.title") }}</h2>
                        <p class="text-xs text-muted">{{ t("backend.posts.merge.subtitle") }}</p>
                    </div>
                    <span v-if="totalUnresolved" class="px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/60 text-amber-800 dark:text-amber-200">
                        {{ t("backend.posts.merge.unresolved_count", { count: totalUnresolved }) }}
                    </span>
                    <AppButton variant="ghost" size="md" v-on:click="$emit('close')">
                        <X class="w-4 h-4" :stroke-width="2" />
                        {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton variant="primary" size="md" :disabled="totalUnresolved > 0" v-on:click="apply">
                        <Save class="w-4 h-4" :stroke-width="2" />
                        {{ t("backend.posts.merge.apply") }}
                    </AppButton>
                </div>

                <div v-if="locales.length > 1" class="flex gap-1 px-6 py-2 border-b border-line bg-surface-2 shrink-0 overflow-x-auto scrollbar-thin">
                    <AppButton
                        v-for="locale in locales"
                        :key="locale"
                        variant="ghost"
                        size="none"
                        class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-md transition-colors shrink-0"
                        :class="activeLocale === locale ? 'bg-accent-600 text-white hover:bg-accent-700' : 'text-secondary hover:bg-surface-3'"
                        v-on:click="activeLocale = locale"
                    >
                        {{ t("shared.locales." + locale) }}
                        <span
                            v-if="conflictBadgeFor(locale) > 0"
                            class="px-1.5 py-0.5 rounded-full text-xs font-semibold"
                            :class="unresolvedBadgeFor(locale) > 0 ? 'bg-amber-500 text-white' : 'bg-emerald-500 text-white'"
                        >
                            {{ unresolvedBadgeFor(locale) > 0 ? unresolvedBadgeFor(locale) : conflictBadgeFor(locale) }}
                        </span>
                    </AppButton>
                </div>

                <div class="flex flex-wrap items-center gap-3 px-6 py-2 border-b border-line bg-surface shrink-0 text-xs">
                    <span class="text-muted">
                        {{ t("backend.posts.merge.summary", {
                            unchanged: summaryFor(activeLocale).unchanged,
                            auto: summaryFor(activeLocale).autoResolved,
                            conflicts: summaryFor(activeLocale).conflicts,
                        }) }}
                    </span>
                    <div class="flex-1" />
                    <AppTextLinkButton class="font-medium" v-on:click="showUnchanged = !showUnchanged">
                        {{ showUnchanged ? t("backend.posts.merge.hide_unchanged") : t("backend.posts.merge.show_unchanged") }}
                    </AppTextLinkButton>
                    <AppTextLinkButton
                        class="font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="summaryFor(activeLocale).conflicts === 0"
                        v-on:click="acceptAll(activeLocale, 'local')"
                    >
                        {{ t("backend.posts.merge.accept_all_mine") }}
                    </AppTextLinkButton>
                    <AppTextLinkButton
                        class="font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="summaryFor(activeLocale).conflicts === 0"
                        v-on:click="acceptAll(activeLocale, 'remote')"
                    >
                        {{ t("backend.posts.merge.accept_all_theirs") }}
                    </AppTextLinkButton>
                </div>

                <div class="flex-1 overflow-y-auto scrollbar-thin px-6 py-4">
                    <div v-if="visibleEntries.length === 0" class="text-center py-12 text-muted text-sm">
                        {{ t("backend.posts.merge.nothing_to_show") }}
                    </div>

                    <MergeBlockEntry
                        v-for="entry in visibleEntries"
                        :key="entry.id"
                        :entry="entry"
                        class="mb-4"
                        v-on:resolve="resolveEntry(entry, $event)"
                    />
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
