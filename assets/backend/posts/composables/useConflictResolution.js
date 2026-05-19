import { ref, unref } from "vue";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

/**
 * Manages optimistic-locking state for an edited post: tracks the version
 * loaded from the server, a snapshot of translations at load time (the merge
 * "base"), and the fetching of the remote version for preview or 3-way merge.
 *
 * Returns reactive state and action handlers for the conflict banner / merge
 * overlay wired in PostEditor.vue.
 *
 * @param {{ showPath: import("vue").MaybeRef<string>, postId: import("vue").MaybeRef<number|null> }} config
 */
export function useConflictResolution({ showPath, postId }) {
    const version = ref(null);
    const baseTranslations = ref({});

    const remotePost = ref(null);
    const remoteLoading = ref(false);
    const showMerge = ref(false);
    const mergeRemoteTranslations = ref(null);

    const { request } = useRequest();

    function resolvePath() {
        const id = unref(postId);
        if (id === null || id === undefined) return null;
        return buildPath(unref(showPath), { id });
    }

    function snapshotBase(translations) {
        baseTranslations.value = JSON.parse(JSON.stringify(translations));
    }

    async function fetchRemotePost() {
        const url = resolvePath();
        if (!url) return null;
        remoteLoading.value = true;
        try {
            const data = await request(url, null, {
                method: HttpMethod.Get,
                noGuard: true,
            });
            if (data?.success) return data.post;
        } finally {
            remoteLoading.value = false;
        }
        return null;
    }

    async function openRemoteVersion() {
        const post = await fetchRemotePost();
        if (post) remotePost.value = post;
    }

    async function openMerge() {
        const post = await fetchRemotePost();
        if (!post) return;
        mergeRemoteTranslations.value = post.translations ?? {};
        showMerge.value = true;
    }

    function closeRemoteVersion() {
        remotePost.value = null;
    }

    function closeMerge() {
        showMerge.value = false;
    }

    return {
        version,
        baseTranslations,
        remotePost,
        remoteLoading,
        showMerge,
        mergeRemoteTranslations,
        snapshotBase,
        openRemoteVersion,
        closeRemoteVersion,
        openMerge,
        closeMerge,
    };
}
