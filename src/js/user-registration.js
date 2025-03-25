jQuery(document).ready(function($) {
    const registrationButtons = $('.registration-button');
    const registrationFormContainer = $('.registration-form-container');
    const cachedForms = {};
    cachedForms.user = registrationFormContainer.html();
    fetchTeacherForm();
    
    registrationButtons.on('click', function() {
        registrationButtons.removeClass('active');
        $(this).addClass('active');
        const formId = $(this).data('form-id');
        
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
