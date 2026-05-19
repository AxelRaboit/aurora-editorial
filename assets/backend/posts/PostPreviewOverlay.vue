<script setup>
import { ref, computed, watch, nextTick } from "vue";
import { useI18n } from "vue-i18n";
import { ExternalLink, X } from "lucide-vue-next";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useBackButtonClose } from "@/shared/composables/overlay/useBackButtonClose.js";
import { PostStatus } from "@editorial/shared/enums/postStatus.js";
import AppButton from "@/shared/components/action/AppButton.vue";

const { t } = useI18n();

const props = defineProps({
    post:        { type: Object,  default: null },
    loading:     { type: Boolean, default: false },
    locales:     { type: Array,   default: () => [] },
    previewPath: { type: String,  required: true },
});

const emit = defineEmits(["close"]);

const availableLocales = computed(() => {
    const translations = props.post?.translations ?? {};
    return props.locales.filter((locale) => translations[locale]);
});

const activeLocale = ref(availableLocales.value[0] ?? props.locales[0] ?? "fr");

watch(() => props.post, () => {
    activeLocale.value = availableLocales.value[0] ?? props.locales[0] ?? "fr";
});

const iframeSrc = computed(() => {
    if (!props.post?.id || !activeLocale.value) return null;
    return buildPath(props.previewPath, { id: props.post.id, locale: activeLocale.value });
});

const publicUrl = computed(() => {
    const post = props.post;
    if (!post || post.trashed || post.status !== PostStatus.Published) return null;
    const slug = post.translations?.[activeLocale.value]?.slug ?? post.slug;
    if (!slug || !post.postType?.slug || !activeLocale.value) return null;
    return `/${activeLocale.value}/${post.postType.slug}/${slug}`;
});

// Iframe loaded via contentWindow.replace so its navigations don't add their
// own history entries on top of the overlay's own pushed entry.
const iframeRef = ref(null);
const isOpen = computed(() => Boolean(props.post || props.loading));

function loadIframe() {
    const frame = iframeRef.value;
    const url = iframeSrc.value;
    if (!frame || !url) return;
    try {
        frame.contentWindow.location.replace(url);
    } catch {
        frame.src = url;
    }
}

watch(iframeSrc, () => nextTick(loadIframe));
watch(isOpen, (open) => { if (open) nextTick(loadIframe); });

const { requestClose } = useBackButtonClose({
    isOpen,
    onClose: () => emit("close"),
});
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
            <div v-if="isOpen" class="fixed inset-0 z-50 flex flex-col bg-bg">
                <div class="flex items-center gap-3 px-6 py-3 border-b border-line bg-surface shrink-0">
                    <span class="flex-1 text-sm font-medium text-secondary truncate">
                        {{ post?.title ?? "…" }}
                    </span>
                    <div v-if="post && availableLocales.length" class="flex gap-1">
                        <AppButton
                            v-for="locale in availableLocales"
                            :key="locale"
                            variant="ghost"
                            size="none"
                            class="px-2.5 py-1 text-xs font-medium rounded transition-colors"
                            :class="activeLocale === locale
                                ? 'bg-accent-600 text-white hover:bg-accent-700'
                                : 'text-secondary hover:bg-surface-2'"
                            v-on:click="activeLocale = locale"
                        >
                            {{ t("shared.locales." + locale) }}
                        </AppButton>
                    </div>
                    <a
                        v-if="publicUrl"
                        :href="publicUrl"
                        target="_blank"
                        rel="noopener"
                        :title="t('shared.common.view')"
                        class="p-1.5 text-secondary hover:text-primary hover:bg-surface-2 rounded transition-colors"
                    >
                        <ExternalLink class="w-5 h-5" :stroke-width="2" />
                    </a>
                    <AppButton variant="ghost" size="none" class="p-1.5" v-on:click="requestClose">
                        <X class="w-5 h-5" :stroke-width="2" />
                    </AppButton>
                </div>

                <div class="flex-1 min-h-0">
                    <div v-if="loading" class="h-full flex items-center justify-center text-secondary text-sm">
                        {{ t("shared.common.loading") }}
                    </div>
                    <iframe
                        v-else
                        ref="iframeRef"
                        src="about:blank"
                        class="w-full h-full border-0 bg-white"
                        :title="post?.title ?? 'Preview'"
                    />
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
