jQuery(document).ready(function($) {
    const dialog = document.querySelector('#approve-dialog-approveDialog');
    const confirmApprovalBtn = document.querySelector('.approve-dialog-confirm');
    const cancelBtn = document.querySelector('.approve-dialog-cancel');
    const enrollmentRows = $('.enrollment-table-row');

    const { class_id, studentApiUrl, approveApiUrl, rejectApiUrl, nonce } = tigerGradesData;

    let selectedStudentId;

    enrollmentRows.each(function() {
        const enrollmentId = $(this).data('enrollment-id');
        const statusCell = $(this).find('.enrollment-table-cell.enrollment-status');
        const status = statusCell.text();

        const viewMessageBtn = $(this).find('.view-message-btn');
        if (viewMessageBtn.length > 0) {
            viewMessageBtn.on('click', function(e) {
                e.preventDefault();
                const messageDialog = document.querySelector(`.message-dialog[data-enrollment-id="${enrollmentId}"]`);
                messageDialog.showModal();
                const messageDialogCloseBtn = messageDialog.querySelector('.message-dialog-close');
                messageDialogCloseBtn.addEventListener('click', () => {
                    messageDialog.close();
                });
            });
            viewMessageBtn.removeAttr('disabled');
        }

        const rejectBtn = $(this).find('.reject-enrollment-btn');
        rejectBtn.on('click', function(e) {
            e.preventDefault();
            const rejectBtnLabel = $(this).find('.reject-enrollment-btn-label');
            const rejectBtnText = rejectBtnLabel.text();
            rejectBtnLabel.text("");
            rejectBtnLabel.addClass('loading-small');
            $.ajax({
                url: rejectApiUrl,
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', nonce);
                },
                data: {
                    enrollment_id: enrollmentId
                },
                success: function(data) {
                    rejectBtnLabel.text(rejectBtnText);
                    rejectBtnLabel.removeClass('loading-small');
                    statusCell.text(data.data.status);
                    rejectBtn.attr('disabled', 'disabled');
                }
            });
        });
        if (status !== 'rejected') {
            rejectBtn.removeAttr('disabled');
        }
    });

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
            // Populate the student select with the students in the class
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
                    confirmApprovalBtn.disabled = false;
                } else {
                    confirmApprovalBtn.disabled = true;
                }
            });
            studentSelect.disabled = false;

            enrollmentRows.each(function() {
                const statusCell = $(this).find('.enrollment-table-cell.enrollment-status');
                const rejectBtn = $(this).find('.reject-enrollment-btn');
                // Populate the gradebook name for each enrollment row
                const gradebookName = $(this).find('.gradebook-name');
                const studentId = $(this).data('student-id');
                if (studentId) {
                    const studentName = data.find(student => Number(student.id) === studentId)?.name;
                    gradebookName.text(studentName);
                    gradebookName.removeClass('loading-small');
                }

                // Handle the function of the approve button
                const approveBtn = $(this).find('.approve-enrollment-btn');
                approveBtn.on('click', function(e) {
                    e.preventDefault();
                    dialog.showModal(); // Opens the modal
                    const enrollmentId = $(this).data('enrollment-id');
        
                    confirmApprovalBtn.addEventListener('click', () => {
                        const confirmText = $('.approve-dialog-confirm-text');
                        confirmText.text('');
                        confirmText.addClass('loading-small');
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
                                statusCell.text(data.data.status);
                                approveBtn.text('Change');
                                confirmText.text('Confirm');
                                confirmText.removeClass('loading-small');
                                const studentSelect = document.querySelector('.approve-dialog-student-select');
                                studentSelect.value = '';
                                rejectBtn.removeAttr('disabled');
                                confirmApprovalBtn.disabled = true;
                            },
                            error: function(xhr, status, error) {
                                confirmText.text('Confirm');
                                confirmText.removeClass('loading-small');
                                const errorMessage = document.createElement('div');
                                errorMessage.className = 'error-message';
                                errorMessage.textContent = 'Error approving enrollment. Please try again later. If the problem persists, please contact support.';
                                dialog.appendChild(errorMessage);
                                setTimeout(() => {
                                    errorMessage.remove();
                                }, 3000);
                            }
                        });
                    });
                });

                approveBtn.removeAttr('disabled');
                approveBtn.find('.loading-small').hide();
            });
        },
        error: function(xhr, status, error) {
            reportCard.html('<div class="error-message">Error loading report card. Please try again later.</div>');
            console.error('Error fetching report card:', error);
        }
    });

    // Handle dialog close
    cancelBtn.addEventListener('click', () => {
        confirmApprovalBtn.removeEventListener('click', () => {});
        dialog.close();
    });
}); 
