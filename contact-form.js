// Contact Form Validation
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('contactForm');
  if (!form) return;

  const nomInput = document.getElementById('nom');
  const emailInput = document.getElementById('email');
  const messageInput = document.getElementById('message');
  const nomError = document.getElementById('nom-error');
  const emailError = document.getElementById('email-error');
  const messageError = document.getElementById('message-error');
  const formMessage = document.getElementById('form-message');

  // Email validation regex
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  // Clear error messages
  function clearError(errorElement) {
    if (errorElement) {
      errorElement.textContent = '';
      errorElement.style.display = 'none';
    }
  }

  // Show error message
  function showError(errorElement, message) {
    if (errorElement) {
      errorElement.textContent = message;
      errorElement.style.display = 'block';
    }
  }

  // Validate name
  function validateName() {
    const value = nomInput.value.trim();
    if (!value) {
      showError(nomError, 'Le nom complet est obligatoire.');
      nomInput.style.borderColor = '#e74c3c';
      return false;
    }
    clearError(nomError);
    nomInput.style.borderColor = '#4AB0D9';
    return true;
  }

  // Validate email
  function validateEmail() {
    const value = emailInput.value.trim();
    if (!value) {
      showError(emailError, 'L\'e-mail est obligatoire.');
      emailInput.style.borderColor = '#e74c3c';
      return false;
    }
    if (!emailRegex.test(value)) {
      showError(emailError, 'Veuillez entrer une adresse e-mail valide.');
      emailInput.style.borderColor = '#e74c3c';
      return false;
    }
    clearError(emailError);
    emailInput.style.borderColor = '#4AB0D9';
    return true;
  }

  // Validate message
  function validateMessage() {
    const value = messageInput.value.trim();
    if (!value) {
      showError(messageError, 'Le message est obligatoire.');
      messageInput.style.borderColor = '#e74c3c';
      return false;
    }
    if (value.length < 10) {
      showError(messageError, 'Le message doit contenir au moins 10 caractères.');
      messageInput.style.borderColor = '#e74c3c';
      return false;
    }
    clearError(messageError);
    messageInput.style.borderColor = '#4AB0D9';
    return true;
  }

  // Real-time validation
  nomInput.addEventListener('blur', validateName);
  nomInput.addEventListener('input', function() {
    if (nomInput.value.trim()) {
      clearError(nomError);
      nomInput.style.borderColor = '#E3F6FD';
    }
  });

  emailInput.addEventListener('blur', validateEmail);
  emailInput.addEventListener('input', function() {
    if (emailInput.value.trim() && emailRegex.test(emailInput.value.trim())) {
      clearError(emailError);
      emailInput.style.borderColor = '#E3F6FD';
    }
  });

  messageInput.addEventListener('blur', validateMessage);
  messageInput.addEventListener('input', function() {
    if (messageInput.value.trim().length >= 10) {
      clearError(messageError);
      messageInput.style.borderColor = '#E3F6FD';
    }
  });

  // Form submission
  form.addEventListener('submit', function(e) {
    e.preventDefault();

    // Validate all fields
    const isNameValid = validateName();
    const isEmailValid = validateEmail();
    const isMessageValid = validateMessage();

    if (!isNameValid || !isEmailValid || !isMessageValid) {
      if (formMessage) formMessage.classList.add('hidden');
      return;
    }

    // If validation passes, submit the form
    form.submit();
  });

  // Check for URL parameters (success/error from PHP)
  const urlParams = new URLSearchParams(window.location.search);
  const status = urlParams.get('status');
  const message = urlParams.get('message');

  if (status && formMessage) {
    formMessage.classList.remove('hidden');
    if (status === 'success') {
      formMessage.className = 'form-message form-message-success';
      formMessage.innerHTML = '✅ ' + decodeURIComponent(message || 'Merci, votre message a bien été envoyé. Nous vous répondrons dans les plus brefs délais.');
      // Scroll to message
      formMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
      // Reset form on success
      form.reset();
    } else {
      formMessage.className = 'form-message form-message-error';
      formMessage.innerHTML = '❌ ' + decodeURIComponent(message || 'Une erreur est survenue. Veuillez réessayer.');
      // Scroll to message
      formMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }
});
