/**
 * StudentHub Portal - Core Interactivity Script (Practicals 4 & 5)
 * Handles:
 * - Theme Switcher (Dark/Light) with localStorage persistence
 * - Mobile Navigation Hamburger Toggle
 * - Collapsible FAQ Accordion
 * - Modal Dialog System
 * - Hero Content Slider / Carousel
 * - Dismissible Notification Banners
 * - Registration Form Validation with Regex & Real-time Feedback
 * - Password Strength Meter
 * - Custom Canvas CAPTCHA generator
 * - Toast Notification Helper
 */

document.addEventListener('DOMContentLoaded', () => {
  initTheme();
  initMobileNav();
  initNotificationBanner();
  initHeroSlider();
  initFaqAccordion();
  initModalSystem();
  initRegistrationValidation();
  initCanvasCaptcha();
});

/* ==========================================================================
   1. Theme Switcher (Dark / Light Mode) with localStorage (Practical 4)
   ========================================================================== */
function initTheme() {
  const themeToggleBtn = document.getElementById('themeToggleBtn');
  const savedTheme = localStorage.getItem('studenthub_theme') || 'light';
  
  applyTheme(savedTheme);

  if (themeToggleBtn) {
    themeToggleBtn.addEventListener('click', () => {
      const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
      const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
      applyTheme(newTheme);
      localStorage.setItem('studenthub_theme', newTheme);
      showToast(`Switched to ${newTheme.toUpperCase()} mode`, 'info');
    });
  }
}

function applyTheme(theme) {
  document.documentElement.setAttribute('data-theme', theme);
  const themeToggleBtn = document.getElementById('themeToggleBtn');
  if (themeToggleBtn) {
    if (theme === 'dark') {
      themeToggleBtn.innerHTML = '☀️ Light Mode';
      themeToggleBtn.setAttribute('aria-label', 'Switch to light theme');
    } else {
      themeToggleBtn.innerHTML = '🌙 Dark Mode';
      themeToggleBtn.setAttribute('aria-label', 'Switch to dark theme');
    }
  }
}

/* ==========================================================================
   2. Mobile Hamburger Navigation (Practical 4)
   ========================================================================== */
function initMobileNav() {
  const menuToggleBtn = document.getElementById('menuToggleBtn');
  const navLinks = document.getElementById('navLinks');

  if (menuToggleBtn && navLinks) {
    menuToggleBtn.addEventListener('click', () => {
      const isOpen = navLinks.classList.toggle('open');
      menuToggleBtn.setAttribute('aria-expanded', isOpen);
      menuToggleBtn.innerHTML = isOpen ? '✕' : '☰';
    });

    // Close on link click for mobile
    navLinks.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => {
        if (window.innerWidth <= 768) {
          navLinks.classList.remove('open');
          menuToggleBtn.innerHTML = '☰';
          menuToggleBtn.setAttribute('aria-expanded', 'false');
        }
      });
    });
  }
}

/* ==========================================================================
   3. Notification Banner (Practical 4)
   ========================================================================== */
function initNotificationBanner() {
  const banner = document.getElementById('notificationBanner');
  const closeBtn = document.getElementById('closeBannerBtn');

  if (banner && closeBtn) {
    const isDismissed = localStorage.getItem('studenthub_banner_dismissed');
    if (isDismissed === 'true') {
      banner.classList.add('hidden');
    }

    closeBtn.addEventListener('click', () => {
      banner.classList.add('hidden');
      localStorage.setItem('studenthub_banner_dismissed', 'true');
    });
  }
}

/* ==========================================================================
   4. Hero Content Slider / Carousel (Practical 4)
   ========================================================================== */
