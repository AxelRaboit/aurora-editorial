import { slugifyIfEmpty } from "@/shared/utils/format/slugify.js";

/**
 * Term-form glue exposed as functions to keep the SFC presentational.
 *
 * - `autoSlugTerm(termForm, locale)` derives the per-locale slug from the
 *   name if the slug is empty. Bind to the name input's @blur.
 * - `normalizeAllLocaleSlugs(termForm, locales)` does the same for every
 *   locale; call right before submit so locales the user didn't touch
 *   still get a sensible slug.
 */
export function useTermFormHelpers() {
    function autoSlugTerm(termForm, locale) {
        const entry = termForm.translations?.[locale];
        if (entry) entry.slug = slugifyIfEmpty(entry.slug, entry.name);
    }

    function normalizeAllLocaleSlugs(termForm, locales) {
        for (const locale of locales) {
            const entry = termForm.translations?.[locale];
            if (entry?.name)
                entry.slug = slugifyIfEmpty(entry.slug, entry.name);
        }
    }

    return { autoSlugTerm, normalizeAllLocaleSlugs };
}
