<script setup>
import { computed, ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import { X, Check } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import { TEMPLATES } from "@editorial/shared/editorjs/templates.js";
import { TEMPLATE_CATEGORIES } from "@editorial/shared/editorjs/blockTypes.js";
import { BlockType } from "@editorial/shared/editorjs/blockType.js";

const { t } = useI18n();

const props = defineProps({
    show: { type: Boolean, default: false },
});

const emit = defineEmits(["close", "apply"]);

const activeCategory = ref("all");
const confirmingTemplate = ref(null);
const hoveredTemplate = ref(null);

watch(
    () => props.show,
    (visible) => {
        if (!visible) {
            confirmingTemplate.value = null;
            hoveredTemplate.value = null;
        }
    },
);

const filteredTemplates = computed(() =>
    activeCategory.value === "all"
        ? TEMPLATES
        : TEMPLATES.filter((tpl) => tpl.category === activeCategory.value),
);

function close() {
    confirmingTemplate.value = null;
    hoveredTemplate.value = null;
    emit("close");
}

function apply(template) {
    emit("apply", template);
    close();
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
            <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                <div class="w-full max-w-4xl bg-surface rounded-2xl border border-line shadow-2xl flex flex-col max-h-[85vh]">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-line shrink-0">
                        <div>
                            <h2 class="text-base font-semibold text-primary">{{ t("backend.editor.templates.title") }}</h2>
                            <p class="text-xs text-muted mt-0.5">{{ t("backend.editor.templates.subtitle") }}</p>
                        </div>
                        <AppButton variant="ghost" size="none" class="p-1.5" v-on:click="close">
                            <X class="w-5 h-5" :stroke-width="2" />
                        </AppButton>
                    </div>

                    <div class="flex gap-2 px-6 py-3 border-b border-line shrink-0 flex-wrap">
                        <button
                            v-for="cat in ['all', ...TEMPLATE_CATEGORIES]"
                            :key="cat"
                            type="button"
                            class="px-3 py-1 rounded-full text-xs font-medium transition-colors"
                            :class="activeCategory === cat ? 'bg-accent-600 text-white' : 'bg-surface-2 text-secondary hover:bg-surface-3'"
                            v-on:click="activeCategory = cat; confirmingTemplate = null"
                        >
                            {{ t("backend.editor.templates.categories." + cat) }}
                        </button>
                    </div>

                    <div class="flex flex-1 min-h-0" v-on:mouseleave="hoveredTemplate = null">
                        <div class="flex-1 overflow-y-auto scrollbar-thin p-6 grid grid-cols-1 sm:grid-cols-2 gap-3 content-start">
                            <button
                                v-for="template in filteredTemplates"
                                :key="template.id"
                                type="button"
                                class="relative text-left p-4 rounded-xl border transition-all group overflow-hidden"
                                :class="confirmingTemplate?.id === template.id
                                    ? 'border-accent-500 bg-accent-500/10'
                                    : 'border-line bg-surface-2 hover:border-accent-500 hover:bg-accent-500/5'"
                                v-on:click="confirmingTemplate = confirmingTemplate?.id === template.id ? null : template"
                                v-on:mouseenter="hoveredTemplate = template"
                            >
                                <Transition
                                    enter-active-class="transition ease-out duration-150"
                                    enter-from-class="opacity-0"
                                    enter-to-class="opacity-100"
                                    leave-active-class="transition ease-in duration-100"
                                    leave-from-class="opacity-100"
                                    leave-to-class="opacity-0"
                                >
                                    <div v-if="confirmingTemplate?.id === template.id" class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-surface/95 backdrop-blur-sm rounded-xl p-4">
                                        <p class="text-sm font-medium text-primary text-center">{{ t("backend.editor.templates.confirm_replace") }}</p>
                                        <div class="flex gap-2">
                                            <AppButton variant="primary" size="md" v-on:click.stop="apply(template)"><Check class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("backend.editor.templates.apply") }}</AppButton>
                                            <AppButton variant="ghost" size="md" v-on:click.stop="confirmingTemplate = null"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                                        </div>
                                    </div>
                                </Transition>

                                <div class="flex items-center gap-3 mb-2">
                                    <span class="text-2xl">{{ template.icon }}</span>
                                    <span class="font-medium text-primary text-sm group-hover:text-accent-400 transition-colors">{{ t("backend.editor.templates." + template.id + ".label") }}</span>
                                </div>
                                <p class="text-xs text-muted">{{ t("backend.editor.templates." + template.id + ".description") }}</p>
                            </button>
                        </div>

                        <div class="w-56 border-l border-line p-5 hidden md:flex flex-col gap-4 shrink-0 overflow-y-auto scrollbar-thin">
                            <Transition
                                enter-active-class="transition ease-out duration-150"
                                enter-from-class="opacity-0"
                                enter-to-class="opacity-100"
                                leave-active-class="transition ease-in duration-100"
                                leave-from-class="opacity-100"
                                leave-to-class="opacity-0"
                                mode="out-in"
                            >
                                <div v-if="hoveredTemplate" :key="hoveredTemplate.id" class="flex flex-col gap-4">
                                    <div>
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-xl">{{ hoveredTemplate.icon }}</span>
                                            <span class="font-semibold text-sm text-primary">{{ t("backend.editor.templates." + hoveredTemplate.id + ".label") }}</span>
                                        </div>
                                        <span class="inline-block px-2 py-0.5 rounded-full text-xs bg-surface-3 text-secondary">
                                            {{ t("backend.editor.templates.categories." + hoveredTemplate.category) }}
                                        </span>
                                    </div>
                                    <div v-if="hoveredTemplate.blocks.length" class="flex flex-col gap-1.5">
                                        <p class="text-xs text-muted uppercase tracking-wide mb-1">{{ t("backend.editor.templates.structure") }}</p>
                                        <template v-for="(block, i) in hoveredTemplate.blocks" :key="i">
                                            <div v-if="block.type === BlockType.Header" class="h-2.5 rounded-sm bg-surface-3 w-3/4" />
                                            <div v-else-if="block.type === BlockType.Paragraph" class="flex flex-col gap-1">
                                                <div class="h-1.5 rounded-sm bg-surface-3 w-full" />
                                                <div class="h-1.5 rounded-sm bg-surface-3 w-5/6" />
                                                <div class="h-1.5 rounded-sm bg-surface-3 w-4/6" />
                                            </div>
                                            <div v-else-if="block.type === BlockType.Image" class="h-10 rounded-sm bg-surface-3 w-full flex items-center justify-center">
                                                <svg
                                                    class="w-4 h-4 text-muted/50"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="1.5"
                                                ><rect
                                                    x="3"
                                                    y="3"
                                                    width="18"
                                                    height="18"
                                                    rx="2"
                                                /><circle cx="8.5" cy="8.5" r="1.5" /><path d="m21 15-5-5L5 21" /></svg>
                                            </div>
                                            <div v-else-if="block.type === BlockType.MediaText" class="flex gap-1.5">
                                                <div class="h-8 rounded-sm bg-surface-3 w-2/5 shrink-0" />
                                                <div class="flex flex-col gap-1 flex-1 justify-center">
                                                    <div class="h-1.5 rounded-sm bg-surface-3 w-full" />
                                                    <div class="h-1.5 rounded-sm bg-surface-3 w-4/5" />
                                                </div>
                                            </div>
                                            <div v-else-if="block.type === BlockType.TwoColumn" class="flex gap-1.5">
                                                <div class="flex flex-col gap-1 flex-1">
                                                    <div class="h-1.5 rounded-sm bg-surface-3 w-full" />
                                                    <div class="h-1.5 rounded-sm bg-surface-3 w-4/5" />
                                                </div>
                                                <div class="flex flex-col gap-1 flex-1">
                                                    <div class="h-1.5 rounded-sm bg-surface-3 w-full" />
                                                    <div class="h-1.5 rounded-sm bg-surface-3 w-3/5" />
                                                </div>
                                            </div>
                                            <div v-else-if="block.type === BlockType.List" class="flex flex-col gap-1">
                                                <div v-for="n in 3" :key="n" class="flex items-center gap-1">
                                                    <div class="w-1 h-1 rounded-full bg-surface-3 shrink-0" />
                                                    <div class="h-1.5 rounded-sm bg-surface-3" :class="n === 1 ? 'w-4/5' : n === 2 ? 'w-3/5' : 'w-2/3'" />
                                                </div>
                                            </div>
                                            <div v-else-if="block.type === BlockType.Code" class="h-8 rounded-sm bg-surface-3 w-full px-2 flex flex-col justify-center gap-1">
                                                <div class="h-1 rounded-sm bg-muted/20 w-2/3" />
                                                <div class="h-1 rounded-sm bg-muted/20 w-1/2" />
                                            </div>
                                            <div v-else-if="block.type === BlockType.Callout" class="h-7 rounded-sm bg-surface-3 w-full border-l-2 border-muted/30 pl-2 flex flex-col justify-center gap-1">
                                                <div class="h-1.5 rounded-sm bg-muted/30 w-3/4" />
                                                <div class="h-1 rounded-sm bg-muted/20 w-1/2" />
                                            </div>
                                            <div v-else-if="block.type === BlockType.Delimiter" class="flex items-center gap-1.5 py-0.5">
                                                <div class="h-px flex-1 bg-surface-3" />
                                                <div class="w-1 h-1 rounded-full bg-surface-3" />
                                                <div class="h-px flex-1 bg-surface-3" />
                                            </div>
                                            <div v-else class="h-2 rounded-sm bg-surface-3 w-2/3" />
                                        </template>
                                    </div>
                                    <p v-else class="text-xs text-muted italic">{{ t("backend.editor.templates.empty_content") }}</p>
                                    <p class="text-xs text-muted mt-auto pt-2 border-t border-line">
                                        {{ hoveredTemplate.blocks.length }}
                                        {{ hoveredTemplate.blocks.length > 1 ? t("backend.editor.templates.blocks") : t("backend.editor.templates.block") }}
                                    </p>
                                </div>
                                <p v-else key="empty" class="text-xs text-muted italic">{{ t("backend.editor.templates.subtitle") }}</p>
                            </Transition>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
