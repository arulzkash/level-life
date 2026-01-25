import twemoji from "twemoji"

function render(el, value) {
  const text = value ?? ""
  el.textContent = text

  el.innerHTML = twemoji.parse(el.textContent, {
    folder: "svg",
    ext: ".svg",
    base: "https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/",
    className: "twemoji",
  })
}

export default {
  mounted(el, binding) {
    render(el, binding.value)
  },
  updated(el, binding) {
    render(el, binding.value)
  },
}
