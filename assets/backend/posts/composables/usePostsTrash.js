import { ref } from "vue";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

export function usePostsTrash(props, removePost, setTrashedFilter) {
    const { t } = useI18n();

    const emptyingTrash = ref(false);
    const confirmEmptyTrash = ref(false);

    const { request: emptyTrashRequest } = useRequest();
    const { request: restoreRequest } = useRequest();

    async function emptyTrash() {
        if (!props.emptyTrashPath) return;
        emptyingTrash.value = true;
        try {
            const data = await emptyTrashRequest(props.emptyTrashPath);
            if (!data) return;
            if (data.success) {
                toast.success(
                    t("backend.posts.empty_trash_done", { count: data.count }),
                );
                setTrashedFilter(true);
            } else toast.error(t("shared.common.error"));
        } finally {
            emptyingTrash.value = false;
            confirmEmptyTrash.value = false;
        }
    }

    async function restorePost(post) {
        const data = await restoreRequest(
            buildPath(props.restorePath, { id: post.id }),
        );
        if (!data) return;
        if (data.success) {
            removePost(post.id);
            toast.success(t("backend.posts.restored"));
        }
    }

    return { emptyingTrash, confirmEmptyTrash, emptyTrash, restorePost };
}
