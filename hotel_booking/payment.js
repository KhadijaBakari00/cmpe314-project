document.addEventListener('DOMContentLoaded', function() {
    // Format card number input
    function formatCardNumber(input) {
        let value = input.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
        let matches = value.match(/\d{4,16}/g);
        let match = matches ? matches[0] : '';
        let parts = [];
        
        for (let i = 0; i < match.length; i += 4) {
            parts.push(match.substring(i, i + 4));
        }
        
        if (parts.length) {
            input.value = parts.join(' ');
            document.getElementById('cardNumberDisplay').textContent = input.value;
        }
    }
    
    // Format expiry date input
    function formatExpiry(input) {
        let value = input.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
        if (value.length > 2) {
            value = value.substring(0, 2) + '/' + value.substring(2, 4);
        }
        
        input.value = value;
        
        // Update card display
        if (value.length >= 2) {
            document.getElementById('month').textContent = value.substring(0, 2);
        }
        if (value.length >= 5) {
            document.getElementById('year').textContent = value.substring(3, 5);
        }
    }
    
    // Initialize event listeners
    document.getElementById('name').addEventListener('input', function() {
        document.getElementById('card-name').textContent = this.value;
    });
    
    document.getElementById('number').addEventListener('input', function() {
        formatCardNumber(this);
    });
    
    document.getElementById('date').addEventListener('input', function() {
        formatExpiry(this);
    });
    
    // Form validation
    const form = document.querySelector('.form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const cardNumber = document.getElementById('number').value.replace(/\s/g, '');
            const expiry = document.getElementById('date').value;
            const cvv = document.getElementById('cvv').value;
            
            // Basic validation
            if (!/^\d{16}$/.test(cardNumber)) {
                alert('Please enter a valid 16-digit card number');
                e.preventDefault();
                return;
            }
            
            if (!/^\d{2}\/\d{2}$/.test(expiry)) {
                alert('Please enter a valid expiry date (MM/YY)');
                e.preventDefault();
                return;
            }
            
            if (!/^\d{3,4}$/.test(cvv)) {
                alert('Please enter a valid CVV (3-4 digits)');
                e.preventDefault();
                return;
            }
        });
    }

    // Show/hide password functionality for CVV
    const cvvEye = document.createElement('span');
    cvvEye.innerHTML = '👁️';
    cvvEye.style.cssText = `
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        opacity: 0.7;
    `;
    
    const cvvField = document.getElementById('cvv');
    if (cvvField) {
        cvvField.parentNode.style.position = 'relative';
        cvvField.parentNode.appendChild(cvvEye);
        
        cvvEye.addEventListener('click', function() {
            if (cvvField.type === 'password') {
                cvvField.type = 'text';
                cvvEye.style.opacity = '1';
            } else {
                cvvField.type = 'password';
                cvvEye.style.opacity = '0.7';
            }
        });
    }
});