import { ref, computed } from "vue";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { slugify } from "@/shared/utils/format/slugify.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

export function useFormEditor(props, fetchForms) {
    const { t } = useI18n();

    const selectedForm = ref(null);
    const saving = ref(false);
    const activeTab = ref("settings");
    const activeLocale = ref(props.locales[0] ?? "fr");
    const showDeleteConfirm = ref(false);
    const deleting = ref(false);
    const slugLocked = ref(
        Object.fromEntries(props.locales.map((l) => [l, false])),
    );
    const sharedSlug = ref(false);
    const formErrors = ref({});

    const isCreating = computed(() => null === selectedForm.value);

    function defaultLocale() {
        return props.locales[0] ?? "fr";
    }

    function emptyTranslations() {
        return Object.fromEntries(
            props.locales.map((l) => [
                l,
                { title: "", slug: "", description: "" },
            ]),
        );
    }

    function emptyForm() {
        return {
            notifyEmail: "",
            webhookUrl: "",
            crmSync: false,
            steps: [],
            active: true,
            translations: emptyTranslations(),
            fields: [],
        };
    }

    const editingForm = ref(emptyForm());

    function formTitle(form, locale = activeLocale.value) {
        return (
            form?.translations?.[locale]?.title ??
            form?.translations?.[defaultLocale()]?.title ??
            ""
        );
    }

    function isLocaleFilled(locale) {
        return !!editingForm.value.translations[locale]?.title?.trim();
    }

    function localeFieldError(scope, locale, key) {
        return scope[`translations.${locale}.${key}`];
    }

    const { request: jsonRequest } = useRequest();

    function applyFormResponse(form) {
        selectedForm.value = form;
        const translations = emptyTranslations();
        for (const [locale, data] of Object.entries(form.translations ?? {})) {
            translations[locale] = {
                title: data.title ?? "",
                slug: data.slug ?? "",
                description: data.description ?? "",
            };
        }
        editingForm.value = {
            notifyEmail: form.notifyEmail ?? "",
            webhookUrl: form.webhookUrl ?? "",
            crmSync: form.crmSync ?? false,
            steps: form.steps ?? [],
            active: form.active,
            translations,
            fields: form.fields ?? [],
        };
        slugLocked.value = Object.fromEntries(
            props.locales.map((l) => [l, !!translations[l]?.slug]),
        );
        const slugs = props.locales
            .map((l) => translations[l]?.slug)
            .filter((s) => s);
        sharedSlug.value =
            slugs.length >= 2 && slugs.every((s) => s === slugs[0]);
    }

    function startCreate() {
        selectedForm.value = null;
        activeTab.value = "settings";
        activeLocale.value = defaultLocale();
        editingForm.value = emptyForm();
        formErrors.value = {};
        slugLocked.value = Object.fromEntries(
            props.locales.map((l) => [l, false]),
        );
        sharedSlug.value = false;
    }

    async function selectForm(form) {
        selectedForm.value = form;
        activeTab.value = "settings";
        activeLocale.value = defaultLocale();
        formErrors.value = {};
        try {
            const data = await jsonRequest(
                buildPath(props.getPath, { id: form.id }),
                null,
                HttpMethod.Get,
            );
            if (data?.success) applyFormResponse(data.form);
        } catch {
            toast.error(t("shared.common.error"));
        }
    }

    function propagateSharedSlug() {
        if (!sharedSlug.value) return;
        const sourceSlug =
            editingForm.value.translations[activeLocale.value]?.slug ?? "";
        for (const locale of props.locales) {
            if (
                locale !== activeLocale.value &&
                editingForm.value.translations[locale]
            ) {
                editingForm.value.translations[locale].slug = sourceSlug;
                slugLocked.value[locale] = true;
            }
        }
    }

    function onTitleInput() {
        const trans = editingForm.value.translations[activeLocale.value];
        if (trans && !slugLocked.value[activeLocale.value]) {
            trans.slug = slugify(trans.title);
            propagateSharedSlug();
        }
    }

    function onSlugInput() {
        slugLocked.value[activeLocale.value] = true;
        propagateSharedSlug();
    }
    function onSharedSlugToggle() {
        if (sharedSlug.value) propagateSharedSlug();
    }

    async function saveForm() {
        if (saving.value) return;
        saving.value = true;
        formErrors.value = {};
        const isNew = isCreating.value;
        const url = isNew
            ? props.createPath
            : buildPath(props.updatePath, { id: selectedForm.value.id });
        const payload = {
            notifyEmail: editingForm.value.notifyEmail || null,
            webhookUrl: editingForm.value.webhookUrl || null,
            crmSync: editingForm.value.crmSync,
            steps: editingForm.value.steps?.length
                ? editingForm.value.steps
                : null,
            active: editingForm.value.active,
            translations: editingForm.value.translations,
        };
        try {
            const data = await jsonRequest(url, payload, HttpMethod.Post);
            if (data?.success) {
                toast.success(t("shared.common.saved"));
                applyFormResponse(data.form);
                await fetchForms();
            } else if (data?.errors) {
                formErrors.value = data.errors;
                const firstErrorLocale = Object.keys(data.errors)
                    .find((k) => k.startsWith("translations."))
                    ?.split(".")[1];
                if (firstErrorLocale) activeLocale.value = firstErrorLocale;
            } else {
                toast.error(t("shared.common.error"));
            }
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            saving.value = false;
        }
    }

    async function confirmDelete() {
        deleting.value = true;
        try {
            const data = await jsonRequest(
                buildPath(props.deletePath, { id: selectedForm.value.id }),
                null,
                HttpMethod.Post,
            );
            if (data?.success) {
                toast.success(t("shared.common.deleted"));
                selectedForm.value = null;
                await fetchForms();
            } else toast.error(t("shared.common.error"));
        } catch {
            toast.error(t("shared.common.error"));
        } finally {
            deleting.value = false;
            showDeleteConfirm.value = false;
        }
    }

    return {
        selectedForm,
        editingForm,
        formErrors,
        saving,
        activeTab,
        activeLocale,
        showDeleteConfirm,
        deleting,
        slugLocked,
        sharedSlug,
        isCreating,
        emptyForm,
        defaultLocale,
        formTitle,
        isLocaleFilled,
        localeFieldError,
        jsonRequest,
        startCreate,
        selectForm,
        onTitleInput,
        onSlugInput,
        onSharedSlugToggle,
        saveForm,
        confirmDelete,
    };
}
