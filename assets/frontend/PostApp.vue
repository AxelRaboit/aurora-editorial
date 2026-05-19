<script setup>
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import AppImage from "@/shared/components/display/AppImage.vue";
import PostCommentsApp from "./PostCommentsApp.vue";

const { formatDateShort } = useDateFormat();

defineProps({
    post: { type: Object, required: true },
    translation: { type: Object, required: true },
    featuredMedia: { type: Object, default: null },
    content: { type: String, default: "" },
    commentsEnabled: { type: Boolean, default: false },
    listPath: { type: [String, null], default: null },
    submitPath: { type: [String, null], default: null },
    reactPathTemplate: { type: [String, null], default: null },
    locale: { type: String, default: "fr" },
});
</script>

<template>
    <article class="max-w-3xl mx-auto">
        <header class="mb-8">
            <h1 class="text-4xl font-bold text-primary mb-2">{{ translation.title }}</h1>
            <time
                v-if="post.publishedAt"
                :datetime="post.publishedAt"
                class="text-sm text-muted"
            >
                {{ formatDateShort(post.publishedAt) }}
            </time>
            <figure v-if="featuredMedia" class="mt-6">
                <AppImage
                    :src="featuredMedia.url"
                    :alt="featuredMedia.alt ?? translation.title ?? ''"
                    class="w-full h-auto rounded-lg"
                    :style="featuredMedia.focalPosition ? `object-position: ${featuredMedia.focalPosition}` : ''"
                    loading="lazy"
                />
            </figure>
        </header>

        <div
            class="prose prose-lg dark:prose-invert max-w-none prose-headings:text-primary prose-a:text-accent hover:prose-a:text-accent-hover prose-a:no-underline prose-code:text-primary prose-pre:bg-surface-2 prose-pre:border prose-pre:border-line prose-blockquote:border-accent prose-blockquote:bg-surface-2 prose-blockquote:not-italic prose-img:rounded-lg prose-hr:border-line"
            v-html="content"
        />
    </article>

    <PostCommentsApp
        v-if="translation.slug && listPath && submitPath && reactPathTemplate"
        :list-path="listPath"
        :submit-path="submitPath"
        :react-path-template="reactPathTemplate"
        :comments-enabled="commentsEnabled"
    />
</template>
