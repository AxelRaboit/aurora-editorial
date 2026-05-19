<script setup>
import { useI18n } from "vue-i18n";
import { Send, X } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppInput from "@/shared/components/form/input/AppInput.vue";
import AppTextarea from "@/shared/components/form/input/AppTextarea.vue";

const { t } = useI18n();

defineProps({
    parentId: { type: Number, default: null },
    submitting: { type: Boolean, default: false },
    errors: { type: Object, default: () => ({}) },
    authorName: { type: String, default: "" },
    authorEmail: { type: String, default: "" },
    content: { type: String, default: "" },
});

defineEmits(["update:authorName", "update:authorEmail", "update:content", "submit", "cancel"]);
</script>

<template>
    <form class="space-y-3" v-on:submit.prevent="$emit('submit')">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <AppInput
                type="text"
                :model-value="authorName"
                :label="t('shared.comment.name')"
                :required="true"
                :error="errors.authorName ?? ''"
                v-on:update:model-value="$emit('update:authorName', $event)"
            />
            <AppInput
                type="email"
                :model-value="authorEmail"
                :label="t('shared.comment.email')"
                :required="true"
                :error="errors.authorEmail ?? ''"
                v-on:update:model-value="$emit('update:authorEmail', $event)"
            />
        </div>
        <AppTextarea
            :model-value="content"
            :label="t('shared.comment.content')"
            :required="true"
            :rows="3"
            :maxlength="2000"
            :error="errors.content ?? ''"
            v-on:update:model-value="$emit('update:content', $event)"
        />
        <div class="flex items-center gap-2">
            <AppButton type="submit" variant="primary" size="md" :loading="submitting">
                <Send class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.comment.submit") }}
            </AppButton>
            <AppButton
                v-if="parentId !== null"
                type="button"
                variant="ghost"
                size="md"
                v-on:click="$emit('cancel')"
            >
                <X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}
            </AppButton>
        </div>
    </form>
</template>
