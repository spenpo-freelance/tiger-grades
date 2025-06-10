jQuery(document).ready(function($) {
    document.querySelector('.enroll-class-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const loading_element = $('.loading-element');
        loading_element.addClass('loading-medium');
        
        const enrollment_code = this.querySelector('input[name="enrollment_code"]').value;
        const student_name = this.querySelector('input[name="student_name"]').value;
        const optional_message = this.querySelector('textarea[name="optional_message"]').value;
        
        const formData = new FormData();
        formData.append('enrollment_code', enrollment_code);
        formData.append('student_name', student_name);
        formData.append('optional_message', optional_message);

        fetch(this.action, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-WP-Nonce': tigerGradesData.nonce
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const message = data.message || data.data.message;
            if (data.success) {
                this.reset();
                const success_message = document.createElement('div');
                success_message.className = 'success-message';
                success_message.textContent = message;
                this.appendChild(success_message);
                setTimeout(() => {
                    success_message.remove();
                }, 3000);
                loading_element.removeClass('loading-medium');
            } else {
                const error_message = document.createElement('div');
                error_message.className = 'error-message';
                error_message.textContent = message;
                this.appendChild(error_message);
                setTimeout(() => {
                    error_message.remove();
                }, 3000);
                loading_element.removeClass('loading-medium');
            }
        })
    });
})
