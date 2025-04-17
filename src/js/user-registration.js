jQuery(document).ready(function($) {
    const registrationButtons = $('.registration-button');
    const registrationFormContainer = $('.registration-form-container');
    const registrationButtonsContainer = $('.registration-buttons');
    const cachedForms = {};
    cachedForms.user = registrationFormContainer.html();
    fetchTeacherForm();
    
    registrationButtons.on('click', function() {
        const formId = $(this).data('form-id');
        
        // Update the container's data-active attribute
        registrationButtonsContainer.attr('data-active', formId);
        
        // Use cached form if available
        if (cachedForms[formId]) {
            registrationFormContainer.html(cachedForms[formId]);
            
            // Reinitialize hCaptcha
            if (typeof hcaptcha !== 'undefined') {
                hcaptcha.render('ur-recaptcha-node', {
                    sitekey: tigr_ajax_object.hcaptcha_site_key,
                    theme: 'light' // optional
                });
            }
        }
    });

    function fetchTeacherForm() {
        $.ajax({
            url: tigr_ajax_object.ajax_url,
            type: 'POST',
            data: { 
                shortcode: '[user_registration_form id="' + tigr_ajax_object.teacher_form_id + '"]'
            },
            success: function(response) {
                cachedForms.teacher = response.rendered;
            }
        });
    }
});
