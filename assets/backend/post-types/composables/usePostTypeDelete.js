import { ref } from "vue";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

export function usePostTypeDelete(deletePath, postTypes, selectedId) {
    const { t } = useI18n();
    const { request } = useRequest();
    const deletingPostType = ref(null);

    async function confirmDeletePostType() {
        const pt = deletingPostType.value;
        if (!pt) return;
        try {
            const data = await request(buildPath(deletePath, { id: pt.id }));
            if (!data) return;
            if (!data.success) {
                toast.error(
                    data.error ? t(data.error) : t("shared.common.error"),
                );
                return;
            }
            postTypes.value = postTypes.value.filter((p) => p.id !== pt.id);
            if (selectedId.value === pt.id)
                selectedId.value = postTypes.value[0]?.id ?? null;
            toast.success(t("shared.common.deleted"));
        } finally {
            deletingPostType.value = null;
        }
    }

    return { deletingPostType, confirmDeletePostType };
}
