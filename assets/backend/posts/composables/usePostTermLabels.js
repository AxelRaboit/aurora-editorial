import { computed } from "vue";

/**
 * Maps taxonomy term ids → localized label, for displaying term chips
 * on post rows in the list view.
 *
 * Flattens all `taxonomies[].terms[]`, resolves the label with a
 * locale fallback (default locale → French → slug), and exposes a
 * `postTermLabels(post)` getter that turns a post's `termIds` into
 * the matching label list (filters out unknown ids).
 *
 * @param {object} options
 * @param {Array}  options.parsedTaxonomies - the taxonomies tree as
 *   provided by the controller payload.
 * @param {string} options.defaultLocale
 */
export function usePostTermLabels({ parsedTaxonomies, defaultLocale }) {
    const termMap = computed(() => {
        const map = {};
        for (const taxonomy of parsedTaxonomies) {
            for (const term of taxonomy.terms ?? []) {
                const name =
                    term.translations?.[defaultLocale]?.name ??
                    term.translations?.["fr"]?.name ??
                    term.slug;
                map[term.id] = name;
            }
        }
        return map;
    });

    function postTermLabels(post) {
        return (post.termIds ?? [])
            .map((id) => termMap.value[id])
            .filter(Boolean);
    }

    return { termMap, postTermLabels };
}
