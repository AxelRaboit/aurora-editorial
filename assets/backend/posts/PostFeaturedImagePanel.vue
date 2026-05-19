<script setup>
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { ImagePlus, X } from "lucide-vue-next";
import AppOverlayIconButton from "@/shared/components/action/AppOverlayIconButton.vue";
import AppTextLinkButton from "@/shared/components/action/AppTextLinkButton.vue";
import { useImageUpload } from "@/shared/composables/http/backend/useImageUpload.js";
import { openMediaPicker } from "@shared/utils/mediaPicker.js";
import { toast } from "vue-sonner";

const { t } = useI18n();

const props = defineProps({
    mediaId: { type: Number, default: null },
    mediaUrl: { type: String, default: null },
    focalPosition: { type: String, default: "50% 50%" },
});

const emit = defineEmits([
    "update:mediaId",
    "update:mediaUrl",
    "update:focalPosition",
]);

const previewOpen = ref(false);

const {
    uploading,
    inputRef,
    uploadFromEvent,
} = useImageUpload({
    onSuccess: ({ file, media }) => {
        emit("update:mediaId", file?.id ?? null);
        emit("update:mediaUrl", file?.url ?? null);
        emit("update:focalPosition", media?.focalPositionCss ?? "50% 50%");
    },
    onError: () => toast.error(t("shared.common.error")),
});

function clear() {
    emit("update:mediaId", null);
    emit("update:mediaUrl", null);
}

async function selectFromLibrary() {
    const media = await openMediaPicker({ imagesOnly: true });
    if (!media) return;
    emit("update:mediaId", media.id);
    emit("update:mediaUrl", media.url);
    emit("update:focalPosition", media.focalPositionCss ?? "50% 50%");
}
</script>

<template>
    <div class="flex flex-col gap-2 px-1">
        <span class="text-xs text-muted uppercase tracking-wide">{{ t("backend.posts.featuredImage") }}</span>
        <div v-if="mediaUrl" class="relative group w-full h-48">
            <img
                :src="mediaUrl"
                class="w-full h-full object-cover rounded-lg border border-line cursor-zoom-in"
                :style="{ objectPosition: focalPosition }"
                :alt="t('backend.posts.featuredImage')"
                v-on:click="previewOpen = true"
            >
            <AppOverlayIconButton
                size="xs"
                class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity"
                v-on:click="clear"
            >
                <X class="w-4 h-4" :stroke-width="2.5" />
            </AppOverlayIconButton>
        </div>
        <label
            v-else
            class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-line rounded-lg cursor-pointer hover:border-accent-400 transition-colors"
            :class="uploading ? 'opacity-50 pointer-events-none' : ''"
        >
            <ImagePlus class="w-6 h-6 text-muted mb-1.5" :stroke-width="1.5" />
            <span class="text-sm text-muted">{{ uploading ? t("shared.common.loading") : t("backend.posts.addImage") }}</span>
            <input
                ref="inputRef"
                type="file"
                accept="image/*"
                class="sr-only"
                v-on:change="uploadFromEvent"
            >
        </label>
        <AppTextLinkButton size="xs" class="mt-1 w-full justify-center" v-on:click="selectFromLibrary">
            {{ t("backend.posts.selectFromLibrary") }}
        </AppTextLinkButton>

        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="previewOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm" v-on:click="previewOpen = false">
                    <img :src="mediaUrl" class="max-w-[90vw] max-h-[90vh] object-contain rounded-lg shadow-2xl" :alt="t('backend.posts.featuredImage')">
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
