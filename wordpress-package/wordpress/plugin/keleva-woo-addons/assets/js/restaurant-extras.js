(() => {
  const picker = document.querySelector('[data-keleva-sauce-picker]');
  if (!picker) return;

  const inputs = Array.from(picker.querySelectorAll('input[type="checkbox"]'));
  const max = Number(picker.dataset.maxSauces || 2);
  const status = picker.querySelector('.keleva-sauce-picker__status');
  const update = () => {
    const selected = inputs.filter((input) => input.checked);
    const isAtLimit = selected.length >= max;
    inputs.forEach((input) => {
      input.disabled = isAtLimit && !input.checked;
    });
    if (status) {
      status.textContent = `${selected.length} / ${max} sauce${max > 1 ? 's' : ''} sélectionnée${selected.length > 1 ? 's' : ''}.`;
    }
  };
  inputs.forEach((input) => input.addEventListener('change', update));
  update();
})();
