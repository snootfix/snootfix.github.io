const LOCAL_STORAGE_KEY = '__beta__snootfix__pins';

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

const pin = key => {
    const localStorageParams = parseLocalStorage();

    if (localStorageParams.pins.includes(key)) {
        localStorageParams.pins = localStorageParams.pins.filter(pinKey => pinKey !== key);
    } else {
        localStorageParams.pins = [...localStorageParams.pins, key];
    }

    serializeLocalStorage(localStorageParams);

    location.reload();
};

//TODO: copy to clipboard
const getPermalink = key => {
    const makeInput = text => {
        const id = "copyable_hidden_input_" + String(Date.now()) + String(Math.random() * 1000);
        const inputNode = document.createElement("input");
        inputNode.value = text;
        inputNode.style.position = "fixed";
        inputNode.style.top = "-1000px";
        inputNode.style.left = "-9999px";
        inputNode.style.opacity = "0";
        inputNode.id = id;

        return inputNode;
    };

    const copyText = text => {
        const inputNode = makeInput(text);
        const rootNode = document.querySelector("body");

        rootNode.appendChild(inputNode);
        inputNode.select();
        document.execCommand("copy");

        const inputNodeAfter = document.getElementById(inputNode.id);
        inputNodeAfter?.parentNode.removeChild(inputNodeAfter);
    };

    copyText(document.location.origin + `?fic=${key}`);
    alert("Permalink copied to clipboard!");
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
        fic: paramsObj?.fic || null
    };
};

const serializeQS = params => {
    const paramObj = {
        sortKey: params.sort.key || null,
        sortDir: params.sort.dir || null,
        filterKey: params.filter.key || null,
        filterValue: params.filter.value || null,
        fic: params.fic || null
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

const parseLocalStorage = () => {
    const lsObjectString = localStorage.getItem(LOCAL_STORAGE_KEY) || "{}";
    const lsObject = JSON.parse(lsObjectString);

    console.log("localstorage", lsObject);

    return {
        pins: lsObject?.pins || []
    };
};

const serializeLocalStorage = params => {
    const lsObject = {
        pins: params?.pins || []
    };

    const lsObjectString = JSON.stringify(lsObject);

    localStorage.setItem(LOCAL_STORAGE_KEY, lsObjectString);
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
            const text = encodeURI((cell.textContent || "").trim().toLowerCase());
            const pattern = value.trim().toLowerCase();

            const isMatch = ["characters", "genre"].includes(key) ? text.includes(pattern) : text === pattern;

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
            const sortValue = cell.getAttribute("data-sort-value");

            return {
                index,
                value: ((sortValue ?? cell.textContent) || "").trim(),
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

const pinTableRows = (localStorageParams) => {
    const table = getTableElement();
    const tbody = table.querySelector("tbody");
    const rows = Array.from(tbody.querySelectorAll("tr"));

    const pins = localStorageParams.pins || [];

    console.log("pins", pins);

    rows.forEach(tr => {
        const rowKey = tr.getAttribute("data-key");
        if (!pins.includes(rowKey)) return;

        const icon = tr.querySelector(".icon.pin");
        icon.classList.add("active");

        tbody.prepend(tr.cloneNode(true));
        tr.remove();
    });
};

const parsePermalink = params => {
    if (!params.fic) return;
    openFile(params.fic);
};

//TODO: niceify read modal
const init = () => {
    const localStorageParams = parseLocalStorage();
    const metadata = parseTableMetadata();
    const params = parseQS();

    filterTableRows(params, metadata);
    sortTableRows(params, metadata);
    pinTableRows(localStorageParams);
    parsePermalink(params);
};
