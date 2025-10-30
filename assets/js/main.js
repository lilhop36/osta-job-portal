/**
 * Main JavaScript for OSTA Job Portal
 * Handles form validation, file upload feedback, and character counting
 */

// Check for required browser features
const hasRequiredFeatures = () => {
    return 'querySelector' in document && 'addEventListener' in window;
};

// Initialize all components
const initApp = () => {
    if (!hasRequiredFeatures()) {
        console.warn('This browser does not support all required features. Some functionality may be limited.');
        return;
    }

    // Initialize Bootstrap components
    initBootstrapComponents();
    
    // Initialize other components
    initFormValidation();
    initFileUploads();
    initCharacterCounters();
};

// Form validation with better browser support
const initFormValidation = () => {
    const forms = document.querySelectorAll('.needs-validation');
    if (!forms.length) return;

    const isFormValid = (form) => {
        if (typeof form.checkValidity === 'function') {
            return form.checkValidity();
        }
        // Fallback for older browsers
        return Array.from(form.elements).every(element => {
            if (!element.checkValidity) return true;
            return element.checkValidity();
        });
    };

    forms.forEach(form => {
        form.setAttribute('novalidate', 'true');
        
        form.addEventListener('submit', function(event) {
            if (!isFormValid(form)) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
};

// Enhanced file upload handling
const initFileUploads = () => {
    const fileInputs = document.querySelectorAll('.custom-file-input');
    if (!fileInputs.length) return;

    const getFileName = (input) => {
        try {
            return input.files && input.files[0] ? input.files[0].name : 'No file chosen';
        } catch (e) {
            console.error('Error accessing file input:', e);
            return 'No file chosen';
        }
    };

    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const label = this.nextElementSibling;
            if (label && label.classList.contains('custom-file-label')) {
                label.textContent = getFileName(this);
                
                // Optional: Add ARIA attributes for better accessibility
                const fileName = getFileName(this);
                const ariaLabel = fileName === 'No file chosen' ? 'No file selected' : `Selected file: ${fileName}`;
                this.setAttribute('aria-label', ariaLabel);
            }
        });

        // Trigger change event if file is pre-selected (useful for form reloads)
        if (input.files && input.files.length > 0) {
            input.dispatchEvent(new Event('change'));
        }
    });
};

// Improved character counter with cleanup
const initCharacterCounters = () => {
    const textareas = document.querySelectorAll('textarea[maxlength]');
    if (!textareas.length) return;

    const counters = new Map();

    const createCounterElement = (textarea) => {
        const counter = document.createElement('small');
        counter.className = 'form-text text-muted float-end';
        counter.setAttribute('aria-live', 'polite');
        return counter;
    };

    const updateCounter = (textarea, counter) => {
        try {
            const maxLength = parseInt(textarea.getAttribute('maxlength'), 10) || 0;
            const remaining = maxLength - (textarea.value ? textarea.value.length : 0);
            counter.textContent = `${remaining} characters remaining`;
            
            // Optional: Add visual feedback when approaching limit
            const warningThreshold = Math.floor(maxLength * 0.1); // 10% of max length
            counter.classList.toggle('text-warning', remaining <= warningThreshold && remaining > 0);
            counter.classList.toggle('text-danger', remaining === 0);
        } catch (e) {
            console.error('Error updating character counter:', e);
        }
    };

    textareas.forEach(textarea => {
        const counter = createCounterElement(textarea);
        const parent = textarea.parentNode;
        
        if (parent) {
            parent.insertBefore(counter, textarea.nextSibling);
            
            const updateHandler = () => updateCounter(textarea, counter);
            
            // Store reference for cleanup
            counters.set(textarea, {
                counter,
                handler: updateHandler
            });
            
            // Add event listeners
            textarea.addEventListener('input', updateHandler);
            textarea.addEventListener('focus', updateHandler);
            
            // Initial update
            updateHandler();
        }
    });

    // Cleanup function (could be called when components are removed from DOM)
    return () => {
        counters.forEach(({ counter, handler }, textarea) => {
            textarea.removeEventListener('input', handler);
            textarea.removeEventListener('focus', handler);
            if (counter && counter.parentNode) {
                counter.parentNode.removeChild(counter);
            }
        });
        counters.clear();
    };
};

// Initialize Bootstrap components
const initBootstrapComponents = () => {
    // Initialize all dropdowns
    const dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    const dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl);
    });

    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
};

// Initialize when DOM is fully loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initApp);
} else {
    initApp();
}
