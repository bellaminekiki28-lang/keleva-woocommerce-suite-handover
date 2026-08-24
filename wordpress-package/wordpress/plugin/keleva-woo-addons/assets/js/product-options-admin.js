(() => {
  const root = document.querySelector('#keleva-product-options-admin');
  if (!root) return;

  const groupsRoot = root.querySelector('[data-keleva-option-groups]');
  const output = root.querySelector('[data-keleva-option-groups-json]');
  const addGroup = root.querySelector('[data-keleva-add-option-group]');
  let groups = [];

  try { groups = JSON.parse(root.dataset.groups || '[]'); } catch { groups = []; }

  const slug = (value, fallback) => (String(value || '').trim().toLowerCase()
    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '') || fallback);

  const uniqueId = (prefix) => `${prefix}-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 6)}`;
  const optionTemplate = () => ({ id: uniqueId('option'), label: 'Nouvelle option', price: 0 });
  const groupTemplate = () => ({
    id: uniqueId('groupe'), label: 'Nouveau groupe', display: 'buttons', max: 1, required: false, options: [optionTemplate()],
  });

  const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (character) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;',
  })[character]);

  const read = () => {
    groups = [...groupsRoot.querySelectorAll('[data-keleva-admin-group]')].map((groupNode, groupIndex) => {
      const label = groupNode.querySelector('[data-field="label"]')?.value.trim() || `Groupe ${groupIndex + 1}`;
      const display = groupNode.querySelector('[data-field="display"]')?.value || 'buttons';
      const max = Math.max(1, Math.min(4, Number(groupNode.querySelector('[data-field="max"]')?.value || 1)));
      const idField = groupNode.querySelector('[data-field="id"]');
      if (!idField.value) idField.value = slug(label, `groupe-${groupIndex + 1}`);
      const options = [...groupNode.querySelectorAll('[data-keleva-admin-option]')].map((optionNode, optionIndex) => {
        const optionLabel = optionNode.querySelector('[data-option-field="label"]')?.value.trim() || `Option ${optionIndex + 1}`;
        const optionId = optionNode.querySelector('[data-option-field="id"]');
        if (!optionId.value) optionId.value = slug(optionLabel, `option-${optionIndex + 1}`);
        return {
          id: optionId.value,
          label: optionLabel,
          price: Math.max(0, Number(optionNode.querySelector('[data-option-field="price"]')?.value || 0)),
        };
      });
      return {
        id: idField.value,
        label,
        display,
        max: display === 'radio' ? 1 : Math.min(max, Math.max(1, options.length)),
        required: Boolean(groupNode.querySelector('[data-field="required"]')?.checked),
        options,
      };
    }).filter((group) => group.options.length);
    output.value = JSON.stringify(groups);
  };

  const render = () => {
    groupsRoot.innerHTML = groups.map((group, index) => {
      const radio = group.display === 'radio';
      return `<section class="keleva-options-admin-group" data-keleva-admin-group>
        <header><strong>Groupe ${index + 1}</strong><button type="button" class="button-link-delete" data-keleva-remove-group>Supprimer</button></header>
        <input type="hidden" data-field="id" value="${escapeHtml(group.id)}">
        <div class="keleva-options-admin-group__fields">
          <label>Libellé<input type="text" data-field="label" value="${escapeHtml(group.label)}" placeholder="Ex. Finition"></label>
          <label>Rendu<select data-field="display">
            <option value="buttons" ${group.display === 'buttons' ? 'selected' : ''}>Boutons</option>
            <option value="radio" ${radio ? 'selected' : ''}>Radio</option>
            <option value="checkbox" ${group.display === 'checkbox' ? 'selected' : ''}>Cases à cocher</option>
          </select></label>
          <label>Choix simultanés (1–4)<input type="number" min="1" max="4" data-field="max" value="${radio ? 1 : Math.max(1, Number(group.max || 1))}" ${radio ? 'disabled' : ''}></label>
          <label class="keleva-options-admin-group__check"><input type="checkbox" data-field="required" ${group.required ? 'checked' : ''}> Obligatoire</label>
        </div>
        <div class="keleva-options-admin-group__options" data-keleva-admin-options>${group.options.map((option, optionIndex) => `<div class="keleva-options-admin-option" data-keleva-admin-option>
          <input type="hidden" data-option-field="id" value="${escapeHtml(option.id)}">
          <label>Option ${optionIndex + 1}<input type="text" data-option-field="label" value="${escapeHtml(option.label)}"></label>
          <label>Supplément (€)<input type="number" min="0" step="0.01" data-option-field="price" value="${Number(option.price || 0)}"></label>
          <button type="button" class="button-link-delete" data-keleva-remove-option>Retirer</button>
        </div>`).join('')}</div>
        <p><button type="button" class="button" data-keleva-add-option>Ajouter une option</button></p>
      </section>`;
    }).join('') || '<p class="description">Aucun groupe d’options : ce produit garde le comportement WooCommerce standard.</p>';
    read();
  };

  root.addEventListener('input', () => read());
  root.addEventListener('change', (event) => {
    const target = event.target;
    if (target.matches('[data-field="display"]')) {
      const group = target.closest('[data-keleva-admin-group]');
      const max = group?.querySelector('[data-field="max"]');
      if (max) {
        max.disabled = target.value === 'radio';
        if (target.value === 'radio') max.value = '1';
      }
    }
    read();
  });
  root.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) return;
    if (target.matches('[data-keleva-add-option-group]')) {
      groups.push(groupTemplate());
      render();
      return;
    }
    const group = target.closest('[data-keleva-admin-group]');
    if (!group) return;
    if (target.matches('[data-keleva-remove-group]')) {
      groups.splice([...groupsRoot.children].indexOf(group), 1);
      render();
      return;
    }
    if (target.matches('[data-keleva-add-option]')) {
      read();
      const index = [...groupsRoot.children].indexOf(group);
      groups[index]?.options.push(optionTemplate());
      render();
      return;
    }
    const option = target.closest('[data-keleva-admin-option]');
    if (option && target.matches('[data-keleva-remove-option]')) {
      read();
      const groupIndex = [...groupsRoot.children].indexOf(group);
      const optionIndex = [...group.querySelectorAll('[data-keleva-admin-option]')].indexOf(option);
      groups[groupIndex]?.options.splice(optionIndex, 1);
      render();
    }
  });
  addGroup?.addEventListener('click', () => {
    groups.push(groupTemplate());
    render();
  });
  render();
})();
