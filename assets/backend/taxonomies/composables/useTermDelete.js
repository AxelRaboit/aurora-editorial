import { ref } from "vue";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";

export function useTermDelete(termDeletePath, selected, replaceTaxonomy) {
    const { t } = useI18n();
    const deletingTerm = ref(null);

    async function confirmDeleteTerm() {
        const term = deletingTerm.value;
        if (!term || !selected.value) return;
        try {
            const url = buildPath(termDeletePath, {
                id: selected.value.id,
                termId: term.id,
            });
            const response = await fetch(url, { method: HttpMethod.Post });
            const data = await response.json();
            if (!data.success) {
                toast.error(t("shared.common.error"));
                return;
            }
            replaceTaxonomy(data.taxonomy);
            toast.success(t("shared.common.deleted"));
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            deletingTerm.value = null;
        }
    }

    return { deletingTerm, confirmDeleteTerm };
}
