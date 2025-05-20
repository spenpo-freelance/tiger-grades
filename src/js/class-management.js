jQuery(document).ready(function($) {
    const approveBtns = $('.approve-enrollment-btn');
    const dialog = document.querySelector('#approve-dialog-approveDialog');
    const approvalBtn = document.querySelector('.approve-dialog-confirm');
    const cancelBtn = document.querySelector('.approve-dialog-cancel');

    const { class_id, studentApiUrl, approveApiUrl, nonce } = tigerGradesData;

    let selectedStudentId;

    $.ajax({
        url: studentApiUrl,
        method: 'GET',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('X-WP-Nonce', nonce);
        },
        data: {
            class_id
        },
        success: function(data) {
            const studentSelect = document.querySelector('.approve-dialog-student-select');
            data.forEach(student => {
                const option = document.createElement('option');
                option.value = student.id;
                option.textContent = student.name;
                studentSelect.appendChild(option);
            });
            studentSelect.addEventListener('change', function() {
                selectedStudentId = this.value;
                if (this.value !== '') {
                    approvalBtn.disabled = false;
                } else {
                    approvalBtn.disabled = true;
                }
            });
            studentSelect.disabled = false;
            approveBtns.each(function() {
                $(this).removeAttr('disabled');
            });
        },
        error: function(xhr, status, error) {
            reportCard.html('<div class="error-message">Error loading report card. Please try again later.</div>');
            console.error('Error fetching report card:', error);
        }
    });

    // Add click handlers to all approve buttons
    approveBtns.each(function() {
        $(this).on('click', function(e) {
            e.preventDefault();
            dialog.showModal(); // Opens the modal
            const enrollmentId = $(this).data('enrollment-id');

            approvalBtn.addEventListener('click', () => {
                $.ajax({
                    url: approveApiUrl,
                    method: 'POST',
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', nonce);
                    },
                    data: {
                        enrollment_id: enrollmentId,
                        student_id: selectedStudentId
                    },
                    success: function(data) {
                        dialog.close();
                    },
                    error: function(xhr, status, error) {
                        console.error('Error approving enrollment:', error);
                    }
                });
            });
        });
    });

    // Handle dialog close
    cancelBtn.addEventListener('click', () => {
        approvalBtn.removeEventListener('click', () => {});
        dialog.close();
    });
}); 
