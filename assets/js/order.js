// Order JavaScript

document.addEventListener('DOMContentLoaded', function() {
    const orderForm = document.getElementById('orderForm');
    const durationInput = document.getElementById('duration');
    const priceInput = document.getElementById('price_per_day');
    const totalDisplay = document.getElementById('total_display');
    const deliveryOption = document.getElementById('delivery_option');
    const addressField = document.getElementById('addressField');
    const startDateInput = document.getElementById('rental_start_date');

    // Set minimum date to today
    if (startDateInput) {
        const today = new Date().toISOString().split('T')[0];
        startDateInput.setAttribute('min', today);
    }

    // Calculate total price
    if (durationInput && priceInput && totalDisplay) {
        const pricePerDay = parseInt(priceInput.value) || 0;
        
        durationInput.addEventListener('input', function() {
            const days = parseInt(this.value) || 0;
            if (days > 0) {
                const total = days * pricePerDay;
                totalDisplay.innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
            } else {
                totalDisplay.innerText = 'Rp 0';
            }
        });
    }

    // Show/hide delivery address field
    if (deliveryOption && addressField) {
        deliveryOption.addEventListener('change', function() {
            addressField.style.display = this.value === 'delivery' ? 'block' : 'none';
            
            const addressInput = document.getElementById('delivery_address');
            if (addressInput) {
                addressInput.required = this.value === 'delivery';
            }
        });
    }

    // Form validation
    if (orderForm) {
        orderForm.addEventListener('submit', function(e) {
            const startDate = startDateInput ? startDateInput.value : '';
            const duration = durationInput ? durationInput.value : 0;
            
            if (!startDate) {
                e.preventDefault();
                alert('Please select a start date.');
                return false;
            }
            
            if (!duration || duration < 1) {
                e.preventDefault();
                alert('Please enter a valid duration (minimum 1 day).');
                return false;
            }
            
            return true;
        });
    }
});