import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";
import { CommentStatus } from "@editorial/shared/enums/commentStatus.js";

export function useCommentModeration(paths, initialStats, onRefresh) {
    const { t } = useI18n();
    const { request } = useRequest();

    const localStats = ref({ ...initialStats });
    const isModerationEnabled = ref(paths.moderationEnabled);
    const pendingToggleModeration = ref(false);
    const toggleModerationLoading = ref(false);

    const pendingSpam = ref(null);
    const spamLoading = ref(false);

    const pendingDelete = ref(null);
    const deleteLoading = ref(false);

    async function moderateComment(comment, path, successKey, statsUpdate) {
        const data = await request(buildPath(path, { id: comment.id }));
        if (!data) return;
        if (data.success) {
            toast.success(t(successKey));
            statsUpdate(comment.status);
            onRefresh();
        } else {
            toast.error(t("shared.common.error"));
        }
    }

    function approveComment(comment) {
        return moderateComment(
            comment,
            paths.approve,
            "backend.comments.approve_success",
            (status) => {
                if (status === CommentStatus.Pending) {
                    localStats.value.pending = Math.max(
                        0,
                        localStats.value.pending - 1,
                    );
                    localStats.value.approved += 1;
                } else if (status === CommentStatus.Spam) {
                    localStats.value.spam = Math.max(
                        0,
                        localStats.value.spam - 1,
                    );
                    localStats.value.approved += 1;
                }
            },
        );
    }

    function spamComment(comment) {
        return moderateComment(
            comment,
            paths.spam,
            "backend.comments.spam_success",
            (status) => {
                if (status === CommentStatus.Pending) {
                    localStats.value.pending = Math.max(
                        0,
                        localStats.value.pending - 1,
                    );
                    localStats.value.spam += 1;
                } else if (status === CommentStatus.Approved) {
                    localStats.value.approved = Math.max(
                        0,
                        localStats.value.approved - 1,
                    );
                    localStats.value.spam += 1;
                }
            },
        );
    }

    function confirmSpam(comment) {
        pendingSpam.value = comment;
    }

    async function doSpam() {
        if (!pendingSpam.value || spamLoading.value) return;
        spamLoading.value = true;
        const comment = pendingSpam.value;
        try {
            await spamComment(comment);
            pendingSpam.value = null;
        } finally {
            spamLoading.value = false;
        }
    }

    function confirmDelete(comment) {
        pendingDelete.value = comment;
    }

    async function doDelete() {
        if (!pendingDelete.value || deleteLoading.value) return;
        deleteLoading.value = true;
        const comment = pendingDelete.value;
        const data = await request(buildPath(paths.delete, { id: comment.id }));
        deleteLoading.value = false;
        if (!data) return;
        if (data.success) {
            toast.success(t("shared.common.deleted"));
            if (comment.status === CommentStatus.Pending)
                localStats.value.pending = Math.max(
                    0,
                    localStats.value.pending - 1,
                );
            else if (comment.status === CommentStatus.Approved)
                localStats.value.approved = Math.max(
                    0,
                    localStats.value.approved - 1,
                );
            else if (comment.status === CommentStatus.Spam)
                localStats.value.spam = Math.max(0, localStats.value.spam - 1);
            pendingDelete.value = null;
            onRefresh();
        } else {
            toast.error(t("shared.common.error"));
        }
    }

    async function doToggleModeration() {
        toggleModerationLoading.value = true;
        const data = await request(paths.toggleModeration);
        toggleModerationLoading.value = false;
        if (!data) return;
        if (data.success) {
            isModerationEnabled.value = data.moderationEnabled;
            toast.success(
                data.moderationEnabled
                    ? t("backend.comments.moderation_enabled")
                    : t("backend.comments.moderation_disabled"),
            );
            pendingToggleModeration.value = false;
        } else {
            toast.error(t("shared.common.error"));
        }
    }

    return {
        localStats,
        isModerationEnabled,
        pendingToggleModeration,
        toggleModerationLoading,
        pendingSpam,
        spamLoading,
        pendingDelete,
        deleteLoading,
        approveComment,
        confirmSpam,
        doSpam,
        confirmDelete,
        doDelete,
        doToggleModeration,
    };
}
