<script setup>
import "./prose.css";

import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { Check } from "lucide-vue-next";
import { renderBlocks } from "@/shared/utils/editor/blocksRenderer.js";
import { MergeKind } from "@/shared/utils/editor/mergeBlocks.js";
import AppButton from "@/shared/components/action/AppButton.vue";

const { t } = useI18n();

const props = defineProps({
    entry: { type: Object, required: true },
});

const emit = defineEmits(["resolve"]);

const KIND_CLASS = {
    [MergeKind.Unchanged]: "bg-surface-2 text-muted",
    [MergeKind.LocalModified]: "bg-accent-100 dark:bg-accent-950/50 text-accent-700 dark:text-accent-300",
    [MergeKind.RemoteModified]: "bg-sky-100 dark:bg-sky-950/50 text-sky-700 dark:text-sky-300",
    [MergeKind.LocalAdded]: "bg-emerald-100 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300",
    [MergeKind.RemoteAdded]: "bg-cyan-100 dark:bg-cyan-950/50 text-cyan-700 dark:text-cyan-300",
    [MergeKind.LocalRemoved]: "bg-rose-100 dark:bg-rose-950/50 text-rose-700 dark:text-rose-300",
    [MergeKind.RemoteRemoved]: "bg-orange-100 dark:bg-orange-950/50 text-orange-700 dark:text-orange-300",
    [MergeKind.Conflict]: "bg-amber-200 dark:bg-amber-900/60 text-amber-900 dark:text-amber-200",
};

const isConflict = computed(() => props.entry.kind === MergeKind.Conflict);
const kindLabel = computed(() => t(`backend.posts.merge.kind.${props.entry.kind}`));
const kindClass = computed(() => KIND_CLASS[props.entry.kind] ?? "bg-surface-2 text-muted");
const blockType = computed(() => (props.entry.local ?? props.entry.remote ?? props.entry.base)?.type ?? "-");
const shortId = computed(() => props.entry.id.slice(0, 8));
const canFlip = computed(
    () => props.entry.kind !== MergeKind.Unchanged && props.entry.local && props.entry.remote,
);
const resolvedBlock = computed(() => {
    if (props.entry.resolution === "local") return props.entry.local;
    if (props.entry.resolution === "remote") return props.entry.remote;
    return null;
});

function renderBlock(block) {
    if (!block) return "";
    return renderBlocks([block]);
}
</script>

<template>
    <div>
        <div class="flex items-center gap-2 mb-1.5 text-xs">
            <span class="px-2 py-0.5 rounded-md font-medium" :class="kindClass">{{ kindLabel }}</span>
            <span class="text-muted font-mono">#{{ shortId }}</span>
            <span class="text-muted">·</span>
            <span class="text-secondary">{{ blockType }}</span>
        </div>

        <div v-if="isConflict" class="grid grid-cols-1 md:grid-cols-2 gap-2">
            <div
                class="border rounded-lg overflow-hidden cursor-pointer transition-all"
                :class="entry.resolution === 'local'
                    ? 'border-accent-500 ring-2 ring-accent-500/30'
                    : 'border-line hover:border-accent-300'"
                v-on:click="emit('resolve', 'local')"
            >
                <div class="flex items-center justify-between px-3 py-1.5 text-xs font-medium bg-surface-2 border-b border-line">
                    <span class="text-accent-600 dark:text-accent-400">{{ t("backend.posts.merge.yours") }}</span>
                    <Check v-if="entry.resolution === 'local'" class="w-3.5 h-3.5 text-accent-600" :stroke-width="2.5" />
                </div>
                <div class="p-3 prose-preview text-sm" v-html="renderBlock(entry.local) || `<em class='text-muted'>${t('backend.posts.merge.deleted')}</em>`" />
            </div>

            <div
                class="border rounded-lg overflow-hidden cursor-pointer transition-all"
                :class="entry.resolution === 'remote'
                    ? 'border-sky-500 ring-2 ring-sky-500/30'
                    : 'border-line hover:border-sky-300'"
                v-on:click="emit('resolve', 'remote')"
            >
                <div class="flex items-center justify-between px-3 py-1.5 text-xs font-medium bg-surface-2 border-b border-line">
                    <span class="text-sky-600 dark:text-sky-400">{{ t("backend.posts.merge.theirs") }}</span>
                    <Check v-if="entry.resolution === 'remote'" class="w-3.5 h-3.5 text-sky-600" :stroke-width="2.5" />
                </div>
                <div class="p-3 prose-preview text-sm" v-html="renderBlock(entry.remote) || `<em class='text-muted'>${t('backend.posts.merge.deleted')}</em>`" />
            </div>
        </div>

        <div v-else class="border border-line rounded-lg overflow-hidden">
            <div class="p-3 prose-preview text-sm bg-surface">
                <div v-if="resolvedBlock" v-html="renderBlock(resolvedBlock)" />
                <em v-else class="text-muted text-xs">{{ t("backend.posts.merge.deleted") }}</em>
            </div>
            <div v-if="canFlip" class="flex items-center justify-end gap-2 px-3 py-1.5 bg-surface-2 border-t border-line text-xs">
                <span class="text-muted">{{ t("backend.posts.merge.use") }}</span>
                <AppButton
                    variant="ghost"
                    size="sm"
                    class="font-medium"
                    :class="entry.resolution === 'local' ? 'bg-accent-600 text-white hover:bg-accent-700' : 'text-accent-600 hover:bg-accent-50 dark:hover:bg-accent-950/50'"
                    v-on:click="emit('resolve', 'local')"
                >
                    {{ t("backend.posts.merge.yours") }}
                </AppButton>
                <AppButton
                    variant="ghost"
                    size="sm"
                    class="font-medium"
                    :class="entry.resolution === 'remote' ? 'bg-sky-600 text-white hover:bg-sky-700' : 'text-sky-600 hover:bg-sky-50 dark:hover:bg-sky-950/50'"
                    v-on:click="emit('resolve', 'remote')"
                >
                    {{ t("backend.posts.merge.theirs") }}
                </AppButton>
            </div>
        </div>
    </div>
</template>
