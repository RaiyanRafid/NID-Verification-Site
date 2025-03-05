document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('verificationForm');
    const phoneInput = document.getElementById('phone');
    const emailInput = document.getElementById('email');
    const documentTypeSelect = document.getElementById('documentType');
    const nidUpload = document.getElementById('nidUpload');
    const passportUpload = document.getElementById('passportUpload');
    const birthUpload = document.getElementById('birthUpload');
    const previewSection = document.getElementById('previewSection');
    const previewFront = document.getElementById('previewFront');
    const previewBack = document.getElementById('previewBack');
    const submitButton = form.querySelector('button[type="submit"]');
    const formInputs = form.querySelectorAll('input, textarea, select');

    // Enhanced phone number validation for Bangladesh
    function validatePhone(phone) {
        // Remove all spaces and dashes for validation
        const cleanPhone = phone.replace(/[\s-]/g, '');
        
        // Pattern for 11 digits starting with 01: 01XXXXXXXXX
        const localPattern = /^01\d{9}$/;
        
        // Pattern for 14 digits starting with +8801: +8801XXXXXXXXX
        const internationalPattern = /^\+8801\d{9}$/;
        
        return localPattern.test(cleanPhone) || internationalPattern.test(cleanPhone);
    }

    // Format phone number for display
    function formatPhoneNumber(phone) {
        // Remove all non-digit characters except +
        let cleaned = phone.replace(/[^\d+]/g, '');
        
        // If it's a local number (11 digits starting with 01)
        if (cleaned.match(/^01\d{9}$/)) {
            return cleaned;
        }
        
        // If it's an international number without +
        if (cleaned.match(/^8801\d{9}$/)) {
            return '+' + cleaned;
        }
        
        // If it's already in international format
        if (cleaned.match(/^\+8801\d{9}$/)) {
            return cleaned;
        }
        
        return phone;
    }

    // Enhanced email validation
    function validateEmail(email) {
        const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        return emailRegex.test(email);
    }

    // Function to reset file inputs
    function resetFileInputs() {
        document.querySelectorAll('.document-upload input[type="file"]').forEach(input => {
            input.value = '';
            input.removeAttribute('required');
            showSuccess(input);
        });
        previewFront.classList.add('d-none');
        previewBack.classList.add('d-none');
        previewSection.classList.add('d-none');
    }

    // Handle document type selection
    documentTypeSelect.addEventListener('change', function() {
        console.log('Document type changed:', this.value); // Debug log
        
        // Hide all upload sections first
        nidUpload.classList.add('d-none');
        passportUpload.classList.add('d-none');
        birthUpload.classList.add('d-none');
        
        // Remove required attribute from all file inputs
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.removeAttribute('required');
            input.value = '';
        });

        // Reset preview images
        previewFront.classList.add('d-none');
        previewBack.classList.add('d-none');
        previewSection.classList.add('d-none');

        // Show the appropriate upload section based on selection
        const selectedType = this.value;
        console.log('Selected type:', selectedType); // Debug log

        if (selectedType) {
            switch(selectedType) {
                case 'nid':
                    console.log('Showing NID upload section'); // Debug log
                    nidUpload.classList.remove('d-none');
                    document.getElementById('documentFront').setAttribute('required', '');
                    document.getElementById('documentBack').setAttribute('required', '');
                    break;
                case 'passport':
                    console.log('Showing passport upload section'); // Debug log
                    passportUpload.classList.remove('d-none');
                    document.getElementById('documentPassport').setAttribute('required', '');
                    break;
                case 'birth':
                    console.log('Showing birth certificate upload section'); // Debug log
                    birthUpload.classList.remove('d-none');
                    document.getElementById('documentBirth').setAttribute('required', '');
                    break;
            }
        }
    });

    // File type validation
    function validateFileType(file) {
        const allowedTypes = ['image/jpeg', 'image/png'];
        return allowedTypes.includes(file.type);
    }

    // File size validation (25MB limit)
    function validateFileSize(file) {
        const maxSize = 25 * 1024 * 1024; // 25MB
        return file.size <= maxSize;
    }

    // Show error message
    function showError(element, message) {
        let feedback;
        if (element.type === 'file') {
            feedback = element.closest('.mb-3').querySelector('.invalid-feedback');
        } else if (element.closest('.input-group')) {
            feedback = element.closest('.input-group').nextElementSibling;
        } else {
            feedback = element.nextElementSibling;
        }
        
        if (feedback && feedback.classList.contains('invalid-feedback')) {
            feedback.textContent = message;
        }
        element.classList.add('is-invalid');
        element.classList.remove('is-valid');
    }

    // Show success state
    function showSuccess(element) {
        let feedback;
        if (element.type === 'file') {
            feedback = element.closest('.mb-3').querySelector('.invalid-feedback');
        } else if (element.closest('.input-group')) {
            feedback = element.closest('.input-group').nextElementSibling;
        } else {
            feedback = element.nextElementSibling;
        }
        
        if (feedback && feedback.classList.contains('invalid-feedback')) {
            feedback.textContent = '';
        }
        element.classList.remove('is-invalid');
        element.classList.add('is-valid');
    }

    // Real-time phone validation and formatting
    phoneInput.addEventListener('input', function() {
        // Allow only numbers, +, and 8
        this.value = this.value.replace(/[^\d+8]/g, '');
        
        // Format the number
        this.value = formatPhoneNumber(this.value);
        
        if (validatePhone(this.value)) {
            showSuccess(this);
        } else {
            showError(this, 'Please enter a valid number (01XXXXXXXXX - 11 digits or +8801XXXXXXXXX - 14 digits)');
        }
    });

    // Real-time email validation
    emailInput.addEventListener('input', function() {
        if (validateEmail(this.value)) {
            showSuccess(this);
        } else {
            showError(this, 'Please enter a valid email address');
        }
    });

    // Real-time validation for required fields
    formInputs.forEach(input => {
        if (input.hasAttribute('required')) {
            input.addEventListener('input', function() {
                if (this.value.trim()) {
                    showSuccess(this);
                } else {
                    showError(this, 'This field is required');
                }
            });
        }
    });

    // Handle file upload and preview
    function handleFileUpload(input, previewElement) {
        const file = input.files[0];
        
        if (!file) {
            previewElement.classList.add('d-none');
            return;
        }

        // Validate file type
        if (!validateFileType(file)) {
            showError(input, 'Invalid file type. Please upload PNG or JPG files only.');
            input.value = '';
            previewElement.classList.add('d-none');
            return;
        }

        // Validate file size
        if (!validateFileSize(file)) {
            showError(input, 'File size must be less than 25MB');
            input.value = '';
            previewElement.classList.add('d-none');
            return;
        }

        showSuccess(input);

        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            previewElement.src = e.target.result;
            previewElement.classList.remove('d-none');
            previewSection.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    }

    // Add file upload handlers
    document.getElementById('documentFront').addEventListener('change', function() {
        handleFileUpload(this, previewFront);
    });

    document.getElementById('documentBack').addEventListener('change', function() {
        handleFileUpload(this, previewBack);
    });

    document.getElementById('documentPassport').addEventListener('change', function() {
        handleFileUpload(this, previewFront);
    });

    document.getElementById('documentBirth').addEventListener('change', function() {
        handleFileUpload(this, previewFront);
    });

    // Form submission handling
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        // Reset all error states
        document.querySelectorAll('.is-invalid').forEach(element => {
            element.classList.remove('is-invalid');
        });

        // Validate all required fields
        let isValid = true;
        formInputs.forEach(input => {
            if (input.hasAttribute('required') && !input.value.trim()) {
                showError(input, 'This field is required');
                isValid = false;
            }
        });

        // Validate document uploads based on selected type
        const documentType = documentTypeSelect.value;
        if (!documentType) {
            showError(documentTypeSelect, 'Please select a document type');
            isValid = false;
        } else {
            switch (documentType) {
                case 'nid':
                    const frontInput = document.getElementById('documentFront');
                    const backInput = document.getElementById('documentBack');
                    
                    if (!frontInput.files[0]) {
                        showError(frontInput, 'NID front side is required');
                        isValid = false;
                    }
                    if (!backInput.files[0]) {
                        showError(backInput, 'NID back side is required');
                        isValid = false;
                    }
                    break;
                    
                case 'passport':
                    const passportInput = document.getElementById('documentPassport');
                    if (!passportInput.files[0]) {
                        showError(passportInput, 'Passport front page is required');
                        isValid = false;
                    }
                    break;
                    
                case 'birth':
                    const birthInput = document.getElementById('documentBirth');
                    if (!birthInput.files[0]) {
                        showError(birthInput, 'Birth certificate is required');
                        isValid = false;
                    }
                    break;
            }
        }

        if (!isValid) {
            // Scroll to the first error
            const firstError = document.querySelector('.is-invalid');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }

        try {
            // Disable form submission
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner"></span> Submitting...';

            const formData = new FormData(form);
            
            const response = await fetch('process.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Your verification request has been submitted successfully.',
                    confirmButtonColor: '#0d6efd'
                }).then(() => {
                    form.reset();
                    resetFileInputs();
                    document.querySelectorAll('.document-upload').forEach(div => div.classList.add('d-none'));
                    formInputs.forEach(input => {
                        input.classList.remove('is-valid');
                    });
                });
            } else {
                throw new Error(result.message || 'Submission failed');
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: error.message || 'Something went wrong! Please try again.',
                confirmButtonColor: '#dc3545'
            });
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="bi bi-check-circle"></i> Submit Verification';
        }
    });

    // Add masking for Bangladesh phone number
    const phoneMask = IMask(phoneInput, {
        mask: [
            {
                mask: '01000000000'  // For format: 01XXXXXXXXX (11 digits)
            },
            {
                mask: '+88001000000000'  // For format: +8801XXXXXXXXX (14 digits)
            }
        ],
        lazy: false
    });
}); 