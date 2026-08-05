// ---------- Modal ----------
function openModal(id) {
  document.getElementById(id)?.classList.remove('hidden');
}
function closeModal(id) {
  document.getElementById(id)?.classList.add('hidden');
}
function confirmDelete(message) {
  return confirm(message);
}
document.addEventListener('click', (e) => {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.add('hidden');
  }
});
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay:not(.hidden)').forEach((el) => el.classList.add('hidden'));
    closeAllCustomSelects();
  }
});

// ---------- Custom Select Popup ----------
function toggleCustomSelect(popupId, triggerId) {
  const popup = document.getElementById(popupId);
  const isHidden = popup.classList.contains('hidden');
  closeAllCustomSelects();
  if (isHidden) {
    popup.classList.remove('hidden');
    popup.querySelector('.custom-select-search')?.focus();
  }
}

function closeAllCustomSelects() {
  document.querySelectorAll('.custom-select-popup').forEach((el) => el.classList.add('hidden'));
}

function pilihBarang(optEl, inputId, labelId, popupId, hintId) {
  document.getElementById(inputId).value = optEl.dataset.value;
  document.getElementById(labelId).textContent = optEl.dataset.label;
  if (hintId) {
    const stok = optEl.dataset.stok || '0';
    const satuan = optEl.dataset.satuan || '';
    document.getElementById(hintId).textContent = `Stok saat ini: ${stok} ${satuan}`;
  }
  document.getElementById(popupId).classList.add('hidden');
}

function filterSelect(input, popupId) {
  const q = input.value.toLowerCase();
  document.querySelectorAll(`#${popupId} .custom-select-option`).forEach((opt) => {
    const text = opt.textContent.toLowerCase();
    opt.classList.toggle('hidden', q !== '' && !text.includes(q));
  });
}

// Tutup semua custom select kalau klik di luar
document.addEventListener('click', (e) => {
  if (!e.target.closest('.custom-select-wrap')) {
    closeAllCustomSelects();
  }
});