function initHeroSlider() {
  const sliderWrapper = document.getElementById('sliderWrapper');
  const slides = document.querySelectorAll('.slide');
  const prevBtn = document.getElementById('sliderPrevBtn');
  const nextBtn = document.getElementById('sliderNextBtn');
  const indicatorsContainer = document.getElementById('sliderIndicators');

  if (!sliderWrapper || slides.length === 0) return;

  let currentIndex = 0;
  let autoSlideTimer = null;

  // Generate indicator dots
  if (indicatorsContainer) {
    indicatorsContainer.innerHTML = '';
    slides.forEach((_, idx) => {
      const dot = document.createElement('div');
      dot.className = `slider-dot ${idx === 0 ? 'active' : ''}`;
      dot.setAttribute('role', 'button');
      dot.setAttribute('aria-label', `Slide ${idx + 1}`);
      dot.addEventListener('click', () => {
        goToSlide(idx);
        restartAutoSlide();
      });
      indicatorsContainer.appendChild(dot);
    });
  }

  function updateSlidePosition() {
    sliderWrapper.style.transform = `translateX(-${currentIndex * 100}%)`;
    const dots = document.querySelectorAll('.slider-dot');
    dots.forEach((dot, idx) => {
      dot.classList.toggle('active', idx === currentIndex);
    });
  }

  function goToSlide(index) {
    if (index < 0) {
      currentIndex = slides.length - 1;
    } else if (index >= slides.length) {
      currentIndex = 0;
    } else {
      currentIndex = index;
    }
    updateSlidePosition();
  }

  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      goToSlide(currentIndex - 1);
      restartAutoSlide();
    });
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      goToSlide(currentIndex + 1);
      restartAutoSlide();
    });
  }

  function startAutoSlide() {
    autoSlideTimer = setInterval(() => {
      goToSlide(currentIndex + 1);
    }, 5000);
  }

  function restartAutoSlide() {
    clearInterval(autoSlideTimer);
    startAutoSlide();
  }

  startAutoSlide();
}

/* ==========================================================================
   5. Collapsible FAQ Accordion (Practical 4)
   ========================================================================== */
function initFaqAccordion() {
  const faqItems = document.querySelectorAll('.faq-item');

  faqItems.forEach(item => {
    const questionBtn = item.querySelector('.faq-question-btn');
    const answerPane = item.querySelector('.faq-answer-pane');

    if (questionBtn && answerPane) {
      questionBtn.addEventListener('click', () => {
        const isActive = item.classList.contains('active');

        // Close other items (accordion behavior)
        faqItems.forEach(otherItem => {
          if (otherItem !== item) {
            otherItem.classList.remove('active');
            const otherPane = otherItem.querySelector('.faq-answer-pane');
            if (otherPane) otherPane.style.maxHeight = null;
          }
        });

        if (!isActive) {
          item.classList.add('active');
          answerPane.style.maxHeight = answerPane.scrollHeight + 30 + 'px';
        } else {
          item.classList.remove('active');
          answerPane.style.maxHeight = null;
        }
      });
    }
  });
}

/* ==========================================================================
   6. Modal Dialog System (Practical 4 & 13)
   ========================================================================== */
function initModalSystem() {
  document.querySelectorAll('[data-modal-target]').forEach(trigger => {
    trigger.addEventListener('click', (e) => {
      e.preventDefault();
      const modalId = trigger.getAttribute('data-modal-target');
      openModal(modalId);
    });
  });

  document.querySelectorAll('.modal-backdrop').forEach(modal => {
    // Backdrop click to dismiss
    modal.addEventListener('click', (e) => {
      if (e.target === modal) {
        closeModal(modal.id);
      }
    });

    // Close buttons (X icon and Close button)
    modal.querySelectorAll('.modal-close-btn, [data-modal-close]').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        closeModal(modal.id);
      });
    });
  });

  // ESC key listener
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      const activeModal = document.querySelector('.modal-backdrop.active, .modal-backdrop.open, .modal-backdrop[style*="display: flex"]');
      if (activeModal) closeModal(activeModal.id);
    }
  });
}

window.openModal = function(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.add('active');
    modal.classList.add('open');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  }
};

window.closeModal = function(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.remove('active');
    modal.classList.remove('open');
    modal.style.display = 'none';
    document.body.style.overflow = '';
  }
};

/* ==========================================================================
   7. Registration Form Validation with Regex (Practical 5)
   ========================================================================== */
