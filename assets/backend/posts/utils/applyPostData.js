/**
 * Apply a fetched post payload onto the editor's reactive form + side state.
 * Used both on initial load and after restoring a revision; keeps the two
 * paths in sync without duplicating ~25 lines of assignment.
 *
 * @param {object} post  — post payload from /admin/posts/{id} endpoint
 * @param {object} form  — reactive form (mutated in place)
 * @param {object} side  — refs for state that lives outside the form:
 *   { publishedAt, trashed, featuredMediaUrl, featuredMediaFocalPosition,
 *     relatedPosts, version }
 */
export function applyPostData(post, form, side) {
    form.postTypeId = String(post.postType.id);
    form.status = post.status;
    form.scheduledAt = post.scheduledAt ? post.scheduledAt.slice(0, 16) : "";
    form.featuredMediaId = post.featuredMediaId ?? null;
    form.termIds = [...(post.termIds ?? [])];
    form.relatedPostIds = [...(post.relatedPostIds ?? [])];
    form.commentsEnabled = post.commentsEnabled ?? true;

    side.publishedAt.value = post.publishedAt ?? null;
    side.trashed.value = post.trashed ?? false;
    side.featuredMediaUrl.value = post.featuredMediaUrl ?? null;
    side.featuredMediaFocalPosition.value =
        post.featuredMediaFocalPosition ?? "50% 50%";
    side.relatedPosts.value = [...(post.relatedPosts ?? [])];
    side.version.value = post.version ?? null;

    for (const [locale, translation] of Object.entries(
        post.translations ?? {},
    )) {
        if (form.translations[locale]) {
            Object.assign(form.translations[locale], translation);
        }
    }
}
