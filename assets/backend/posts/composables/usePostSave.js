import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { translateServerErrors } from "@/shared/utils/validation/translateServerErrors.js";

export function usePostSave(createPath, editPath, onSuccess) {
    const { t } = useI18n();
    const { loading, request } = useRequest();
    const errors = ref({});
    const conflict = ref(false);

    async function save(postId, formData) {
        errors.value = {};
        conflict.value = false;
        const url = postId ? buildPath(editPath, { id: postId }) : createPath;
        const data = await request(url, formData);
        if (!data) return false;
        if (data.conflict) {
            conflict.value = true;
            return false;
        }
        if (data.success) {
            onSuccess(data.post);
            return true;
        }
        errors.value = translateServerErrors(t, data.errors);
        return false;
    }

    return { loading, errors, conflict, save };
}
