jQuery(document).ready(function($) {
    const registrationButtons = $('.registration-button');
    const registrationFormContainer = $('.registration-form-container');
    const registrationButtonsContainer = $('.registration-buttons');
    const cachedForms = {};
    const loadedScripts = new Set();
    
    registrationButtons.on('click', function() {
        const formId = $(this).data('form-id');
        
        // Update the container's data-active attribute
        registrationButtonsContainer.attr('data-active', formId);
        
        // If we don't have this form cached yet, fetch it
        if (!cachedForms[formId]) {
            fetchForm(formId);
        } else {
            // Clear previously loaded scripts
            clearLoadedScripts();
            // Load the form and its scripts
            loadForm(formId);
        }
    });

    function clearLoadedScripts() {
        // Remove all dynamically loaded scripts
        $('script[data-dynamic="true"]').remove();
        loadedScripts.clear();
    }

    function loadForm(formId) {
        // Set the form HTML
        registrationFormContainer.html(cachedForms[formId].html);
        
        // Load the form's scripts
        loadScripts(cachedForms[formId].scripts).catch(error => {
            console.error('Error loading scripts for form:', formId, error);
        });
    }

    function fetchForm(formId) {
        const formShortcode = formId === 'teacher' 
            ? '[user_registration_form id="' + tigr_ajax_object.teacher_form_id + '"]'
            : '[user_registration_form id="' + tigr_ajax_object.user_form_id + '"]';

        $.ajax({
            url: tigr_ajax_object.ajax_url,
            type: 'POST',
            data: { 
                shortcode: formShortcode,
                lang: tigr_ajax_object.language
            },
            success: function(response) {
                cachedForms[formId] = {
                    html: response.rendered,
                    scripts: response.scripts
                };
                // Clear previously loaded scripts
                clearLoadedScripts();
                // Load the new form and its scripts
                loadForm(formId);
            },
            error: function(xhr, status, error) {
                console.error('Error fetching form:', formId, error);
            }
        });
    }

    // Function to load scripts in dependency order
    const loadScripts = async (scripts) => {
        // Create a map of scripts by their handle for easy lookup
        const scriptMap = new Map();
        scripts.forEach(script => {
            scriptMap.set(script.handle, script);
        });

        // Function to load a single script
        const loadScript = (script) => {
            return new Promise((resolve, reject) => {
                // Skip if already loaded
                if (loadedScripts.has(script.handle)) {
                    resolve();
                    return;
                }

                // Load dependencies first
                const dependencyPromises = script.deps
                    .filter(dep => scriptMap.has(dep))
                    .map(dep => loadScript(scriptMap.get(dep)));

                Promise.all(dependencyPromises)
                    .then(() => {
                        const scriptElement = document.createElement('script');
                        scriptElement.src = script.src;
                        if (script.handle === 'ur-recaptcha-hcaptcha') {
                            scriptElement.src += '&hl=' + tigr_ajax_object.language;
                        }
                        scriptElement.setAttribute('data-dynamic', 'true');
                        scriptElement.onload = () => {
                            loadedScripts.add(script.handle);
                            resolve();
                        };
                        scriptElement.onerror = (error) => {
                            reject(error);
                        };
                        document.head.appendChild(scriptElement);
                    })
                    .catch(reject);
            });
        };

        // Load all scripts
        const loadPromises = scripts.map(script => loadScript(script));
        return Promise.all(loadPromises);
    };
});
