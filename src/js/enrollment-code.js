jQuery(document).ready(function($) {
    console.log('QRCode library loaded:', typeof QRCode !== 'undefined');

    // Initialize QR code library
    const qrcode = new QRCode(document.querySelector('.qr-code-container'), {
        width: 200,
        height: 200
    });

    // Copy code button functionality
    $('.copy-code-btn').on('click', function() {
        const code = $(this).data('code');
        navigator.clipboard.writeText(code).then(() => {
            const originalTitle = $(this).attr('title');
            $(this).attr('title', 'Copied!');
            setTimeout(() => {
                $(this).attr('title', originalTitle);
            }, 2000);
        });
    });

    // Copy URL button functionality
    $('.copy-url-btn').on('click', function() {
        const url = $(this).data('url');
        navigator.clipboard.writeText(url).then(() => {
            const originalTitle = $(this).attr('title');
            $(this).attr('title', 'Copied!');
            setTimeout(() => {
                $(this).attr('title', originalTitle);
            }, 2000);
        });
    });

    // QR code button functionality
    $('.qr-code-btn').on('click', function() {
        const url = $(this).data('url');
        const row = $(this).closest('tr');
        const modal = row.find('dialog')[0];
        const qrContainer = row.find('.qr-code-container')[0];
        
        console.log('URL:', url);
        console.log('Modal found:', modal);
        console.log('QR container found:', qrContainer);
        
        // Clear previous QR code
        $(qrContainer).empty();
        
        // Initialize new QR code
        const qrcode = new QRCode(qrContainer, {
            width: 200,
            height: 200
        });
        
        // Generate QR code
        qrcode.makeCode(url);
        
        // Show modal
        modal.showModal();
    });

    // Close QR code modal
    $('.qr-code-modal-close').on('click', function() {
        const modal = $(this).closest('dialog')[0];
        modal.close();
    });
}); 