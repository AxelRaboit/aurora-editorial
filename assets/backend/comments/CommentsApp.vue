<script setup>
import { onMounted } from "vue";
import { usePaginatedFetch } from "@/shared/composables/http/backend/usePaginatedFetch.js";
import { useI18n } from "vue-i18n";
import { useCommentModeration } from "@editorial/backend/comments/composables/useCommentModeration.js";
import { useCommentFilter } from "@editorial/backend/comments/composables/useCommentFilter.js";
import { CommentStatus } from "@editorial/shared/enums/commentStatus.js";
import { MessageSquare, Check, Ban, Trash2, Eye, X } from "lucide-vue-next";
import AppLoader from "@/shared/components/feedback/AppLoader.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppTab from "@/shared/components/nav/AppTab.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import { truncate } from "@/shared/utils/format/truncate.js";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import { usePrivileges } from "@/shared/composables/usePrivileges.js";

const { t } = useI18n();
const { can } = usePrivileges();
const { formatDateShort, formatDateTime } = useDateFormat();

const props = defineProps({
    listPath: { type: String, required: true },
    approvePath: { type: String, required: true },
    spamPath: { type: String, required: true },
    toggleModerationPath: { type: String, required: true },
    moderationEnabled: { type: Boolean, default: true },
    deletePath: { type: String, required: true },
    stats: { type: Object, default: () => ({ pending: 0, approved: 0, spam: 0 }) },
});


const { items: comments, loading, page, totalPages, total, load: fetchComments, goToPage, reset: resetComments } = usePaginatedFetch(
    () => props.listPath,
    () => ({ ...(statusFilter.value && { status: statusFilter.value }) }),
);

onMounted(fetchComments);

const {
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
} = useCommentModeration(
    { approve: props.approvePath, spam: props.spamPath, delete: props.deletePath, toggleModeration: props.toggleModerationPath, moderationEnabled: props.moderationEnabled },
    props.stats,
    fetchComments,
);

