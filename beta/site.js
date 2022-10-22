const openFile = fileId => {
    const portal = document.querySelector(".portal");
    portal.setAttribute("src", `static/${fileId}.html`);

    openModal();
};

const openModal = () => {
    const backdrop = document.querySelector(".backdrop");
    const modal = document.querySelector(".modal");
    const table = document.querySelector("table");
    const body = document.querySelector("body");

    body.style.overflowY = "hidden";
    table.style.filter = "blur(4px)";
    backdrop.style.display = "block";
    modal.style.display = "block";

    backdrop.addEventListener("click", closeModal);
};

const closeModal = () => {
    const backdrop = document.querySelector(".backdrop");
    const modal = document.querySelector(".modal");
    const portal = document.querySelector(".portal");
    const table = document.querySelector("table");
    const body = document.querySelector("body");

    body.style.overflowY = "auto";
    table.style.filter = "none";
    backdrop.style.display = "none";
    modal.style.display = "none";
    portal.setAttribute("src", "");

    backdrop.removeEventListener("click", closeModal);
};

const parseQS = () => {const href = document.location.href;
    const [, paramsStr] = href.split('?');
    const params = (paramsStr || "").split('&');

    console.log(params)
};

const init = () => {
    parseQS();

    //TODO: filter table rows
    //TODO: sort table rows
};
