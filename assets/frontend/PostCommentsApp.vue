<script setup>
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { ref, reactive, onMounted, onBeforeUnmount } from "vue";
import { useI18n } from "vue-i18n";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import PostCommentsForm from "./PostCommentsForm.vue";
import PostCommentsReactionBar from "./PostCommentsReactionBar.vue";
import AppTextLinkButton from "@/shared/components/action/AppTextLinkButton.vue";
import { translateServerErrors } from "@/shared/utils/validation/translateServerErrors.js";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import { useRequest } from "@/shared/composables/http/frontend/useRequest.js";

const props = defineProps({
    listPath: { type: String, required: true },
    submitPath: { type: String, required: true },
    reactPathTemplate: { type: String, required: true },
    commentsEnabled: { type: Boolean, default: false },
});

const { t } = useI18n();
const { formatDateShort } = useDateFormat();

const { loading, request: requestList } = useRequest();
const { loading: submitting, request: requestSubmit } = useRequest();
const { request: requestReact } = useRequest();

const roots = ref([]);
const replies = ref({});
const reactionEmojis = ref({});
const successMessage = ref("");
let successTimeout = null;
const errors = ref({});
const replyOpenFor = ref(null);
const form = reactive({ authorName: "", authorEmail: "", content: "" });
const mainForm = reactive({ authorName: "", authorEmail: "", content: "" });

async function fetchComments() {
    const data = await requestList(props.listPath, null, HttpMethod.Get);
    if (data?.success) {
        roots.value = data.roots;
        replies.value = data.replies;
        reactionEmojis.value = data.reactionEmojis;
    }
}

onMounted(() => {
    if (props.commentsEnabled) fetchComments();
});

onBeforeUnmount(() => {
    clearTimeout(successTimeout);
});

function openReply(commentId) {
    replyOpenFor.value = replyOpenFor.value === commentId ? null : commentId;
    form.authorName = "";
    form.authorEmail = "";
    form.content = "";
    errors.value = {};
}

async function submitComment(parentId, activeForm) {
    errors.value = {};
    const data = await requestSubmit(props.submitPath, {
        authorName: activeForm.authorName,
        authorEmail: activeForm.authorEmail,
        content: activeForm.content,
        parent_id: parentId ?? 0,
    });
    if (!data?.success) {
        errors.value = translateServerErrors(t, data?.errors);
        return;
    }
    activeForm.authorName = "";
    activeForm.authorEmail = "";
    activeForm.content = "";
    replyOpenFor.value = null;
    successMessage.value = t("shared.comment.success");
    await fetchComments();
    clearTimeout(successTimeout);
    successTimeout = setTimeout(() => { successMessage.value = ""; }, 5000);
}

async function react(commentId, type) {
    const url = buildPath(props.reactPathTemplate, { commentId });
    const data = await requestReact(url, { type });
    if (!data?.success) return;
    const update = (list) => list.map((c) =>
        c.id === commentId ? { ...c, reactionCounts: data.counts } : c
    );
    roots.value = update(roots.value);
    const updated = {};
    for (const [rootId, list] of Object.entries(replies.value)) {
        updated[rootId] = update(list);
    }
    replies.value = updated;
}

function formatDate(iso) {
    return formatDateShort(iso);
}

</script>

