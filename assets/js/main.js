document.addEventListener('DOMContentLoaded', function() {
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Mobile menu toggle
    const menuToggle = document.querySelector('.menu-toggle');
    const navLinks = document.querySelector('.nav-links');
    
    if (menuToggle && navLinks) {
        menuToggle.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            menuToggle.classList.toggle('active');
        });
    }

    // Header scroll effect
    const header = document.querySelector('.header');
    let lastScroll = 0;

    window.addEventListener('scroll', () => {
        const currentScroll = window.pageYOffset;

        if (currentScroll <= 0) {
            header.classList.remove('scroll-up');
            return;
        }

        if (currentScroll > lastScroll && !header.classList.contains('scroll-down')) {
            // Scrolling down
            header.classList.remove('scroll-up');
            header.classList.add('scroll-down');
        } else if (currentScroll < lastScroll && header.classList.contains('scroll-down')) {
            // Scrolling up
            header.classList.remove('scroll-down');
            header.classList.add('scroll-up');
        }

        lastScroll = currentScroll;
    });

    // Form animations
    const formInputs = document.querySelectorAll('.form-input');
    formInputs.forEach(input => {
        // Add focus effect
        input.addEventListener('focus', () => {
            input.parentElement.classList.add('focused');
        });

        // Remove focus effect if input is empty
        input.addEventListener('blur', () => {
            if (input.value.trim() === '') {
                input.parentElement.classList.remove('focused');
            }
        });

        // Check if input has value on page load
        if (input.value.trim() !== '') {
            input.parentElement.classList.add('focused');
        }
    });

    // Feature card hover effects
    const featureCards = document.querySelectorAll('.feature-card');
    featureCards.forEach(card => {
        card.addEventListener('mouseenter', () => {
            featureCards.forEach(c => c.classList.remove('active'));
            card.classList.add('active');
        });
    });

    // Notification system
    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;

        document.body.appendChild(notification);

        // Show notification
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);

        // Hide and remove notification
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }

    // Example usage of notification system
    window.showNotification = showNotification;

    // Add loading indicators to buttons
    const submitButtons = document.querySelectorAll('button[type="submit"]');
    submitButtons.forEach(button => {
        button.addEventListener('click', function() {
            const form = this.closest('form');
            if (form && form.checkValidity()) {
                this.classList.add('loading');
                // Reset button state after 2 seconds if form submission fails
                setTimeout(() => {
                    if (this.classList.contains('loading')) {
                        this.classList.remove('loading');
                    }
                }, 2000);
            }
        });
    });

    // Image lazy loading
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                observer.unobserve(img);
            }
        });
    });

    images.forEach(img => imageObserver.observe(img));

    // Form auto-save (for longer forms)
    const autoSaveForms = document.querySelectorAll('form[data-autosave]');
    autoSaveForms.forEach(form => {
        let timeout;
        const formInputs = form.querySelectorAll('input, textarea, select');
        
        formInputs.forEach(input => {
            input.addEventListener('input', () => {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    const formData = new FormData(form);
                    localStorage.setItem(`formData_${form.id}`, JSON.stringify(Object.fromEntries(formData)));
                    showNotification('Progress saved', 'info');
                }, 1000);
            });
        });

        // Restore form data on page load
        const savedData = localStorage.getItem(`formData_${form.id}`);
        if (savedData) {
            const data = JSON.parse(savedData);
            Object.entries(data).forEach(([name, value]) => {
                const input = form.querySelector(`[name="${name}"]`);
                if (input) {
                    input.value = value;
                    input.dispatchEvent(new Event('input'));
                }
            });
        }
    });
});
