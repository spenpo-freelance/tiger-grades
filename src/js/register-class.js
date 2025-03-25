jQuery(document).ready(function($) {
    document.querySelector('.create-class-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const title = this.querySelector('input[name="title"]').value;
        
        const formData = new FormData();
        formData.append('title', title);

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
                console.log('Error creating class: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            console.log('An error occurred while creating the class');
        });
    });
})
