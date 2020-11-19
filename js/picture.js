function show(element) {
  let el = element.currentTarget;
  if (el.classList.contains('post-picture')) {
    el.nextElementSibling.classList.toggle('show');
  } else {
    el.classList.toggle('show');
  }
}

for (let item of document.querySelectorAll(".post-picture, .full-post-picture")) {
  item.addEventListener("click", show);
}
