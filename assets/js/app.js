document.addEventListener('DOMContentLoaded', () => {
  const tabs = document.querySelectorAll('[data-tab]');
  const tabContents = document.querySelectorAll('[data-tab-content]');
  tabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      tabs.forEach((item) => item.classList.remove('active'));
      tabContents.forEach((content) => content.classList.add('hidden'));
      tab.classList.add('active');
      const target = tab.dataset.tab;
      const match = document.querySelector(`[data-tab-content="${target}"]`);
      if (match) {
        match.classList.remove('hidden');
      }
    });
  });

  document.querySelectorAll('[data-modal-open]').forEach((button) => {
    button.addEventListener('click', () => {
      const target = button.dataset.modalOpen;
      const modal = document.getElementById(target);
      if (modal) {
        modal.classList.add('active');
      }
    });
  });

  document.querySelectorAll('[data-modal-close]').forEach((button) => {
    button.addEventListener('click', () => {
      const target = button.dataset.modalClose;
      const modal = document.getElementById(target);
      if (modal) {
        modal.classList.remove('active');
      }
    });
  });

  document.querySelectorAll('[data-confirm]').forEach((button) => {
    button.addEventListener('click', (event) => {
      const message = button.dataset.confirm;
      if (!window.confirm(message || 'Are you sure?')) {
        event.preventDefault();
      }
    });
  });

  document.querySelectorAll('[data-edit-user]').forEach((button) => {
    button.addEventListener('click', () => {
      const userId = button.dataset.userId;
      const fullName = button.dataset.fullName;
      const email = button.dataset.email;
      const role = button.dataset.role;
      const managerId = button.dataset.managerId;

      const idField = document.getElementById('edit-user-id');
      const nameField = document.getElementById('edit-full-name');
      const emailField = document.getElementById('edit-email');
      const roleField = document.getElementById('edit-role');
      const managerField = document.getElementById('edit-manager');

      if (idField) idField.value = userId || '';
      if (nameField) nameField.value = fullName || '';
      if (emailField) emailField.value = email || '';
      if (roleField) roleField.value = role || 'EMPLOYEE';
      if (managerField) managerField.value = managerId || '';
    });
  });
});
