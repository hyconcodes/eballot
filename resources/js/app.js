document.addEventListener('DOMContentLoaded', () => {
  const btn = document.getElementById('hamburger')
  const panel = document.getElementById('mobile-menu')
  const backdrop = document.getElementById('mobile-menu-backdrop')
  const openIcon = document.getElementById('hamburger-open-icon')
  const closeIcon = document.getElementById('hamburger-close-icon')
  if (!btn || !panel || !backdrop || !openIcon || !closeIcon) return
  const setState = (open) => {
    btn.setAttribute('aria-expanded', open ? 'true' : 'false')
    panel.classList.toggle('hidden', !open)
    backdrop.classList.toggle('hidden', !open)
    openIcon.classList.toggle('hidden', open)
    closeIcon.classList.toggle('hidden', !open)
    document.body.classList.toggle('overflow-hidden', open)
  }
  btn.addEventListener('click', () => {
    const open = btn.getAttribute('aria-expanded') !== 'true'
    setState(open)
  })
  backdrop.addEventListener('click', () => setState(false))
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') setState(false)
  })
  panel.querySelectorAll('a').forEach((a) => {
    a.addEventListener('click', () => setState(false))
  })
})

import { Chart, registerables } from 'chart.js'
Chart.register(...registerables)
window.Chart = Chart
