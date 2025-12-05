// Appointment Form Validation
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('appointmentForm');
  if (!form) return;

  const nomInput = document.getElementById('nom');
  const telephoneInput = document.getElementById('telephone');
  const emailInput = document.getElementById('email');
  const serviceTypeInput = document.getElementById('service_type');
  const dateSouhaiteeInput = document.getElementById('date_souhaitee');
  const heureSouhaiteeInput = document.getElementById('heure_souhaitee');
  const messageInput = document.getElementById('message');
  
  const nomError = document.getElementById('nom-error');
  const telephoneError = document.getElementById('telephone-error');
  const emailError = document.getElementById('email-error');
  const serviceTypeError = document.getElementById('service_type-error');
  const dateSouhaiteeError = document.getElementById('date_souhaitee-error');
  const heureSouhaiteeError = document.getElementById('heure_souhaitee-error');
  const messageError = document.getElementById('message-error');
  const formMessage = document.getElementById('form-message');

  // Set minimum date to today
  const today = new Date().toISOString().split('T')[0];
  dateSouhaiteeInput.setAttribute('min', today);

  // Email validation regex
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  // Phone validation regex (supports international format)
  const phoneRegex = /^[\+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,9}$/;

  // Clear error message
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
    if (value.length < 2) {
      showError(nomError, 'Le nom doit contenir au moins 2 caractères.');
      nomInput.style.borderColor = '#e74c3c';
      return false;
    }
    clearError(nomError);
    nomInput.style.borderColor = '#4AB0D9';
    return true;
  }

  // Validate phone
  function validatePhone() {
    const value = telephoneInput.value.trim();
    if (!value) {
      showError(telephoneError, 'Le numéro de téléphone est obligatoire.');
      telephoneInput.style.borderColor = '#e74c3c';
      return false;
    }
    if (!phoneRegex.test(value.replace(/\s/g, ''))) {
      showError(telephoneError, 'Veuillez entrer un numéro de téléphone valide.');
      telephoneInput.style.borderColor = '#e74c3c';
      return false;
    }
    clearError(telephoneError);
    telephoneInput.style.borderColor = '#4AB0D9';
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

  // Validate service type
  function validateServiceType() {
    const value = serviceTypeInput.value;
    if (!value) {
      showError(serviceTypeError, 'Veuillez sélectionner un type de service.');
      serviceTypeInput.style.borderColor = '#e74c3c';
      return false;
    }
    clearError(serviceTypeError);
    serviceTypeInput.style.borderColor = '#4AB0D9';
    return true;
  }

  // Validate date
  function validateDate() {
    const value = dateSouhaiteeInput.value;
    if (!value) {
      showError(dateSouhaiteeError, 'Veuillez sélectionner une date.');
      dateSouhaiteeInput.style.borderColor = '#e74c3c';
      return false;
    }
    const selectedDate = new Date(value);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    if (selectedDate < today) {
      showError(dateSouhaiteeError, 'La date ne peut pas être dans le passé.');
      dateSouhaiteeInput.style.borderColor = '#e74c3c';
      return false;
    }
    clearError(dateSouhaiteeError);
    dateSouhaiteeInput.style.borderColor = '#4AB0D9';
    return true;
  }

  // Validate time
  function validateTime() {
    const value = heureSouhaiteeInput.value;
    if (!value) {
      showError(heureSouhaiteeError, 'Veuillez sélectionner une heure.');
      heureSouhaiteeInput.style.borderColor = '#e74c3c';
      return false;
    }
    const [hours, minutes] = value.split(':').map(Number);
    if (hours < 8 || hours > 19 || (hours === 19 && minutes > 0)) {
      showError(heureSouhaiteeError, 'Les rendez-vous sont disponibles entre 08h00 et 19h00.');
      heureSouhaiteeInput.style.borderColor = '#e74c3c';
      return false;
    }
    clearError(heureSouhaiteeError);
    heureSouhaiteeInput.style.borderColor = '#4AB0D9';
    return true;
  }

  // Real-time validation
  nomInput.addEventListener('blur', validateName);
  nomInput.addEventListener('input', function() {
    if (nomInput.value.trim().length >= 2) {
      clearError(nomError);
      nomInput.style.borderColor = '#E3F6FD';
    }
  });

  telephoneInput.addEventListener('blur', validatePhone);
  telephoneInput.addEventListener('input', function() {
    if (telephoneInput.value.trim() && phoneRegex.test(telephoneInput.value.trim().replace(/\s/g, ''))) {
      clearError(telephoneError);
      telephoneInput.style.borderColor = '#E3F6FD';
    }
  });

  emailInput.addEventListener('blur', validateEmail);
  emailInput.addEventListener('input', function() {
    if (emailInput.value.trim() && emailRegex.test(emailInput.value.trim())) {
      clearError(emailError);
      emailInput.style.borderColor = '#E3F6FD';
    }
  });

  serviceTypeInput.addEventListener('change', function() {
    if (serviceTypeInput.value) {
      clearError(serviceTypeError);
      serviceTypeInput.style.borderColor = '#E3F6FD';
    }
  });

  dateSouhaiteeInput.addEventListener('change', validateDate);
  heureSouhaiteeInput.addEventListener('change', validateTime);

  // Form submission
  form.addEventListener('submit', function(e) {
    e.preventDefault();

    // Validate all fields
    const isNameValid = validateName();
    const isPhoneValid = validatePhone();
    const isEmailValid = validateEmail();
    const isServiceTypeValid = validateServiceType();
    const isDateValid = validateDate();
    const isTimeValid = validateTime();

    if (!isNameValid || !isPhoneValid || !isEmailValid || !isServiceTypeValid || !isDateValid || !isTimeValid) {
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
      formMessage.innerHTML = '✅ ' + decodeURIComponent(message || 'Votre demande de rendez-vous a bien été envoyée. Nous vous contacterons dans les plus brefs délais pour confirmer.');
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

