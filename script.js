const notifyForm = document.querySelector('#notify-form');
const emailInput = document.querySelector('#email');
const formMessage = document.querySelector('#form-message');

notifyForm.addEventListener('submit', (event) => {
  event.preventDefault();

  if (!emailInput.value) return;

  formMessage.textContent = 'You’re on the list. We’ll be in touch.';
  notifyForm.reset();
});
