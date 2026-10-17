// paymentSlip.js

function generatePaymentSlip(name, amount, mode, date, time) {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({
        orientation: 'portrait',
        unit: 'mm',
        format: 'a5'
    });

    // Colors
    const primaryColor = '#4169E1';
    const textColor = '#333333';
    const lightGray = '#F5F5F5';

    // Background
    doc.setFillColor(lightGray);
    doc.rect(0, 0, 148, 210, 'F');

    // Header
    doc.setFillColor(primaryColor);
    doc.rect(0, 0, 148, 25, 'F');

    doc.setFontSize(18);
    doc.setTextColor('#FFFFFF');
    doc.setFont('helvetica', 'bold');
    const headerText = 'NeXusHooD Payment Receipt';
    const headerWidth = doc.getTextWidth(headerText);
    doc.text(headerText, (148 - headerWidth) / 2, 15);

    // Main Content
    doc.setTextColor(textColor);
    doc.setFontSize(12);
    doc.setFont('helvetica', 'normal');

    // Details
    const details = [
        { label: 'Resident Name:', value: name },
        { label: 'Amount Paid:', value: `₹ ${parseFloat(amount).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` },
        { label: 'Payment Mode:', value: mode },
        { label: 'Date:', value: date },
        { label: 'Time:', value: time },
        { label: 'Receipt No:', value: `NXH-${Date.now().toString().slice(-6)}` },
        { label: 'Status:', value: 'PAID' }
    ];

    let yPos = 35;
    details.forEach(detail => {
        doc.setFont('helvetica', 'bold');
        doc.text(detail.label, 10, yPos);
        doc.setFont('helvetica', 'normal');
        doc.text(detail.value, 50, yPos);
        yPos += 10;
    });

    // Divider
    doc.setLineWidth(0.5);
    doc.setDrawColor(primaryColor);
    doc.line(10, yPos, 138, yPos);
    yPos += 10;

    // Generated Date
    doc.setFontSize(10);
    const generatedText = `Generated on: ${new Date().toLocaleDateString('en-IN')} ${new Date().toLocaleTimeString('en-IN')}`;
    const generatedWidth = doc.getTextWidth(generatedText);
    doc.text(generatedText, (148 - generatedWidth) / 2, yPos);

    // Footer
    doc.setFillColor(primaryColor);
    doc.rect(0, 190, 148, 20, 'F');

    doc.setFontSize(10);
    doc.setTextColor('#FFFFFF');
    const footerText = 'NeXusHooD Society Management | support@nexushood.com | www.nexushood.com';
    const footerWidth = doc.getTextWidth(footerText);
    doc.text(footerText, (148 - footerWidth) / 2, 200);

    // Download
    const filename = `Payment_Receipt_${name}_${date.replace(/-/g, '')}.pdf`;
    doc.save(filename);
}

document.addEventListener('DOMContentLoaded', () => {
    const downloadButtons = document.querySelectorAll('.download-btn');
    
    downloadButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            
            const row = button.closest('tr');
            const name = row.cells[0].textContent;
            // Remove ₹ and commas, then trim whitespace
            let amountText = row.cells[1].textContent.replace('₹', '').replace(/,/g, '').trim();
            const amount = parseFloat(amountText);
            const mode = row.cells[2].textContent;
            const date = row.cells[3].textContent;
            const time = row.cells[4].textContent;
            
            // Debug logging to check amount parsing
            console.log('Raw amount text:', row.cells[1].textContent);
            console.log('Parsed amount:', amount);
            
            if (isNaN(amount)) {
                alert('Error: Could not parse amount. Please check the table data.');
                return;
            }
            
            generatePaymentSlip(name, amount, mode, date, time);
        });
    });
});