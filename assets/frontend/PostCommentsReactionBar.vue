<script setup>
import { useI18n } from "vue-i18n";
import AppButton from "@/shared/components/action/AppButton.vue";

const { t } = useI18n();

defineProps({
    comment: { type: Object, required: true },
    reactionEmojis: { type: Object, default: () => ({}) },
});

defineEmits(["react"]);

const btnClass = "inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-surface-2 hover:bg-surface-3 border border-line transition-colors text-primary";
</script>

<template>
    <div class="flex items-center gap-1 mt-3 flex-wrap">
        <template v-for="(emoji, type) in reactionEmojis" :key="type">
            <AppButton
                v-if="(comment.reactionCounts?.[type] ?? 0) > 0"
                type="button"
                variant="ghost"
                size="none"
                :class="btnClass"
                v-on:click="$emit('react', comment.id, type)"
            >
                {{ emoji }} {{ comment.reactionCounts[type] }}
            </AppButton>
        </template>

        <div class="relative group">
            <AppButton type="button" variant="ghost" size="none" :class="btnClass + ' text-muted hover:text-primary'">
                ＋ {{ t("shared.comment.react") }}
            </AppButton>
            <div class="hidden group-hover:flex absolute left-0 top-full mt-1 z-10 gap-1 p-2 bg-surface border border-line rounded-xl shadow-lg">
                <AppButton
                    v-for="(emoji, type) in reactionEmojis"
                    :key="type"
                    type="button"
                    variant="ghost"
                    size="none"
                    :class="'text-lg hover:scale-125 transition-transform p-1'"
                    v-on:click="$emit('react', comment.id, type)"
                >
                    {{ emoji }}
                </AppButton>
            </div>
        </div>
    </div>
</template>
