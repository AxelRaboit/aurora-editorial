import { ref, reactive, computed, watch, nextTick } from "vue";
import { useDebounce } from "@/shared/composables/useDebounce.js";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { MenuTargetType } from "@core/utils/enums/menu/menuTargetType.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";

export function useMenuItemForm(props) {
    const { t } = useI18n();
    const { request: jsonRequest } = useRequest();

    const form = reactive({
        targetType: MenuTargetType.Home,
        targetId: null,
        customUrl: "",
        openInNewTab: false,
        cssClass: "",
        visibility: "always",
        translations: {},
    });

    const targetLabel = ref("");
    const activeLocale = ref(props.locales[0] ?? "fr");
    const saving = ref(false);

    watch(
        () => props.show,
        async (open) => {
            if (!open) return;
            if (props.editing) {
                form.targetType = props.editing.targetType;
                form.targetId = props.editing.targetId ?? null;
                form.customUrl = props.editing.customUrl ?? "";
                form.openInNewTab = !!props.editing.openInNewTab;
                form.cssClass = props.editing.cssClass ?? "";
                form.visibility = props.editing.visibility ?? "always";
                form.translations = { ...(props.editing.translations ?? {}) };
                targetLabel.value = props.editing.targetPreview?.label ?? "";
            } else {
                form.targetType = MenuTargetType.Home;
                form.targetId = null;
                form.customUrl = "";
                form.openInNewTab = false;
                form.cssClass = "";
                form.visibility = "always";
                form.translations = {};
                targetLabel.value = "";
            }
            activeLocale.value = props.locales[0] ?? "fr";
            await nextTick();
        },
    );

    const requiresTargetId = computed(() =>
        [
            MenuTargetType.Post,
            MenuTargetType.Term,
            MenuTargetType.PostTypeArchive,
        ].includes(form.targetType),
    );
    const requiresCustomUrl = computed(
        () => form.targetType === MenuTargetType.CustomUrl,
    );
    const requiresTranslationOverride = computed(
        () => form.targetType === MenuTargetType.CustomUrl,
    );

    watch(
        () => form.targetType,
        (newType, oldType) => {
            if (newType === oldType) return;
            form.targetId = null;
            targetLabel.value = "";
            if (newType !== MenuTargetType.CustomUrl) form.customUrl = "";
        },
    );

    const pickerQuery = ref("");
    const pickerResults = ref([]);
    const pickerLoading = ref(false);
    const pickerOpen = ref(false);
    const postTypeFilter = ref(null);
    const taxonomyFilter = ref(null);
    const postTypeOptions = ref([]);
    const taxonomyOptions = ref([]);

    async function loadFilters() {
        try {
            if (form.targetType === MenuTargetType.Post) {
                const data = await jsonRequest(
                    props.pickerPostTypesPath,
                    null,
                    HttpMethod.Get,
                );
                if (data?.success)
                    postTypeOptions.value = [
                        { id: 0, label: t("backend.menus.all_types") },
                        ...data.items,
                    ];
            }
            if (
                form.targetType === MenuTargetType.Term &&
                !taxonomyOptions.value.length
            ) {
                const data = await jsonRequest(
                    props.pickerTaxonomiesPath,
                    null,
                    HttpMethod.Get,
                );
                if (data?.success)
                    taxonomyOptions.value = [
                        { id: 0, label: t("backend.menus.all_taxonomies") },
                        ...data.items,
                    ];
            }
            if (form.targetType === MenuTargetType.PostTypeArchive) {
                const data = await jsonRequest(
                    `${props.pickerPostTypesPath}?withArchive=1`,
                    null,
                    HttpMethod.Get,
                );
                if (data?.success) postTypeOptions.value = data.items;
            }
        } catch {
            toast.error(t("shared.common.error"));
        }
    }

    watch(
        () => form.targetType,
        async () => {
            pickerQuery.value = "";
            pickerResults.value = [];
            await loadFilters();
        },
    );
    watch(
        () => props.show,
        async (open) => {
            if (open) await loadFilters();
        },
    );

    async function runSearch() {
        pickerLoading.value = true;
        try {
            let url;
            if (form.targetType === MenuTargetType.Post) {
                url = `${props.pickerPostsPath}?q=${encodeURIComponent(pickerQuery.value)}`;
                if (postTypeFilter.value)
                    url += `&postTypeId=${postTypeFilter.value}`;
            } else if (form.targetType === MenuTargetType.Term) {
                url = `${props.pickerTermsPath}?q=${encodeURIComponent(pickerQuery.value)}`;
                if (taxonomyFilter.value)
                    url += `&taxonomyId=${taxonomyFilter.value}`;
            } else {
                return;
            }
            const data = await jsonRequest(url, null, HttpMethod.Get);
            if (data?.success) {
                pickerResults.value = data.items;
                pickerOpen.value = true;
            }
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            pickerLoading.value = false;
        }
    }

    const debouncedSearch = useDebounce(runSearch, 250);

    watch([postTypeFilter, taxonomyFilter], () => {
        if (
            form.targetType === MenuTargetType.Post ||
            form.targetType === MenuTargetType.Term
        )
            runSearch();
    });

    async function onPickerFocus() {
        if (pickerResults.value.length === 0) await runSearch();
        else pickerOpen.value = true;
    }

    function pickResult(result) {
        form.targetId = result.id;
        targetLabel.value = result.label;
        pickerOpen.value = false;
        pickerQuery.value = "";
        pickerResults.value = [];
    }

    function clearTarget() {
        form.targetId = null;
        targetLabel.value = "";
    }

    const archiveOptions = computed(() =>
        postTypeOptions.value
            .filter((pt) => pt.id !== 0)
            .map((pt) => ({ value: pt.id, label: pt.label })),
    );

    const visibilityOptions = computed(() =>
        props.visibilities.map((v) => ({ value: v.value, label: v.label })),
    );
    const targetTypeOptions = computed(() =>
        props.targetTypes.map((tt) => ({ value: tt.value, label: tt.label })),
    );

    const errors = ref({});

    function validate() {
        errors.value = {};
        if (requiresTargetId.value && !form.targetId)
            errors.value.target = t("backend.menus.errors.target_required");
        if (requiresCustomUrl.value && !form.customUrl.trim())
            errors.value.customUrl = t(
                "backend.menus.errors.custom_url_required",
            );
        if (requiresTranslationOverride.value) {
            const hasAny = Object.values(form.translations).some(
                (v) => v && v.trim(),
            );
            if (!hasAny)
                errors.value.translations = t(
                    "backend.menus.errors.translation_required_for_custom_url",
                );
        }
        return Object.keys(errors.value).length === 0;
    }

    function buildPayload() {
        if (!validate()) return null;
        return {
            targetType: form.targetType,
            targetId: requiresTargetId.value ? form.targetId : null,
            customUrl: requiresCustomUrl.value ? form.customUrl : null,
            openInNewTab: form.openInNewTab,
            cssClass: form.cssClass || null,
            visibility: form.visibility,
            translations: form.translations,
        };
    }

    function setTranslation(locale, value) {
        form.translations = { ...form.translations, [locale]: value };
    }

    return {
        form,
        targetLabel,
        activeLocale,
        saving,
        errors,
        requiresTargetId,
        requiresCustomUrl,
        requiresTranslationOverride,
        pickerQuery,
        pickerResults,
        pickerLoading,
        pickerOpen,
        postTypeFilter,
        taxonomyFilter,
        postTypeOptions,
        taxonomyOptions,
        debouncedSearch,
        onPickerFocus,
        pickResult,
        clearTarget,
        archiveOptions,
        visibilityOptions,
        targetTypeOptions,
        buildPayload,
        setTranslation,
    };
}