const { statusFilter, viewingComment, tabs, selectTab, statusBadgeColor } = useCommentFilter(localStats, resetComments);
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="flex gap-1 flex-wrap">
                <AppTab
                    v-for="tab in tabs"
                    :key="tab.key"
                    size="sm"
                    :active="statusFilter === tab.key"
                    v-on:click="selectTab(tab.key)"
                >
                    {{ tab.label }}
                    <span class="inline-flex items-center justify-center min-w-5 h-5 px-1 rounded-full text-xs" :class="statusFilter === tab.key ? 'bg-accent-600/25' : 'bg-surface-3'">
                        {{ tab.count }}
                    </span>
                </AppTab>
            </div>
            <AppButton
                :variant="isModerationEnabled ? 'primary' : 'secondary'"
                size="md"
                v-on:click="pendingToggleModeration = true"
            >
                <span class="w-2 h-2 rounded-full" :class="isModerationEnabled ? 'bg-white' : 'bg-muted'" />
                {{ isModerationEnabled ? t("backend.comments.moderationOn") : t("backend.comments.moderationOff") }}
            </AppButton>
        </div>

        <div class="relative space-y-4">
            <div class="sm:hidden space-y-2">
                <AppNoData v-if="!loading && !comments.length" :message="t('backend.comments.empty')" />
                <div v-for="comment in comments" :key="comment.id" class="bg-surface border border-line/60 rounded-xl p-4 space-y-3 shadow-sm">
                    <div class="flex items-start gap-3">
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-primary text-sm">
                                {{ comment.authorName }}
                                <span v-if="comment.replyCount > 0" class="ml-1.5 inline-flex items-center gap-0.5 text-xs text-secondary bg-surface-3 rounded px-1 py-0.5">↩ {{ comment.replyCount }}</span>
                            </p>
                            <p class="text-xs text-muted mt-0.5">{{ comment.authorEmail }}</p>
                            <p class="text-xs text-secondary mt-1.5 line-clamp-2">{{ comment.content }}</p>
                        </div>
                        <AppBadge :color="statusBadgeColor(comment.status)" class="shrink-0">{{ comment.statusLabel }}</AppBadge>
                    </div>
                    <div class="flex items-center justify-between pt-2 border-t border-line/40">
                        <p class="text-xs text-muted">{{ formatDateShort(comment.createdAt) }}</p>
                        <div class="flex items-center gap-0.5">
                            <AppIconButton color="accent" :title="t('backend.comments.view')" v-on:click="viewingComment = comment">
                                <Eye class="w-4 h-4" :stroke-width="2" />
                            </AppIconButton>
                            <AppIconButton v-if="comment.status !== 'approved' && can('editorial.comments.moderate')" color="emerald" :title="t('backend.comments.approve')" v-on:click="approveComment(comment)">
                                <Check class="w-4 h-4" :stroke-width="2" />
                            </AppIconButton>
                            <AppIconButton v-if="comment.status !== 'spam' && can('editorial.comments.moderate')" color="amber" :title="t('backend.comments.markSpam')" v-on:click="confirmSpam(comment)">
                                <Ban class="w-4 h-4" :stroke-width="2" />
                            </AppIconButton>
                            <AppIconButton v-if="can('editorial.comments.delete')" color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(comment)">
                                <Trash2 class="w-4 h-4" :stroke-width="2" />
                            </AppIconButton>
                        </div>
                    </div>
                </div>
            </div>

            <div class="hidden sm:block bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
                <AppNoData v-if="!loading && !comments.length" :message="t('backend.comments.empty')" />
                <table v-else class="w-full text-sm">
                    <thead>
                        <tr class="bg-surface-2/50 border-b border-line/40">
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.comments.name') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t('backend.comments.email') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden lg:table-cell">{{ t('backend.comments.post') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.comments.content') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t('backend.comments.date') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.comments.status') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('shared.common.edit') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line/40">
                        <tr v-for="comment in comments" :key="comment.id" class="group hover:bg-surface-2/40 transition-colors">
                            <td class="px-6 py-3 text-primary font-medium whitespace-nowrap">
                                {{ comment.authorName }}
                                <span v-if="comment.replyCount > 0" class="ml-1.5 inline-flex items-center gap-0.5 text-xs text-secondary bg-surface-3 rounded px-1 py-0.5">↩ {{ comment.replyCount }}</span>
                            </td>
                            <td class="px-6 py-3 text-secondary text-xs hidden md:table-cell">{{ comment.authorEmail }}</td>
                            <td class="px-6 py-3 text-secondary text-xs hidden lg:table-cell">{{ truncate(comment.postTitle, 40) }}</td>
                            <td class="px-6 py-3 text-secondary max-w-xs">{{ truncate(comment.content, 100) }}</td>
                            <td class="px-6 py-3 text-xs text-muted whitespace-nowrap hidden md:table-cell">{{ formatDateShort(comment.createdAt) }}</td>
                            <td class="px-6 py-3">
                                <AppBadge :color="statusBadgeColor(comment.status)">{{ comment.statusLabel }}</AppBadge>
                            </td>
                            <td class="px-6 py-3">
                                <div class="flex items-center justify-end gap-0.5">
                                    <AppIconButton color="accent" :title="t('backend.comments.view')" v-on:click="viewingComment = comment">
                                        <Eye class="w-4 h-4" :stroke-width="2" />
                                    </AppIconButton>
                                    <AppIconButton v-if="comment.status !== 'approved' && can('editorial.comments.moderate')" color="emerald" :title="t('backend.comments.approve')" v-on:click="approveComment(comment)">
                                        <Check class="w-4 h-4" :stroke-width="2" />
                                    </AppIconButton>
                                    <AppIconButton v-if="comment.status !== 'spam' && can('editorial.comments.moderate')" color="amber" :title="t('backend.comments.markSpam')" v-on:click="confirmSpam(comment)">
                                        <Ban class="w-4 h-4" :stroke-width="2" />
                                    </AppIconButton>
                                    <AppIconButton v-if="can('editorial.comments.delete')" color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(comment)">
                                        <Trash2 class="w-4 h-4" :stroke-width="2" />
                                    </AppIconButton>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <AppPagination :page="page" :total-pages="totalPages" v-on:change="goToPage" />
            <AppLoader :active="loading" />
        </div>

        <AppModal
            :show="!!viewingComment"
            max-width="md"
            :title="t('backend.comments.view')"
            :icon="MessageSquare"
            :closeable="false"
            v-on:close="viewingComment = null"
        >
            <div class="space-y-4">
                <div class="flex flex-col gap-1.5">
                    <label class="block text-xs text-secondary uppercase tracking-wide">{{ t('backend.comments.name') }}</label>
                    <p class="text-sm text-primary font-medium">{{ viewingComment?.authorName }}</p>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="block text-xs text-secondary uppercase tracking-wide">{{ t('backend.comments.email') }}</label>
                    <p class="text-sm text-secondary">{{ viewingComment?.authorEmail }}</p>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="block text-xs text-secondary uppercase tracking-wide">{{ t('backend.comments.post') }}</label>
                    <p class="text-sm text-secondary">{{ viewingComment?.postTitle }}</p>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="block text-xs text-secondary uppercase tracking-wide">{{ t('backend.comments.date') }}</label>
                    <p class="text-sm text-muted">{{ viewingComment ? formatDateTime(viewingComment.createdAt) : '' }}</p>
                </div>
                <div v-if="viewingComment?.parentId" class="flex flex-col gap-1.5">
                    <label class="block text-xs text-secondary uppercase tracking-wide">{{ t('backend.comments.replyTo') }}</label>
                    <p class="text-sm text-secondary">{{ viewingComment?.parentAuthorName }}</p>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="block text-xs text-secondary uppercase tracking-wide">{{ t('backend.comments.content') }}</label>
                    <p class="text-sm text-primary whitespace-pre-wrap bg-surface-2 rounded-lg px-3 py-2.5">{{ viewingComment?.content }}</p>
                </div>
                <div v-if="viewingComment?.reactionCount > 0" class="flex flex-col gap-1.5">
                    <label class="block text-xs text-secondary uppercase tracking-wide">{{ t('backend.comments.reactions') }}</label>
                    <p class="text-sm text-secondary">{{ viewingComment?.reactionCount }}</p>
                </div>
            </div>
            <template #footer>
                <AppModalFooter bordered>
                    <AppIconButton v-if="viewingComment?.status !== 'approved' && can('editorial.comments.moderate')" color="emerald" :title="t('backend.comments.approve')" v-on:click="approveComment(viewingComment); viewingComment = null">
                        <Check class="w-4 h-4" :stroke-width="2" />
                    </AppIconButton>
                    <AppIconButton v-if="viewingComment?.status !== 'spam' && can('editorial.comments.moderate')" color="amber" :title="t('backend.comments.markSpam')" v-on:click="confirmSpam(viewingComment); viewingComment = null">
                        <Ban class="w-4 h-4" :stroke-width="2" />
                    </AppIconButton>
                    <AppIconButton v-if="can('editorial.comments.delete')" color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(viewingComment); viewingComment = null">
                        <Trash2 class="w-4 h-4" :stroke-width="2" />
                    </AppIconButton>
                    <AppIconButton color="default" :title="t('shared.common.cancel')" v-on:click="viewingComment = null">
                        <span class="text-xs px-1">✕</span>
                    </AppIconButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal
            :show="pendingToggleModeration"
            max-width="sm"
            :title="isModerationEnabled ? t('backend.comments.moderationDisableConfirm') : t('backend.comments.moderationEnableConfirm')"
            :closeable="false"
            v-on:close="pendingToggleModeration = false"
        >
            <p class="text-sm text-secondary">
                {{ isModerationEnabled ? t('backend.comments.moderationDisableConfirmDesc') : t('backend.comments.moderationEnableConfirmDesc') }}
            </p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="pendingToggleModeration = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}</AppButton>
                    <AppButton :variant="isModerationEnabled ? 'danger' : 'primary'" size="md" :loading="toggleModerationLoading" v-on:click="doToggleModeration">
                        {{ isModerationEnabled ? t('backend.comments.moderationOff') : t('backend.comments.moderationOn') }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal
            :show="!!pendingSpam"
            max-width="sm"
            :title="t('backend.comments.spamConfirm')"
            :closeable="false"
            v-on:close="pendingSpam = null"
        >
            <p class="text-sm text-secondary">{{ t('backend.comments.spamConfirmDesc') }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="pendingSpam = null"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}</AppButton>
                    <AppButton variant="danger" size="md" :loading="spamLoading" v-on:click="doSpam"><Ban class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('backend.comments.markSpam') }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal
            :show="!!pendingDelete"
            max-width="sm"
            :closeable="false"
            :title="t('shared.common.delete')"
            :icon="Trash2"
            v-on:close="pendingDelete = null"
        >
            <p class="text-sm text-primary">{{ t('backend.comments.deleteConfirm') }}</p>
            <p class="text-sm text-secondary">{{ t('backend.comments.deleteWarning') }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="pendingDelete = null"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}</AppButton>
                    <AppButton variant="danger" size="md" :loading="deleteLoading" v-on:click="doDelete"><Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.delete') }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>
    </div>
</template>
