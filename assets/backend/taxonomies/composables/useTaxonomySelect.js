import { ref, computed } from "vue";

export function useTaxonomySelect(initialTaxonomies, locales) {
    const taxonomies = ref([...initialTaxonomies]);
    const selectedId = ref(taxonomies.value[0]?.id ?? null);
    const activeLocale = ref(locales[0] ?? "fr");

    const selected = computed(
        () => taxonomies.value.find((tx) => tx.id === selectedId.value) ?? null,
    );

    function replaceTaxonomy(fresh) {
        const idx = taxonomies.value.findIndex((tx) => tx.id === fresh.id);
        if (idx === -1) taxonomies.value.push(fresh);
        else taxonomies.value[idx] = fresh;
    }

    function translationLabel(taxonomy, locale) {
        return (
            taxonomy?.translations?.[locale]?.label ??
            taxonomy?.translations?.[locales[0]]?.label ??
            taxonomy?.slug ??
            ""
        );
    }

    function termName(term, locale) {
        return (
            term?.translations?.[locale]?.name ??
            term?.translations?.[locales[0]]?.name ??
            "(—)"
        );
    }

    return {
        taxonomies,
        selectedId,
        activeLocale,
        selected,
        replaceTaxonomy,
        translationLabel,
        termName,
    };
}
