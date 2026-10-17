// expense.js
$(document).ready(function() {
    // Accordion toggle functionality
    function initializeAccordion() {
        $('.accordion-header').off('click');
        $('.accordion-header').on('click', function() {
            const $accordion = $(this).parent();
            const $content = $accordion.find('.accordion-content');
            
            $accordion.toggleClass('active');
            
            if ($accordion.hasClass('active')) {
                $content.slideDown(300);
            } else {
                $content.slideUp(300);
            }
            
            $('.form-accordion').not($accordion).removeClass('active');
            $('.form-accordion').not($accordion).find('.accordion-content').slideUp(300);
        });

        $('.form-accordion').removeClass('active');
        $('.accordion-content').hide();
    }

    initializeAccordion();

    // Cheque details toggle
    const paymentType = document.getElementById('paymentType');
    const chequeDetails = document.getElementById('chequeDetails');
    const chequeDetailsBank = document.getElementById('chequeDetailsBank');

    paymentType.addEventListener('change', function() {
        if (this.value === 'cheque') {
            chequeDetails.classList.remove('hidden');
            chequeDetailsBank.classList.remove('hidden');
        } else {
            chequeDetails.classList.add('hidden');
            chequeDetailsBank.classList.add('hidden');
        }
    });

    // Delete button functionality
    $('.delete-btn').on('click', function() {
        $('.delete-range-form').slideToggle(300);
    });

    $('.cancel-delete-btn').on('click', function() {
        $('.delete-range-form').slideUp(300);
    });
});