function initRegistrationValidation() {
  const form = document.getElementById('registrationForm');
  if (!form) return;

  const nameInput = document.getElementById('regName');
  const emailInput = document.getElementById('regEmail');
  const phoneInput = document.getElementById('regPhone');
  const passwordInput = document.getElementById('regPassword');
  const confirmPasswordInput = document.getElementById('regConfirmPassword');
  const courseSelect = document.getElementById('regCourse');
  const termsCheckbox = document.getElementById('regTerms');
  const captchaInput = document.getElementById('regCaptcha');

  // Regex Patterns
  const nameRegex = /^[A-Za-z\s]{3,50}$/;
  const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
  const phoneRegex = /^[6-9]\d{9}$/;
  // At least 8 chars, 1 uppercase, 1 lowercase, 1 number, 1 special char
  const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

  // Real-time Listeners
  if (nameInput) {
    nameInput.addEventListener('input', () => validateField(nameInput, nameRegex, 'Name must be at least 3 letters without numbers.'));
  }
  if (emailInput) {
    emailInput.addEventListener('input', () => validateField(emailInput, emailRegex, 'Enter a valid email address (e.g., student@charusat.edu.in).'));
  }
  if (phoneInput) {
    phoneInput.addEventListener('input', () => validateField(phoneInput, phoneRegex, 'Enter a valid 10-digit Indian mobile number.'));
  }
  if (passwordInput) {
    passwordInput.addEventListener('input', () => {
      validatePasswordStrength(passwordInput.value);
      validateField(passwordInput, passwordRegex, 'Password must be 8+ chars with uppercase, lowercase, number, and special character.');
      if (confirmPasswordInput && confirmPasswordInput.value) {
        validateConfirmPassword();
      }
    });
  }
  if (confirmPasswordInput) {
    confirmPasswordInput.addEventListener('input', validateConfirmPassword);
  }

  function validateField(input, regex, errorMessage) {
    const errorSpan = document.getElementById(`${input.id}Error`);
    const val = input.value.trim();
    if (!val) {
      setError(input, errorSpan, 'This field is required.');
      return false;
    }
    if (!regex.test(val)) {
      setError(input, errorSpan, errorMessage);
      return false;
    }
    setSuccess(input, errorSpan);
    return true;
  }

  function validateConfirmPassword() {
    const errorSpan = document.getElementById('regConfirmPasswordError');
    if (!confirmPasswordInput.value) {
      setError(confirmPasswordInput, errorSpan, 'Please confirm your password.');
      return false;
    }
    if (confirmPasswordInput.value !== passwordInput.value) {
      setError(confirmPasswordInput, errorSpan, 'Passwords do not match.');
      return false;
    }
    setSuccess(confirmPasswordInput, errorSpan);
    return true;
  }

  function setError(input, errorSpan, msg) {
    input.classList.add('is-invalid');
    input.classList.remove('is-valid');
    if (errorSpan) errorSpan.textContent = msg;
  }

  function setSuccess(input, errorSpan) {
    input.classList.remove('is-invalid');
    input.classList.add('is-valid');
    if (errorSpan) errorSpan.textContent = '';
  }

  // Password Strength Meter Logic
  function validatePasswordStrength(pwd) {
    const meterFill = document.getElementById('passwordStrengthFill');
    const meterText = document.getElementById('passwordStrengthText');
    if (!meterFill || !meterText) return;

    let score = 0;
    if (pwd.length >= 8) score++;
    if (/[A-Z]/.test(pwd)) score++;
    if (/[a-z]/.test(pwd)) score++;
    if (/\d/.test(pwd)) score++;
    if (/[@$!%*?&]/.test(pwd)) score++;

    if (pwd.length === 0) {
      meterFill.style.width = '0%';
      meterFill.style.backgroundColor = 'transparent';
      meterText.textContent = 'Enter password to see strength';
      meterText.style.color = 'var(--text-muted)';
    } else if (score <= 2) {
      meterFill.style.width = '33%';
      meterFill.style.backgroundColor = 'var(--danger)';
      meterText.textContent = 'Weak password';
      meterText.style.color = 'var(--danger)';
    } else if (score <= 4) {
      meterFill.style.width = '66%';
      meterFill.style.backgroundColor = 'var(--warning)';
      meterText.textContent = 'Medium password';
      meterText.style.color = 'var(--warning)';
    } else {
      meterFill.style.width = '100%';
      meterFill.style.backgroundColor = 'var(--success)';
      meterText.textContent = 'Strong password!';
      meterText.style.color = 'var(--success)';
    }
  }

  // Submit Handler
  form.addEventListener('submit', (e) => {
    let isValid = true;

    if (!validateField(nameInput, nameRegex, 'Name must be at least 3 letters.')) isValid = false;
    if (!validateField(emailInput, emailRegex, 'Enter a valid email address.')) isValid = false;
    if (!validateField(phoneInput, phoneRegex, 'Enter a valid 10-digit mobile number.')) isValid = false;
    if (!validateField(passwordInput, passwordRegex, 'Password must meet complexity requirements.')) isValid = false;
    if (!validateConfirmPassword()) isValid = false;

    // Course
    const courseError = document.getElementById('regCourseError');
    if (courseSelect && !courseSelect.value) {
      courseSelect.classList.add('is-invalid');
      if (courseError) courseError.textContent = 'Please select a course.';
      isValid = false;
    } else if (courseSelect) {
      courseSelect.classList.remove('is-invalid');
      if (courseError) courseError.textContent = '';
    }

    // Gender radio check
    const genderSelected = form.querySelector('input[name="gender"]:checked');
    const genderError = document.getElementById('regGenderError');
    if (!genderSelected) {
      if (genderError) genderError.textContent = 'Please select your gender.';
      isValid = false;
    } else if (genderError) {
      genderError.textContent = '';
    }

    // Terms
    const termsError = document.getElementById('regTermsError');
    if (termsCheckbox && !termsCheckbox.checked) {
      if (termsError) termsError.textContent = 'You must accept the terms and conditions.';
      isValid = false;
    } else if (termsError) {
      termsError.textContent = '';
    }

    // Canvas CAPTCHA check
    if (captchaInput) {
      const captchaError = document.getElementById('regCaptchaError');
      if (captchaInput.value.toUpperCase() !== window.currentCaptchaCode) {
        if (captchaError) captchaError.textContent = 'Incorrect CAPTCHA code. Please try again.';
        captchaInput.classList.add('is-invalid');
        isValid = false;
        generateNewCaptcha();
      } else {
        captchaInput.classList.remove('is-invalid');
        if (captchaError) captchaError.textContent = '';
      }
    }

    if (!isValid) {
      e.preventDefault();
      showToast('Please fix the errors in the form before submitting.', 'error');
    } else {
      // If client-side only mode, show success modal
      if (form.getAttribute('action') === '#' || !form.getAttribute('action')) {
        e.preventDefault();
        showToast('Registration successful! Redirecting to login...', 'success');
        setTimeout(() => {
          window.location.href = 'login.html';
        }, 1500);
      }
    }
  });
}

