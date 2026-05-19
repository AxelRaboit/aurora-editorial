import { ref } from "vue";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

export function useTaxonomyDelete(deletePath, taxonomies, selectedId) {
    const { t } = useI18n();
    const { request } = useRequest();
    const deletingTaxonomy = ref(null);

    async function confirmDeleteTaxonomy() {
        const taxonomy = deletingTaxonomy.value;
        if (!taxonomy) return;
        try {
            const data = await request(
                buildPath(deletePath, { id: taxonomy.id }),
            );
            if (!data) return;
            if (!data.success) {
                toast.error(data.error ?? t("shared.common.error"));
                return;
            }
            taxonomies.value = taxonomies.value.filter(
                (tx) => tx.id !== taxonomy.id,
            );
            if (selectedId.value === taxonomy.id)
                selectedId.value = taxonomies.value[0]?.id ?? null;
            toast.success(t("shared.common.deleted"));
        } finally {
            deletingTaxonomy.value = null;
        }
    }

    return { deletingTaxonomy, confirmDeleteTaxonomy };
}
