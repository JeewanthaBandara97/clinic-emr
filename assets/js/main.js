/**
 * Clinic EMR System - Main JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Sidebar Toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('active');
        });
    }
    
    // Update current time
    function updateTime() {
        const timeElement = document.getElementById('currentTime');
        if (timeElement) {
            const now = new Date();
            let hours = now.getHours();
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12;
            timeElement.textContent = `${hours}:${minutes} ${ampm}`;
        }
    }
    
    setInterval(updateTime, 1000);
    
    // Auto-dismiss alerts
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000);
    });
    
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
    
    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('[data-confirm]');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(event) {
            const message = this.dataset.confirm || 'Are you sure?';
            if (!confirm(message)) {
                event.preventDefault();
            }
        });
    });
    
    // Calculate BMI
    window.calculateBMI = function() {
        const weight = parseFloat(document.getElementById('weight')?.value) || 0;
        const height = parseFloat(document.getElementById('height')?.value) || 0;
        
        if (weight > 0 && height > 0) {
            const heightInMeters = height / 100;
            const bmi = (weight / (heightInMeters * heightInMeters)).toFixed(1);
            const bmiField = document.getElementById('bmi');
            if (bmiField) {
                bmiField.value = bmi;
            }
        }
    };
    
    // Add event listeners for BMI calculation
    const weightField = document.getElementById('weight');
    const heightField = document.getElementById('height');
    
    if (weightField) {
        weightField.addEventListener('input', window.calculateBMI);
    }
    if (heightField) {
        heightField.addEventListener('input', window.calculateBMI);
    }
});

/**
 * AJAX helper function
 */
function ajaxRequest(url, method, data, callback) {
    const xhr = new XMLHttpRequest();
    xhr.open(method, url, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    callback(null, response);
                } catch (e) {
                    callback(null, xhr.responseText);
                }
            } else {
                callback(new Error('Request failed'), null);
            }
        }
    };
    
    if (method === 'POST' && data) {
        xhr.send(data);
    } else {
        xhr.send();
    }
}

/**
 * Show loading spinner
 */
function showLoading(element) {
    element.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
}

/**
 * Format date
 */
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('en-US', options);
}