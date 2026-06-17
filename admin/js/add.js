// add.js - client validation, image preview, file size checks
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('addDriverForm');
    const pw = document.getElementById('password');
    const cpw = document.getElementById('confirmPassword');
    const pwMessage = document.getElementById('pwMessage');
    const profileImage = document.getElementById('profileImage');
    const imagePreview = document.getElementById('imagePreview').querySelector('img');
    const licenseDoc = document.getElementById('licenseDoc');
    const idDoc = document.getElementById('idDoc');
    const formMessage = document.getElementById('formMessage');

    const MAX_IMAGE_BYTES = 3 * 1024 * 1024; // 3MB
    const MAX_DOC_BYTES = 5 * 1024 * 1024; // 5MB

    // password match check in real-time
    function checkPasswords() {
        if (!pw.value && !cpw.value) { pwMessage.textContent = ''; return true; }
        if (pw.value !== cpw.value) {
            pwMessage.textContent = 'Passwords do not match';
            pwMessage.style.color = '#b71c1c';
            return false;
        } else {
            pwMessage.textContent = 'Passwords match';
            pwMessage.style.color = '#0b7a47';
            return true;
        }
    }

    pw.addEventListener('input', checkPasswords);
    cpw.addEventListener('input', checkPasswords);

    // image preview
    profileImage.addEventListener('change', function() {
        const file = this.files && this.files[0];
        if (!file) { imagePreview.style.display = 'none';
            imagePreview.src = ''; return; }
        if (!file.type.startsWith('image/')) {
            alert('Profile must be an image file');
            this.value = '';
            return;
        }
        if (file.size > MAX_IMAGE_BYTES) {
            alert('Profile image must be smaller than 3MB');
            this.value = '';
            return;
        }
        const reader = new FileReader();
        reader.onload = function(e) {
            imagePreview.src = e.target.result;
            imagePreview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    });

    // file size checks for docs
    function checkFileSize(inputEl, maxBytes, label) {
        const file = inputEl.files && inputEl.files[0];
        if (!file) return true;
        if (file.size > maxBytes) {
            alert(label + ' must be smaller than ' + (maxBytes / (1024 * 1024)) + 'MB');
            inputEl.value = '';
            return false;
        }
        return true;
    }

    licenseDoc.addEventListener('change', () => checkFileSize(licenseDoc, MAX_DOC_BYTES, 'License'));
    idDoc.addEventListener('change', () => checkFileSize(idDoc, MAX_DOC_BYTES, 'ID Document'));

    // final validation on submit
    form.addEventListener('submit', function(e) {
        formMessage.textContent = '';
        // built-in HTML validation
        if (!form.checkValidity()) {
            form.reportValidity();
            e.preventDefault();
            return;
        }

        // passwords match
        if (!checkPasswords()) {
            e.preventDefault();
            return;
        }

        // file sizes
        if (!checkFileSize(profileImage, MAX_IMAGE_BYTES, 'Profile image') ||
            !checkFileSize(licenseDoc, MAX_DOC_BYTES, 'License') ||
            !checkFileSize(idDoc, MAX_DOC_BYTES, 'ID Document')) {
            e.preventDefault();
            return;
        }

        // At this point the form will be submitted using normal POST (server handles DB insertion).
        // Optionally show a "submitting" state:
        formMessage.textContent = 'Submitting, please wait...';
        formMessage.style.color = '#0b7a47';
    });
});