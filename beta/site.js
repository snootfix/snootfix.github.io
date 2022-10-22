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

    const isSortedBy = !params.sort.key || (params.sort.key === key);
    const isDefaultSort = !params.sort.dir;

    const dir = !isSortedBy ? "ASC" : (params.sort.dir === "ASC" || isDefaultSort ? "DESC" : "ASC");

    params.sort.key = key;
    params.sort.dir = dir;

    location.href = serializeParams(params);
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
    const [, paramsStr] = href.split('?');
    const params = (paramsStr || "").split('&');

    const paramsObj = params.reduce((acc, paramStr) => {
        const [key, value] = paramStr.split("=");
        if (!key) return acc;

        return { ...acc, [key]: value };
    }, {});

    console.log('params', paramsObj);

    return {
        sort: {
            key: paramsObj?.sortKey || null,
            dir: paramsObj?.sortDir || null
        }
    };
};

const serializeParams = (params) => {
    const paramObj = {
        sortKey: params.sort.key || null,
        sortDir: params.sort.dir || null
    };

    const qsObj = Object.keys(paramObj).reduce((acc, key) => {
        if (!paramObj[key]) return acc;
        return [...acc, `${key}=${paramObj[key]}`];
    }, []);

    const qs = qsObj.join('&');

    return !qs ? '' : `?${qs}`;
};

const parseTableMetadata = () => {
    const metadata = {
        columns: window.columns
    };

    console.log('metadata', metadata);

    return metadata;
};

const sortTableRows = (params, metadata) => {
    const table = getTableElement();

    const firstSortableColumn = metadata.columns.findIndex(({ sortable }) => sortable);

    const key = params.sort.key || metadata.columns[firstSortableColumn].id;
    const dir = params.sort.dir || "ASC";

    const addHeaderTriangle = () => {
        const thead = table.querySelector('thead');
        const th = thead.querySelector(`th[data-key=${key}]`);

        th.textContent += ` ${dir === "ASC" ? '▲' : '▼'}`;
    };

    const sortRows = () => {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));

        const values = rows.map((tr, index) => {
            const cell = tr.querySelector(`td[data-key=${key}]`);

            return {
                index,
                value: (cell.textContent || "").trim()
            };
        });

        values.sort((a, b) => a.value < b.value ? -1 : 1);

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

    console.log("sort", key, dir);

    sortRows();
    addHeaderTriangle();
};

const init = () => {
    const metadata = parseTableMetadata();
    const params = parseQS();

    sortTableRows(params, metadata);

    //TODO: filter table rows
    //TODO: sort table rows
};
