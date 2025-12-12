document.addEventListener('DOMContentLoaded', function() {
    function isMobile() {
        return window.innerWidth <= 500;
    }

    function updateHCaptchaSize() {
        const hcaptchaDivs = document.querySelectorAll('.g-recaptcha-hcaptcha');
        hcaptchaDivs.forEach(function(div) {
            if (isMobile()) {
                div.setAttribute('data-size', 'compact');
            } else {
                div.removeAttribute('data-size');
            }
        });
    }

    updateHCaptchaSize();
    window.addEventListener('resize', updateHCaptchaSize);
}); 