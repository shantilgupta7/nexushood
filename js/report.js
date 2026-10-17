$(document).ready(function() {
    const toggle = document.querySelector(".toggle");
    const sidebar = document.querySelector(".sidebar");
    const main = document.querySelector(".main");

    toggle.onclick = function() {
        sidebar.classList.toggle("active");
        main.classList.toggle("active");
    };

    // Accordion toggle functionality
    function initializeAccordion() {
        $('.accordion-header').off('click');
        $('.accordion-header').on('click', function() {
            const $accordion = $(this).parent('.accordion');
            const $content = $accordion.find('.accordion-content');
            
            $accordion.toggleClass('active');
            
            if ($accordion.hasClass('active')) {
                $content.slideDown(300);
            } else {
                $content.slideUp(300);
            }
            
            $('.accordion').not($accordion).removeClass('active');
            $('.accordion').not($accordion).find('.accordion-content').slideUp(300);
        });

        $('.accordion').removeClass('active');
        $('.accordion-content').hide();
    }

    initializeAccordion();

    // Date parsing function
    function parseDate(dateStr) {
        const parts = dateStr.split('-');
        if (parts.length !== 3) return null;
        return new Date(parts[2], parts[1] - 1, parts[0]);
    }

    // Format date to DD/MM/YYYY
    function formatDateToDDMMYYYY(dateStr) {
        if (!dateStr) return "N/A";
        const date = new Date(dateStr);
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${day}/${month}/${year}`;
    }

    // Filter Expense Table
    window.filterExpenseTable = function() {
        const fromDate = $("#fromDateExpense").val();
        const toDate = $("#toDateExpense").val();
        const selectedCategory = $('input[name="categoryFilter"]:checked').val();
        const selectedPaymentType = $('input[name="expensePaymentTypeFilter"]:checked').val();
        const $rows = $("#expenseTable tbody tr");
        let totalFilteredExpense = 0;

        console.log("Selected Category: " + selectedCategory);
        console.log("Selected Payment Type: " + selectedPaymentType);

        const from = fromDate ? new Date(fromDate) : null;
        const to = toDate ? new Date(toDate) : null;
        if (to) to.setHours(23, 59, 59, 999);

        if ((fromDate && !toDate) || (!fromDate && toDate)) {
            alert("Please select both 'From' and 'To' dates or leave both empty.");
            return;
        }
        if (from && to && from > to) {
            alert("'From' date must be earlier than 'To' date.");
            return;
        }

        $rows.each(function() {
            const $row = $(this);
            const dateStr = $row.find("td:eq(4)").text();
            const category = $row.find("td:eq(1)").text().trim().toLowerCase();
            const paymentType = $row.find("td:eq(5)").text().trim().toLowerCase();
            const amountText = $row.find("td:eq(3)").text().replace('₹', '').replace(/,/g, '');
            const amount = parseFloat(amountText);
            const rowDate = parseDate(dateStr);

            console.log(`Row - Category: ${category}, Payment Type: ${paymentType}, Date: ${dateStr}`);

            const dateMatch = (!from && !to) || (rowDate >= from && rowDate <= to);
            const categoryMatch = selectedCategory === "all" || category === selectedCategory;
            const typeMatch = selectedPaymentType === "all" || paymentType === selectedPaymentType;

            if (dateMatch && categoryMatch && typeMatch) {
                $row.show();
                totalFilteredExpense += isNaN(amount) ? 0 : amount;
            } else {
                $row.hide();
            }
        });

        $("#totalExpense").text(totalFilteredExpense.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    };

    // Filter Payment Table
    window.filterPaymentTable = function() {
        const fromDate = $("#fromDatePayment").val();
        const toDate = $("#toDatePayment").val();
        const selectedPurpose = $('input[name="purposeFilter"]:checked').val();
        const selectedMode = $('input[name="modeFilter"]:checked').val();
        const selectedPaymentType = $('input[name="paymentTypeFilter"]:checked').val();
        const $rows = $("#paymentTable tbody tr");
        let totalFilteredMaintenance = 0;

        const from = fromDate ? new Date(fromDate) : null;
        const to = toDate ? new Date(toDate) : null;
        if (to) to.setHours(23, 59, 59, 999);

        if ((fromDate && !toDate) || (!fromDate && toDate)) {
            alert("Please select both 'From' and 'To' dates or leave both empty.");
            return;
        }
        if (from && to && from > to) {
            alert("'From' date must be earlier than 'To' date.");
            return;
        }

        $rows.each(function() {
            const $row = $(this);
            const dateStr = $row.find("td:eq(8)").text(); // Adjusted index due to new columns
            const purpose = $row.find("td:eq(2)").text().trim().toLowerCase();
            const mode = $row.find("td:eq(3)").text().trim().toLowerCase();
            const paymentType = $row.find("td:eq(4)").text().trim().toLowerCase();
            const amountText = $row.find("td:eq(7)").text().replace('₹', '').replace(/,/g, ''); // Adjusted index
            const amount = parseFloat(amountText);
            const rowDate = parseDate(dateStr);

            const dateMatch = (!from && !to) || (rowDate >= from && rowDate <= to);
            const purposeMatch = selectedPurpose === "all" || purpose === selectedPurpose;
            const modeMatch = selectedMode === "all" || mode === selectedMode;
            const typeMatch = selectedPaymentType === "all" || paymentType === selectedPaymentType;

            if (dateMatch && purposeMatch && modeMatch && typeMatch) {
                $row.show();
                totalFilteredMaintenance += isNaN(amount) ? 0 : amount;
            } else {
                $row.hide();
            }
        });

        $("#totalMaintenance").text(totalFilteredMaintenance.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    };

    // Filter Complaint Table
    window.filterComplaintTable = function() {
        const fromDate = $("#fromDateComplaint").val();
        const toDate = $("#toDateComplaint").val();
        const selectedStatus = $('input[name="statusFilter"]:checked').val();
        const selectedBlocked = $('input[name="blockedFilter"]:checked').val();
        const $rows = $("#complaintTable tbody tr");

        const from = fromDate ? new Date(fromDate) : null;
        const to = toDate ? new Date(toDate) : null;
        if (to) to.setHours(23, 59, 59, 999);

        if ((fromDate && !toDate) || (!fromDate && toDate)) {
            alert("Please select both 'From' and 'To' dates or leave both empty.");
            return;
        }
        if (from && to && from > to) {
            alert("'From' date must be earlier than 'To' date.");
            return;
        }

        $rows.each(function() {
            const $row = $(this);
            const dateStr = $row.find("td:eq(5)").text();
            const status = $row.find("td:eq(7)").text().trim().toLowerCase();
            const blocked = $row.find("td:eq(8)").text().trim().toLowerCase();
            const rowDate = parseDate(dateStr);

            const dateMatch = (!from && !to) || (rowDate >= from && rowDate <= to);
            const statusMatch = selectedStatus === "all" || 
                               (selectedStatus === "resolved" && status === "resolved") || 
                               (selectedStatus === "unresolved" && status === "unresolved");
            const blockedMatch = selectedBlocked === "all" || 
                                (selectedBlocked === "blocked" && blocked === "yes") || 
                                (selectedBlocked === "unblocked" && blocked === "no");

            if (dateMatch && statusMatch && blockedMatch) {
                $row.show();
            } else {
                $row.hide();
            }
        });
    };

    // Filter Pending Table
    window.filterPendingTable = function() {
        const fromDate = $("#fromDatePending").val();
        const toDate = $("#toDatePending").val();
        const selectedOccupancy = $('input[name="occupancyFilter"]:checked').val();
        const $rows = $("#pendingTable tbody tr");
        let totalFilteredPending = 0;

        const from = fromDate ? new Date(fromDate) : null;
        const to = toDate ? new Date(toDate) : null;
        if (to) to.setHours(23, 59, 59, 999);

        if ((fromDate && !toDate) || (!fromDate && toDate)) {
            alert("Please select both 'From' and 'To' dates or leave both empty.");
            return;
        }
        if (from && to && from > to) {
            alert("'From' date must be earlier than 'To' date.");
            return;
        }

        $rows.each(function() {
            const $row = $(this);
            const dateStr = $row.find("td:eq(3)").text();
            const occupancy = $row.find("td:eq(4)").text().trim();
            const amountText = $row.find("td:eq(5)").text().replace('₹', '').replace(/,/g, '');
            const amount = parseFloat(amountText);
            const rowDate = parseDate(dateStr);

            const dateMatch = (!from && !to) || (rowDate >= from && rowDate <= to);
            const occupancyMatch = selectedOccupancy === "all" || 
                                   (selectedOccupancy === "Owner" && occupancy === "Owner") || 
                                   (selectedOccupancy === "Rented" && occupancy === "Rented");

            if (dateMatch && occupancyMatch) {
                $row.show();
                totalFilteredPending += isNaN(amount) ? 0 : amount;
            } else {
                $row.hide();
            }
        });

        $("#totalPending").text(totalFilteredPending.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    };

    // Print Table Function
    function printTable(tableId, title) {
        const $rows = $(`#${tableId} tbody tr:visible`);
        if ($rows.length === 0) {
            alert("No data to print. Please filter some data first.");
            return;
        }

        const tableHead = $(`#${tableId} thead`).html();
        const tableFooter = $(`#${tableId} tfoot`).html() || '';
        const currentDate = new Date().toLocaleDateString('en-IN', { day: '2-digit', month: '2-digit', year: 'numeric' });
        let fromDateId, toDateId;
        if (tableId === 'expenseTable') {
            fromDateId = '#fromDateExpense';
            toDateId = '#toDateExpense';
        } else if (tableId === 'paymentTable') {
            fromDateId = '#fromDatePayment';
            toDateId = '#toDatePayment';
        } else if (tableId === 'complaintTable') {
            fromDateId = '#fromDateComplaint';
            toDateId = '#toDateComplaint';
        } else if (tableId === 'pendingTable') {
            fromDateId = '#fromDatePending';
            toDateId = '#toDatePending';
        }
        const fromDate = formatDateToDDMMYYYY($(fromDateId).val());
        const toDate = formatDateToDDMMYYYY($(toDateId).val());

        let tbodyContent = '';
        $rows.each(function() {
            tbodyContent += $(this).prop('outerHTML');
        });

        const printWindow = window.open("", "", "width=900,height=700");
        printWindow.document.write(`
            <html>
            <head>
                <title>${title}</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 40px; text-align: center; }
                    .header { background: #00246b; color: white; padding: 20px; border-radius: 8px; }
                    .header h1 { margin: 0; font-size: 28px; }
                    .header p { margin: 5px 0; font-size: 14px; }
                    .range { margin: 20px 0; font-weight: bold; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
                    th { background: #00246b; color: white; }
                    tr:nth-child(even) { background: #f8f9fa; }
                    .footer { position: fixed; bottom: 20px; width: calc(100% - 80px); display: flex; justify-content: space-between; font-size: 14px; }
                    .footer .date { margin: 0; font-weight: normal; }
                    .footer .signature { text-align: right; }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1>NexusHood.Com</h1>
                    <p>Sea-Queen, Main Road, Moti Daman, Dadra Nagar & Haveli, Daman & Div - 396210</p>
                </div>
                <p class="range">Range: ${fromDate} to ${toDate}</p>
                <table>
                    <thead>${tableHead}</thead>
                    <tbody>${tbodyContent}</tbody>
                    <tfoot>${tableFooter}</tfoot>
                </table>
                <div class="footer">
                    <div class="date">Date: ${currentDate}</div>
                    <div class="signature">Signature: ____________________</div>
                </div>
            </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
    }

    window.printExpenseTable = function() { printTable("expenseTable", "Expense Report"); };
    window.printPaymentTable = function() { printTable("paymentTable", "Income Report"); };
    window.printComplaintTable = function() { printTable("complaintTable", "Complaint Report"); };
    window.printPendingTable = function() { printTable("pendingTable", "Pending Maintenance Report"); };

    // Download Table Function
    function downloadTable(tableId, filename) {
        const $rows = $(`#${tableId} tbody tr:visible`);
        if ($rows.length === 0) {
            alert("No data to download. Please filter some data first.");
            return;
        }

        const tableHead = $(`#${tableId} thead`).html();
        const tableFooter = $(`#${tableId} tfoot`).html() || '';
        const currentDate = new Date().toLocaleDateString('en-IN', { day: '2-digit', month: '2-digit', year: 'numeric' });
        let fromDateId, toDateId;
        if (tableId === 'expenseTable') {
            fromDateId = '#fromDateExpense';
            toDateId = '#toDateExpense';
        } else if (tableId === 'paymentTable') {
            fromDateId = '#fromDatePayment';
            toDateId = '#toDatePayment';
        } else if (tableId === 'complaintTable') {
            fromDateId = '#fromDateComplaint';
            toDateId = '#toDateComplaint';
        } else if (tableId === 'pendingTable') {
            fromDateId = '#fromDatePending';
            toDateId = '#toDatePending';
        }
        const fromDate = formatDateToDDMMYYYY($(fromDateId).val());
        const toDate = formatDateToDDMMYYYY($(toDateId).val());

        let tbodyContent = '';
        $rows.each(function() {
            tbodyContent += $(this).prop('outerHTML');
        });

        const htmlContent = `
            <div style="font-family: Arial, sans-serif; text-align: center; padding: 20px;">
                <div style="background: #00246b; color: white; padding: 20px; border-radius: 8px;">
                    <h1 style="margin: 0;">NexusHood.Com</h1>
                    <p style="margin: 5px 0;">Sea-Queen, Main Road, Moti Daman, Dadra Nagar & Haveli, Daman & Div - 396210</p>
                </div>
                <p style="margin: 20px 0; font-weight: bold;">Range: ${fromDate} to ${toDate}</p>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>${tableHead}</thead>
                    <tbody>${tbodyContent}</tbody>
                    <tfoot>${tableFooter}</tfoot>
                </table>
                <div style="background: #ffffff; color: black; padding: 10px; border-radius: 8px; margin-top: 20px; display: flex; justify-content: space-between;">
                    <span>Date: ${currentDate}</span>
                    <span>Signature: ____________________</span>
                </div>
            </div>
        `;

        const $container = $('<div></div>').css({
            position: 'absolute',
            left: '-9999px',
            top: '-9999px',
            width: '1000px'
        }).html(htmlContent);

        $('body').append($container);

        html2canvas($container[0], { scale: 2 }).then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF('p', 'mm', 'a4');
            const imgProps = pdf.getImageProperties(imgData);
            const pdfWidth = pdf.internal.pageSize.getWidth();
            const pdfHeight = pdf.internal.pageSize.getHeight();
            const contentHeight = (imgProps.height * pdfWidth) / imgProps.width;
            const footerHeight = 20;
            const pageContentHeight = pdfHeight - footerHeight;
            let heightLeft = contentHeight;
            let position = 0;

            while (heightLeft > 0) {
                const heightToRender = Math.min(pageContentHeight, heightLeft);
                pdf.addImage(imgData, 'PNG', 0, position, pdfWidth, contentHeight, null, 'FAST', 0, -position * (imgProps.height / contentHeight));
                heightLeft -= pageContentHeight;
                if (heightLeft > 0) {
                    pdf.addPage();
                    position -= pageContentHeight;
                }
            }

            pdf.save(`${filename}_${fromDate}_to_${toDate}.pdf`);
        }).catch(error => {
            console.error("PDF generation error:", error);
            alert("Failed to generate PDF.");
        }).finally(() => {
            $container.remove();
        });
    }

    window.downloadExpenseTable = function() { downloadTable("expenseTable", "expense_report"); };
    window.downloadPaymentTable = function() { downloadTable("paymentTable", "income_report"); };
    window.downloadComplaintTable = function() { downloadTable("complaintTable", "complaint_report"); };
    window.downloadPendingTable = function() { downloadTable("pendingTable", "pending_maintenance_report"); };
});
