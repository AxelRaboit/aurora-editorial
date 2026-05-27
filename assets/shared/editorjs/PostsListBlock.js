/**
 * Posts List block for Editor.js.
 *
 * Two modes:
 *  - "manual": admin picks specific posts (search + select). Stored as ordered postIds.
 *  - "auto":   shows the full list of a given post type, paginated server-side via ?page=N.
 *
 * Saved data shape:
 *   {
 *     mode: "manual" | "auto",
 *     postTypeSlug: string,
 *     postIds?: number[],       // when mode = manual
 *     perPage?: number,         // when mode = auto
 *     columns: 1..4,
 *     title?: string,
 *   }
 */
export default class PostsListBlock {
    #wrapper = null;
    #data;
    #postTypes;
    #labels;
    #searchUrl;
    #selectedCache = new Map();
    #searchAbort = null;

    static get toolbox() {
        return {
            title: "Liste d'articles",
            icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>',
        };
    }

    constructor({ data, config = {} }) {
        this.#data = {
            mode: data.mode === "manual" ? "manual" : "auto",
            postTypeSlug: data.postTypeSlug ?? "article",
            postIds: Array.isArray(data.postIds)
                ? data.postIds.filter((id) => Number.isInteger(id))
                : [],
            perPage: typeof data.perPage === "number" ? data.perPage : 12,
            columns: typeof data.columns === "number" ? data.columns : 3,
            title: data.title ?? "",
        };
        this.#postTypes = config.postTypes ?? [];
        this.#searchUrl = config.searchUrl ?? "/backend/editorial/posts/search";
        this.#labels = {
            title: config.titleLabel ?? "Titre",
            postType: config.postTypeLabel ?? "Type de contenu",
            mode: config.modeLabel ?? "Mode",
            modeAuto: config.modeAutoLabel ?? "Liste complète paginée",
            modeManual: config.modeManualLabel ?? "Sélection manuelle",
            perPage: config.perPageLabel ?? "Articles par page",
            columns: config.columnsLabel ?? "Colonnes",
            searchPlaceholder:
                config.searchPlaceholderLabel ?? "Rechercher un article…",
            selected: config.selectedLabel ?? "Articles sélectionnés",
            empty: config.emptyLabel ?? "Aucun article sélectionné",
            noResults: config.noResultsLabel ?? "Aucun résultat",
        };
    }

    render() {
        this.#wrapper = document.createElement("div");
        this.#wrapper.className = "posts-list-block";
        this.#wrapper.innerHTML = `
            <div class="posts-list-block__header">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
                <span>Liste d'articles</span>
            </div>

            <div class="posts-list-block__field">
                <label>${this.#labels.title}</label>
                <input type="text" data-field="title" value="${this.#escape(this.#data.title)}" placeholder="Ex: Derniers articles">
            </div>

            <div class="posts-list-block__row">
                <div class="posts-list-block__field">
                    <label>${this.#labels.postType}</label>
                    <select data-field="postTypeSlug">
                        ${this.#postTypes
                            .map(
                                (pt) =>
                                    `<option value="${this.#escape(pt.slug)}" ${pt.slug === this.#data.postTypeSlug ? "selected" : ""}>${this.#escape(pt.label)}</option>`,
                            )
                            .join("")}
                    </select>
                </div>

                <div class="posts-list-block__field">
                    <label>${this.#labels.columns}</label>
                    <select data-field="columns">
                        <option value="1" ${this.#data.columns === 1 ? "selected" : ""}>1</option>
                        <option value="2" ${this.#data.columns === 2 ? "selected" : ""}>2</option>
                        <option value="3" ${this.#data.columns === 3 ? "selected" : ""}>3</option>
                        <option value="4" ${this.#data.columns === 4 ? "selected" : ""}>4</option>
                    </select>
                </div>
            </div>

            <div class="posts-list-block__field">
                <label>${this.#labels.mode}</label>
                <div class="posts-list-block__segmented" role="tablist">
                    <button type="button" role="tab" data-mode="auto" class="posts-list-block__seg ${this.#data.mode === "auto" ? "is-active" : ""}" aria-selected="${this.#data.mode === "auto"}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                        <span>${this.#escape(this.#labels.modeAuto)}</span>
                    </button>
                    <button type="button" role="tab" data-mode="manual" class="posts-list-block__seg ${this.#data.mode === "manual" ? "is-active" : ""}" aria-selected="${this.#data.mode === "manual"}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        <span>${this.#escape(this.#labels.modeManual)}</span>
                    </button>
                </div>
            </div>

            <div class="posts-list-block__panel" data-pane="auto" ${this.#data.mode === "auto" ? "" : "hidden"}>
                <div class="posts-list-block__field">
                    <label>${this.#labels.perPage}</label>
                    <input type="number" data-field="perPage" value="${this.#data.perPage}" min="1" max="100">
                </div>
            </div>

            <div class="posts-list-block__panel" data-pane="manual" ${this.#data.mode === "manual" ? "" : "hidden"}>
                <div class="posts-list-block__field posts-list-block__search">
                    <label>${this.#labels.searchPlaceholder.replace(/…$/, "")}</label>
                    <div class="posts-list-block__search-input">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" data-search placeholder="${this.#escape(this.#labels.searchPlaceholder)}">
                    </div>
                    <div class="posts-list-block__results" data-results hidden></div>
                </div>
                <div class="posts-list-block__field">
                    <label>${this.#labels.selected} <span class="posts-list-block__count" data-count></span></label>
                    <div class="posts-list-block__selected" data-selected></div>
                </div>
            </div>
        `;

        this.#wrapper.querySelectorAll("[data-field]").forEach((el) => {
            el.addEventListener("input", () => this.#syncField(el));
            el.addEventListener("change", () => this.#syncField(el));
        });

        this.#wrapper.querySelectorAll("[data-mode]").forEach((btn) => {
            btn.addEventListener("click", () => {
                const mode = btn.dataset.mode;
                this.#data.mode = mode;
                this.#wrapper
                    .querySelectorAll("[data-mode]")
                    .forEach((other) => {
                        const isActive = other.dataset.mode === mode;
                        other.classList.toggle("is-active", isActive);
                        other.setAttribute("aria-selected", String(isActive));
                    });
                this.#wrapper.querySelector('[data-pane="auto"]').hidden =
                    mode !== "auto";
                this.#wrapper.querySelector('[data-pane="manual"]').hidden =
                    mode !== "manual";
                if (mode === "manual") {
                    this.#hydrateSelected();
                }
            });
        });

        const searchInput = this.#wrapper.querySelector("[data-search]");
        searchInput?.addEventListener("input", () =>
            this.#onSearchInput(searchInput),
        );
        searchInput?.addEventListener("focus", () =>
            this.#onSearchInput(searchInput),
        );
        document.addEventListener("click", this.#onDocClick);

        if (this.#data.mode === "manual") {
            this.#hydrateSelected();
        }

        return this.#wrapper;
    }

    #onDocClick = (event) => {
        if (!this.#wrapper) {
            return;
        }
        const results = this.#wrapper.querySelector("[data-results]");
        if (!results) {
            return;
        }
        if (!this.#wrapper.contains(event.target)) {
            results.hidden = true;
        }
    };

    destroy() {
        document.removeEventListener("click", this.#onDocClick);
    }

    #syncField(el) {
        const field = el.dataset.field;
        const value = el.value;
        if (field === "perPage" || field === "columns") {
            this.#data[field] = parseInt(value, 10) || 0;
        } else {
            this.#data[field] = value;
        }
        if (field === "postTypeSlug" && this.#data.mode === "manual") {
            // changing post type clears selection (prevents stale ids from another type)
            this.#data.postIds = [];
            this.#renderSelected([]);
        }
    }

    async #onSearchInput(input) {
        const query = input.value.trim();
        const results = this.#wrapper.querySelector("[data-results]");
        if (!results) {
            return;
        }
        if ("" === query) {
            results.hidden = true;
            results.innerHTML = "";
            return;
        }

        const postType = this.#postTypes.find(
            (pt) => pt.slug === this.#data.postTypeSlug,
        );
        const params = new URLSearchParams({ q: query });
        if (postType?.id) {
            params.set("postTypeId", String(postType.id));
        }

        if (this.#searchAbort) {
            this.#searchAbort.abort();
        }
        this.#searchAbort = new AbortController();

        try {
            const response = await fetch(
                `${this.#searchUrl}?${params.toString()}`,
                {
                    signal: this.#searchAbort.signal,
                    headers: { Accept: "application/json" },
                },
            );
            if (!response.ok) {
                return;
            }
            const json = await response.json();
            const items = Array.isArray(json.results) ? json.results : [];
            this.#renderSearchResults(items, results);
        } catch (error) {
            if (error.name !== "AbortError") {
                results.hidden = true;
            }
        }
    }

    #renderSearchResults(items, container) {
        if (0 === items.length) {
            container.innerHTML = `<div class="posts-list-block__result posts-list-block__result--empty">${this.#escape(this.#labels.noResults)}</div>`;
            container.hidden = false;
            return;
        }

        container.innerHTML = items
            .map(
                (item) =>
                    `<button type="button" class="posts-list-block__result" data-id="${item.id}" data-title="${this.#escape(item.title ?? "—")}">
                        <span>${this.#escape(item.title ?? "—")}</span>
                        <small>${this.#escape(item.postType ?? "")}</small>
                    </button>`,
            )
            .join("");
        container.hidden = false;

        container.querySelectorAll("[data-id]").forEach((btn) => {
            btn.addEventListener("click", () => {
                const id = parseInt(btn.dataset.id, 10);
                const title = btn.dataset.title;
                if (!Number.isInteger(id) || this.#data.postIds.includes(id)) {
                    return;
                }
                this.#data.postIds.push(id);
                this.#selectedCache.set(id, { id, title });
                this.#renderSelected(
                    this.#data.postIds.map(
                        (pid) =>
                            this.#selectedCache.get(pid) ?? {
                                id: pid,
                                title: `#${pid}`,
                            },
                    ),
                );
                container.hidden = true;
                container.innerHTML = "";
                const searchInput =
                    this.#wrapper.querySelector("[data-search]");
                if (searchInput) {
                    searchInput.value = "";
                }
            });
        });
    }

    async #hydrateSelected() {
        if (0 === this.#data.postIds.length) {
            this.#renderSelected([]);
            return;
        }
        const missing = this.#data.postIds.filter(
            (id) => !this.#selectedCache.has(id),
        );
        if (missing.length > 0) {
            try {
                const response = await fetch(
                    `${this.#searchUrl}?ids=${missing.join(",")}`,
                    {
                        headers: { Accept: "application/json" },
                    },
                );
                if (response.ok) {
                    const json = await response.json();
                    (json.results ?? []).forEach((item) =>
                        this.#selectedCache.set(item.id, item),
                    );
                }
            } catch {
                // ignore
            }
        }
        this.#renderSelected(
            this.#data.postIds.map(
                (pid) =>
                    this.#selectedCache.get(pid) ?? {
                        id: pid,
                        title: `#${pid}`,
                    },
            ),
        );
    }

    #renderSelected(items) {
        const container = this.#wrapper?.querySelector("[data-selected]");
        if (!container) {
            return;
        }
        const countEl = this.#wrapper.querySelector("[data-count]");
        if (countEl) {
            countEl.textContent = items.length > 0 ? `(${items.length})` : "";
        }
        if (0 === items.length) {
            container.innerHTML = `<div class="posts-list-block__empty">${this.#escape(this.#labels.empty)}</div>`;
            return;
        }
        container.innerHTML = items
            .map(
                (item, index) =>
                    `<div class="posts-list-block__chip" data-id="${item.id}">
                        <button type="button" class="posts-list-block__chip-move" data-action="up" data-index="${index}" ${index === 0 ? "disabled" : ""} aria-label="Up">↑</button>
                        <button type="button" class="posts-list-block__chip-move" data-action="down" data-index="${index}" ${index === items.length - 1 ? "disabled" : ""} aria-label="Down">↓</button>
                        <span class="posts-list-block__chip-title">${this.#escape(item.title ?? `#${item.id}`)}</span>
                        <button type="button" class="posts-list-block__chip-remove" data-action="remove" data-index="${index}" aria-label="Remove">×</button>
                    </div>`,
            )
            .join("");

        container.querySelectorAll("[data-action]").forEach((btn) => {
            btn.addEventListener("click", () => {
                const action = btn.dataset.action;
                const index = parseInt(btn.dataset.index, 10);
                if (action === "remove") {
                    this.#data.postIds.splice(index, 1);
                } else if (action === "up" && index > 0) {
                    [this.#data.postIds[index - 1], this.#data.postIds[index]] =
                        [
                            this.#data.postIds[index],
                            this.#data.postIds[index - 1],
                        ];
                } else if (
                    action === "down" &&
                    index < this.#data.postIds.length - 1
                ) {
                    [this.#data.postIds[index + 1], this.#data.postIds[index]] =
                        [
                            this.#data.postIds[index],
                            this.#data.postIds[index + 1],
                        ];
                }
                this.#renderSelected(
                    this.#data.postIds.map(
                        (pid) =>
                            this.#selectedCache.get(pid) ?? {
                                id: pid,
                                title: `#${pid}`,
                            },
                    ),
                );
            });
        });
    }

    #escape(value) {
        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/"/g, "&quot;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;");
    }

    save() {
        const base = {
            mode: this.#data.mode,
            postTypeSlug: this.#data.postTypeSlug,
            columns: Math.max(1, Math.min(4, this.#data.columns)),
            title: this.#data.title,
        };
        if (this.#data.mode === "manual") {
            return { ...base, postIds: this.#data.postIds };
        }
        return {
            ...base,
            perPage: Math.max(1, Math.min(100, this.#data.perPage)),
        };
    }
}
