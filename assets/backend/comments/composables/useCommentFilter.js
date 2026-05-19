import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { CommentStatus } from "@editorial/shared/enums/commentStatus.js";

export const COMMENT_STATUS_BADGE = {
    [CommentStatus.Approved]: "emerald",
    [CommentStatus.Spam]: "rose",
};

export function useCommentFilter(localStats, resetComments) {
    const { t } = useI18n();

    const statusFilter = ref("");
    const viewingComment = ref(null);

    const tabs = computed(() => [
        {
            key: "",
            label: t("backend.comments.all"),
            count:
                localStats.value.pending +
                localStats.value.approved +
                localStats.value.spam,
        },
        {
            key: CommentStatus.Pending,
            label: t("backend.comments.pending"),
            count: localStats.value.pending,
        },
        {
            key: CommentStatus.Approved,
            label: t("backend.comments.approved"),
            count: localStats.value.approved,
        },
        {
            key: CommentStatus.Spam,
            label: t("backend.comments.spam"),
            count: localStats.value.spam,
        },
    ]);

    function selectTab(key) {
        statusFilter.value = key;
        resetComments();
    }

    function statusBadgeColor(status) {
        return COMMENT_STATUS_BADGE[status] ?? "amber";
    }

    return { statusFilter, viewingComment, tabs, selectTab, statusBadgeColor };
}
