import { ref } from "vue";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";

export function usePostsPreview(showPath, locales) {
    const { request } = useRequest();
    const previewPost = ref(null);
    const previewLoading = ref(false);

    function frontUrl(post) {
        const locale = locales[0] ?? "fr";
        if (!post.slug || !post.postType?.slug) return null;
        return `/${locale}/${post.postType.slug}/${post.slug}`;
    }

    async function openPreview(post) {
        previewLoading.value = true;
        previewPost.value = null;
        try {
            const data = await request(
                buildPath(showPath, { id: post.id }),
                null,
                HttpMethod.Get,
            );
            if (data?.success) previewPost.value = data.post;
        } finally {
            previewLoading.value = false;
        }
    }

    return { previewPost, previewLoading, frontUrl, openPreview };
}
