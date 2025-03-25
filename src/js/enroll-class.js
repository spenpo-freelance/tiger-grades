jQuery(document).ready(function($) {
    document.querySelector('.enroll-class-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
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
            if (data.success) {
                this.reset();
            } else {
                console.log('Error enrolling in class: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            console.log('An error occurred while enrolling in the class');
        });
    });
})
