const getBodyElement = () => document.querySelector("body");

const getPortalElement = () => document.querySelector(".portal");

const getTableElement = () => document.querySelector("table");

const getModalElement = () => document.querySelector(".modal");

const getBackdropElement = () => document.querySelector(".backdrop");

const openFile = fileId => {
    const portal = getPortalElement();
    portal.setAttribute("src", `static/${fileId}.html`);

    openModal();
};

const sortBy = key => {
    const params = parseQS();

    const isSortedBy = !params.sort.key || params.sort.key === key;
    const isDefaultSort = !params.sort.dir;

    const dir = !isSortedBy ? "ASC" : params.sort.dir === "ASC" || isDefaultSort ? "DESC" : "ASC";

    params.sort.key = key;
    params.sort.dir = dir;

    location.href = serializeQS(params);
};

const filterBy = (key, value) => {
    const params = parseQS();

    params.filter.key = key;
    params.filter.value = value;

    location.href = serializeQS(params);
};

const openModal = () => {
    const backdrop = getBackdropElement();
    const modal = getModalElement();
    const table = getTableElement();
    const body = getBodyElement();

    body.style.overflowY = "hidden";
    table.style.filter = "blur(4px)";
    backdrop.style.display = "block";
    modal.style.display = "block";

    backdrop.addEventListener("click", closeModal);
};

const closeModal = () => {
    const backdrop = getBackdropElement();
    const modal = getModalElement();
    const portal = getPortalElement();
    const table = getTableElement();
    const body = getBodyElement();

    body.style.overflowY = "auto";
    table.style.filter = "none";
    backdrop.style.display = "none";
    modal.style.display = "none";
    portal.setAttribute("src", "");

    backdrop.removeEventListener("click", closeModal);
};

const parseQS = () => {
    const href = document.location.href;
    const [, paramsStr] = href.split("?");
    const params = (paramsStr || "").split("&");

    const paramsObj = params.reduce((acc, paramStr) => {
        const [key, value] = paramStr.split("=");
        if (!key) return acc;

        return { ...acc, [key]: value };
    }, {});

    console.log("params", paramsObj);

    return {
        sort: {
            key: paramsObj?.sortKey || null,
            dir: paramsObj?.sortDir || null,
        },
        filter: {
            key: paramsObj?.filterKey || null,
            value: paramsObj?.filterValue || null,
        },
    };
};

const serializeQS = params => {
    const paramObj = {
        sortKey: params.sort.key || null,
        sortDir: params.sort.dir || null,
        filterKey: params.filter.key || null,
        filterValue: params.filter.value || null,
    };

    const qsObj = Object.keys(paramObj).reduce((acc, key) => {
        if (!paramObj[key]) return acc;
        return [...acc, `${key}=${paramObj[key]}`];
    }, []);

    const qs = qsObj.join("&");

    return !qs ? "" : `?${qs}`;
};

const parseTableMetadata = () => {
    const metadata = {
        columns: window.columns,
    };

    console.log("metadata", metadata);

    return metadata;
};

const filterTableRows = (params, metadata) => {
    if (!params.filter.key) return;

    const table = getTableElement();
    const rows = Array.from(table.querySelectorAll("tbody tr"));

    const key = params.filter.key;
    const value = params.filter.value || "";

    console.log("filter", key, value);

    const addClearFilterBadge = () => {
        const filterClear = document.createElement('div');

        filterClear.innerHTML = '<a href="/beta">Clear filters</a>';
        filterClear.classList.add("filter-clear");

        document.querySelector('body').append(filterClear);
    };

    const filterRows = () => {
        rows.forEach(tr => {
            const cell = tr.querySelector(`td[data-key=${key}]`);
            const text = (cell.textContent || "").trim().toLowerCase();
            const pattern = value.trim().toLowerCase();

            const isMatch = key === "characters" ? text.includes(pattern) : text === pattern;

            if (!isMatch) {
                tr.remove();
            }
        });
    };

    filterRows();
    addClearFilterBadge();
};

const sortTableRows = (params, metadata) => {
    const table = getTableElement();

    const firstSortableColumn = metadata.columns.findIndex(({ sortable }) => sortable);

    const key = params.sort.key || metadata.columns[firstSortableColumn].id;
    const dir = params.sort.dir || "ASC";

    console.log("sort", key, dir);

    const addHeaderTriangle = () => {
        const thead = table.querySelector("thead");
        const th = thead.querySelector(`th[data-key=${key}]`);

        th.textContent += ` ${dir === "ASC" ? "▲" : "▼"}`;
    };

    const sortRows = () => {
        const tbody = table.querySelector("tbody");
        const rows = Array.from(tbody.querySelectorAll("tr"));

        const values = rows.map((tr, index) => {
            const cell = tr.querySelector(`td[data-key=${key}]`);

            return {
                index,
                value: (cell.textContent || "").trim(),
            };
        });

        values.sort((a, b) => (a.value < b.value ? -1 : 1));

        if (dir === "DESC") {
            values.reverse();
        }

        for (let i = values.length - 1; i >= 0; --i) {
            const { index } = values[i];
            const row = rows[index];

            tbody.prepend(row.cloneNode(true));
            row.remove();
        }
    };

    sortRows();
    addHeaderTriangle();
};

const init = () => {
    const metadata = parseTableMetadata();
    const params = parseQS();

    filterTableRows(params, metadata);
    sortTableRows(params, metadata);
};