/* ==========================================================================
   8. Custom Canvas CAPTCHA Generator (Practical 5 Advanced Extension)
   ========================================================================== */
function initCanvasCaptcha() {
  const canvas = document.getElementById('captchaCanvas');
  const refreshBtn = document.getElementById('captchaRefreshBtn');

  if (canvas) {
    generateNewCaptcha();
    if (refreshBtn) {
      refreshBtn.addEventListener('click', (e) => {
        e.preventDefault();
        generateNewCaptcha();
      });
    }
  }
}

function generateNewCaptcha() {
  const canvas = document.getElementById('captchaCanvas');
  if (!canvas) return;

  const ctx = canvas.getContext('2d');
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
  let code = '';
  for (let i = 0; i < 5; i++) {
    code += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  window.currentCaptchaCode = code;

  // Clear canvas
  ctx.clearRect(0, 0, canvas.width, canvas.height);

  // Background noise lines
  ctx.fillStyle = '#f8fafc';
  ctx.fillRect(0, 0, canvas.width, canvas.height);

  for (let i = 0; i < 4; i++) {
    ctx.strokeStyle = `rgba(79, 70, 229, ${Math.random() * 0.4 + 0.1})`;
    ctx.lineWidth = 1.5;
    ctx.beginPath();
    ctx.moveTo(Math.random() * canvas.width, Math.random() * canvas.height);
    ctx.lineTo(Math.random() * canvas.width, Math.random() * canvas.height);
    ctx.stroke();
  }

  // Draw characters with slight rotation
  ctx.font = 'bold 22px "Outfit", sans-serif';
  for (let i = 0; i < code.length; i++) {
    ctx.fillStyle = i % 2 === 0 ? '#4f46e5' : '#0f172a';
    ctx.save();
    ctx.translate(20 + i * 22, 28);
    ctx.rotate((Math.random() - 0.5) * 0.35);
    ctx.fillText(code[i], 0, 0);
    ctx.restore();
  }
}

/* ==========================================================================
   9. Global Toast Notification Helper
   ========================================================================== */
window.showToast = function(message, type = 'info') {
  let toastContainer = document.getElementById('toastContainer');
  if (!toastContainer) {
    toastContainer = document.createElement('div');
    toastContainer.id = 'toastContainer';
    toastContainer.className = 'toast-container';
    document.body.appendChild(toastContainer);
  }

  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.innerHTML = `
    <span>${message}</span>
    <button style="background:none;border:none;cursor:pointer;font-weight:bold;margin-left:12px;" onclick="this.parentElement.remove()">✕</button>
  `;

  toastContainer.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transition = 'opacity 0.3s ease';
    setTimeout(() => toast.remove(), 300);
  }, 4000);
};
