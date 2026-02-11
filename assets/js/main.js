// Main JavaScript - Nusantara Rental Car

// ============ Smooth Scroll for Anchor Links ============
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
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

// ============ Auto-dismiss Alerts ============
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000); // 5 seconds
    });
});

// ============ Loading State for Forms ============
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const submitBtn = this.querySelector('button[type="submit"]');
        if (submitBtn && !submitBtn.disabled) {
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...';
            
            // Re-enable button after 10 seconds as fallback (in case of errors)
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }, 10000);
        }
    });
});

// ============ Fade-in Animation on Scroll ============
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('fade-in');
        }
    });
}, observerOptions);

// Observe cards and feature sections
document.querySelectorAll('.card, .feature-card').forEach(el => {
    observer.observe(el);
});

// ============ Form Validation Enhancement ============
document.querySelectorAll('input[required], textarea[required], select[required]').forEach(input => {
    input.addEventListener('invalid', function(e) {
        e.preventDefault();
        this.classList.add('is-invalid');
    });
    
    input.addEventListener('input', function() {
        if (this.validity.valid) {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        }
    });
});

// ============ Number Input Validation ============
document.querySelectorAll('input[type="number"]').forEach(input => {
    input.addEventListener('keypress', function(e) {
        // Allow only numbers and specific keys
        if (e.key && !/[0-9]/.test(e.key) && e.key !== 'Backspace' && e.key !== 'Delete' && e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') {
            e.preventDefault();
        }
    });
});

// ============ Image Preview for File Uploads ============
document.querySelectorAll('input[type="file"][accept*="image"]').forEach(input => {
    input.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                const preview = document.getElementById('imagePreview') || createPreviewElement();
                preview.src = event.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });
});

function createPreviewElement() {
    const preview = document.createElement('img');
    preview.id = 'imagePreview';
    preview.style.maxWidth = '200px';
    preview.style.marginTop = '10px';
    preview.style.borderRadius = '8px';
    preview.style.display = 'none';
    const fileInput = document.querySelector('input[type="file"][accept*="image"]');
    if (fileInput && fileInput.parentNode) {
        fileInput.parentNode.appendChild(preview);
    }
    return preview;
}

// ============ Tooltip Initialization ============
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// ============ Copy to Clipboard Utility ============
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Copied to clipboard!', 'success');
    }).catch(err => {
        console.error('Failed to copy:', err);
    });
}

// ============ Toast Notification Helper ============
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    
    const toastEl = document.createElement('div');
    toastEl.className = `toast align-items-center text-white bg-${type} border-0`;
    toastEl.setAttribute('role', 'alert');
    toastEl.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    toastContainer.appendChild(toastEl);
    const toast = new bootstrap.Toast(toastEl);
    toast.show();
    
    toastEl.addEventListener('hidden.bs.toast', () => {
        toastEl.remove();
    });
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}

// ============ Confirm Dialog Enhancement ============
window.confirmDelete = function(message) {
    return confirm(message || 'Are you sure you want to delete this item? This action cannot be undone.');
};

// ============ Back to Top Button ============
const backToTopButton = document.createElement('button');
backToTopButton.innerHTML = '<i class="fas fa-arrow-up"></i>';
backToTopButton.className = 'btn btn-primary btn-back-to-top';
backToTopButton.style.cssText = `
    position: fixed;
    bottom: 20px;
    right: 20px;
    display: none;
    z-index: 999;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    padding: 0;
    box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
`;

document.body.appendChild(backToTopButton);

window.addEventListener('scroll', () => {
    if (window.pageYOffset > 300) {
        backToTopButton.style.display = 'block';
    } else {
        backToTopButton.style.display = 'none';
    }
});

backToTopButton.addEventListener('click', () => {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
});

// ============ Console Welcome Message ============
console.log('%c🚗 Nusantara Rental Car', 'color: #667eea; font-size: 20px; font-weight: bold;');
console.log('%cWelcome to our car rental platform!', 'color: #764ba2; font-size: 14px;');
