jQuery(document).ready(function($) {
    const createClassForm = $('.create-class-form');
    createClassForm.on('submit', function(e) {
        e.preventDefault();
        
        const title = createClassForm.find('input[name="title"]').val();
        const classTypeId = createClassForm.find('.class-type-selection-container-item input[type="radio"]:checked').val();
        const numStudents = createClassForm.find('select[name="num_students"]').val();
        const numCategories = createClassForm.find('select[name="num_categories"]').val();
        const description = createClassForm.find('input[name="description"]').val();
        const message = createClassForm.find('textarea[name="message"]').val();
        const startDate = createClassForm.find('input[name="start_date"]').val();
        const endDate = createClassForm.find('input[name="end_date"]').val();
        
        const formData = new FormData();
        formData.append('title', title);
        formData.append('type', classTypeId);
        formData.append('num_students', numStudents);
        formData.append('num_categories', numCategories);
        formData.append('description', description);
        formData.append('message', message);
        formData.append('start_date', startDate);
        formData.append('end_date', endDate);

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