<template>
    <div v-if="commentsEnabled" class="max-w-3xl mx-auto mt-12 pt-8 border-t border-line">
        <h2 class="text-2xl font-bold text-primary mb-6">{{ t("shared.comment.title") }}</h2>

        <div v-if="successMessage" class="mb-6 p-4 rounded-lg bg-emerald-500/15 text-emerald-600 text-sm">
            {{ successMessage }}
        </div>

        <div v-if="loading" class="text-muted text-sm mb-8">{{ t("shared.common.loading") }}</div>

        <template v-else>
            <AppNoData v-if="!roots.length" :message="t('shared.comment.empty')" />

            <div v-else class="space-y-6 mb-10">
                <div v-for="rootComment in roots" :key="rootComment.id" class="bg-surface-2 rounded-lg p-5">
                    <div class="flex items-center gap-3 mb-3">
                        <span class="font-semibold text-primary text-sm">{{ rootComment.authorName }}</span>
                        <time class="text-xs text-muted">{{ formatDate(rootComment.createdAt) }}</time>
                    </div>
                    <p class="text-secondary text-sm leading-relaxed">{{ rootComment.content }}</p>

                    <PostCommentsReactionBar :comment="rootComment" :reaction-emojis="reactionEmojis" v-on:react="react" />

                    <div v-if="replies[rootComment.id]?.length" class="mt-4 ml-6 space-y-3 border-l-2 border-line pl-4">
                        <div v-for="reply in replies[rootComment.id]" :key="reply.id" class="bg-surface rounded-lg p-4">
                            <div class="flex items-center gap-2 mb-2 flex-wrap">
                                <span class="font-semibold text-primary text-sm">{{ reply.authorName }}</span>
                                <span v-if="reply.parentAuthorName" class="text-xs text-muted">↩ {{ reply.parentAuthorName }}</span>
                                <time class="text-xs text-muted ml-auto">{{ formatDate(reply.createdAt) }}</time>
                            </div>
                            <p class="text-secondary text-sm leading-relaxed">{{ reply.content }}</p>

                            <PostCommentsReactionBar :comment="reply" :reaction-emojis="reactionEmojis" v-on:react="react" />

                            <AppTextLinkButton color="muted" size="xs" class="mt-2" v-on:click="openReply(reply.id)">
                                ↩ {{ t("shared.comment.reply") }}
                            </AppTextLinkButton>
                            <div v-if="replyOpenFor === reply.id" class="mt-3">
                                <PostCommentsForm
                                    :parent-id="reply.id"
                                    :submitting="submitting"
                                    :errors="errors"
                                    :author-name="form.authorName"
                                    :author-email="form.authorEmail"
                                    :content="form.content"
                                    v-on:update:author-name="form.authorName = $event"
                                    v-on:update:author-email="form.authorEmail = $event"
                                    v-on:update:content="form.content = $event"
                                    v-on:submit="submitComment(reply.id, form)"
                                    v-on:cancel="replyOpenFor = null"
                                />
                            </div>
                        </div>
                    </div>

                    <AppTextLinkButton color="muted" size="xs" class="mt-3" v-on:click="openReply(rootComment.id)">
                        ↩ {{ t("shared.comment.reply") }}
                    </AppTextLinkButton>
                    <div v-if="replyOpenFor === rootComment.id" class="mt-3">
                        <PostCommentsForm
                            :parent-id="rootComment.id"
                            :submitting="submitting"
                            :errors="errors"
                            :author-name="form.authorName"
                            :author-email="form.authorEmail"
                            :content="form.content"
                            v-on:update:author-name="form.authorName = $event"
                            v-on:update:author-email="form.authorEmail = $event"
                            v-on:update:content="form.content = $event"
                            v-on:submit="submitComment(rootComment.id, form)"
                            v-on:cancel="replyOpenFor = null"
                        />
                    </div>
                </div>
            </div>
        </template>

        <div class="bg-surface border border-line rounded-xl p-6">
            <h3 class="text-lg font-semibold text-primary mb-4">{{ t("shared.comment.form_title") }}</h3>
            <PostCommentsForm
                :parent-id="null"
                :submitting="submitting"
                :errors="errors"
                :author-name="mainForm.authorName"
                :author-email="mainForm.authorEmail"
                :content="mainForm.content"
                v-on:update:author-name="mainForm.authorName = $event"
                v-on:update:author-email="mainForm.authorEmail = $event"
                v-on:update:content="mainForm.content = $event"
                v-on:submit="submitComment(null, mainForm)"
            />
        </div>
    </div>
</template>
