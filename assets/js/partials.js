async function loadPartial(id, file) {
  const res = await fetch(file);
  document.getElementById(id).innerHTML = await res.text();

  if (id === "header") {
    highlightActiveLink();
  } else if (id === "footer") {
    setFooterGradientClass();
  }
}

function highlightActiveLink() {
  const currentPath = window.location.pathname.split("/").pop().split("?")[0] || "index.html";
  document.querySelectorAll("#header .nav-link").forEach(link => {
    const linkPath = link.getAttribute("href").split("/").pop();
    if (linkPath === currentPath) {
      link.classList.add("active");
    } else {
      link.classList.remove("active");
    }
  });
}

function setFooterGradientClass() {
  const footer = document.querySelector("#footer footer");
  if (!footer) return;

  const page = window.location.pathname.split("/").pop().toLowerCase();

  if (page === "about%20us.html") {
    footer.classList.add("pink");
   } else {
    footer.classList.add("green"); // default
  }
}

loadPartial("header", "header.html");
loadPartial("footer", "footer.html");
