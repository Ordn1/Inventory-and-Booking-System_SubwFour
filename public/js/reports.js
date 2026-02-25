/**
 * Reports Page JavaScript
 * Handles PDF generation and dynamic functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    // PDF Generation
    const pdfBtn = document.getElementById('generatePdfBtn');
    if (pdfBtn) {
        pdfBtn.addEventListener('click', generatePDF);
    }
});

/**
 * Generate PDF Report using jsPDF
 */
async function generatePDF() {
    const btn = document.getElementById('generatePdfBtn');
    const originalText = btn.innerHTML;
    
    try {
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Generating...';
        btn.disabled = true;

        // Wait for jsPDF to load
        await waitForJsPDF();

        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('p', 'mm', 'a4');
        
        // Get report data
        const summaryData = JSON.parse(document.getElementById('reportSummary').textContent);
        const topItemsData = JSON.parse(document.getElementById('topItemsData').textContent);
        
        const pageWidth = doc.internal.pageSize.getWidth();
        const margin = 15;
        let yPos = 20;

        // Header
        doc.setFillColor(30, 30, 30);
        doc.rect(0, 0, pageWidth, 35, 'F');
        
        doc.setTextColor(239, 53, 53);
        doc.setFontSize(22);
        doc.setFont('helvetica', 'bold');
        doc.text('SubWFour', margin, 18);
        
        doc.setTextColor(255, 255, 255);
        doc.setFontSize(12);
        doc.setFont('helvetica', 'normal');
        doc.text('Monthly Report', margin, 28);
        
        doc.setFontSize(10);
        doc.text(summaryData.month, pageWidth - margin, 23, { align: 'right' });
        doc.text('Generated: ' + new Date().toLocaleDateString(), pageWidth - margin, 30, { align: 'right' });

        yPos = 45;

        // Summary Section
        doc.setTextColor(60, 60, 60);
        doc.setFontSize(14);
        doc.setFont('helvetica', 'bold');
        doc.text('Executive Summary', margin, yPos);
        yPos += 10;

        // Key Metrics Table
        doc.autoTable({
            startY: yPos,
            head: [['Metric', 'Value']],
            body: [
                ['Total Revenue', formatCurrency(summaryData.totalRevenue)],
                ['Total Bookings', summaryData.totalBookings.toString()],
                ['Services Completed', summaryData.servicesCompleted.toString()],
                ['Average Service Value', formatCurrency(summaryData.avgServiceValue)],
            ],
            theme: 'striped',
            headStyles: { 
                fillColor: [239, 53, 53],
                textColor: 255,
                fontStyle: 'bold'
            },
            styles: {
                fontSize: 10,
                cellPadding: 5
            },
            columnStyles: {
                0: { fontStyle: 'bold', cellWidth: 80 },
                1: { halign: 'right', cellWidth: 60 }
            },
            margin: { left: margin, right: margin }
        });

        yPos = doc.lastAutoTable.finalY + 15;

        // Revenue Breakdown
        doc.setFontSize(14);
        doc.setFont('helvetica', 'bold');
        doc.text('Revenue Breakdown', margin, yPos);
        yPos += 10;

        doc.autoTable({
            startY: yPos,
            head: [['Category', 'Amount', 'Percentage']],
            body: [
                [
                    'Labor Fees', 
                    formatCurrency(summaryData.laborFeeTotal),
                    summaryData.totalRevenue > 0 
                        ? ((summaryData.laborFeeTotal / summaryData.totalRevenue) * 100).toFixed(1) + '%'
                        : '0%'
                ],
                [
                    'Parts & Items', 
                    formatCurrency(summaryData.partsRevenue),
                    summaryData.totalRevenue > 0 
                        ? ((summaryData.partsRevenue / summaryData.totalRevenue) * 100).toFixed(1) + '%'
                        : '0%'
                ],
            ],
            theme: 'striped',
            headStyles: { 
                fillColor: [59, 130, 246],
                textColor: 255,
                fontStyle: 'bold'
            },
            styles: {
                fontSize: 10,
                cellPadding: 5
            },
            margin: { left: margin, right: margin }
        });

        yPos = doc.lastAutoTable.finalY + 15;

        // Inventory Summary
        doc.setFontSize(14);
        doc.setFont('helvetica', 'bold');
        doc.text('Inventory Summary', margin, yPos);
        yPos += 10;

        doc.autoTable({
            startY: yPos,
            head: [['Metric', 'Value']],
            body: [
                ['Stock-In Value', formatCurrency(summaryData.stockInValue)],
                ['Current Inventory Value', formatCurrency(summaryData.currentInventoryValue)],
                ['Low Stock Items', summaryData.lowStockItems.toString()],
                ['Total Suppliers', summaryData.totalSuppliers.toString()],
                ['New Suppliers Added', summaryData.suppliersAdded.toString()],
            ],
            theme: 'striped',
            headStyles: { 
                fillColor: [34, 197, 94],
                textColor: 255,
                fontStyle: 'bold'
            },
            styles: {
                fontSize: 10,
                cellPadding: 5
            },
            columnStyles: {
                0: { fontStyle: 'bold', cellWidth: 80 },
                1: { halign: 'right', cellWidth: 60 }
            },
            margin: { left: margin, right: margin }
        });

        yPos = doc.lastAutoTable.finalY + 15;

        // Check if we need a new page
        if (yPos > 230) {
            doc.addPage();
            yPos = 20;
        }

        // Top Items Used
        if (topItemsData && topItemsData.length > 0) {
            doc.setFontSize(14);
            doc.setFont('helvetica', 'bold');
            doc.text('Top Items Used', margin, yPos);
            yPos += 10;

            const itemsBody = topItemsData.map(item => [
                item.item_id,
                item.name,
                item.total_qty.toString(),
                formatCurrency(parseFloat(item.total_revenue))
            ]);

            doc.autoTable({
                startY: yPos,
                head: [['Item ID', 'Name', 'Qty Used', 'Revenue']],
                body: itemsBody,
                theme: 'striped',
                headStyles: { 
                    fillColor: [234, 179, 8],
                    textColor: 30,
                    fontStyle: 'bold'
                },
                styles: {
                    fontSize: 9,
                    cellPadding: 4
                },
                columnStyles: {
                    3: { halign: 'right' }
                },
                margin: { left: margin, right: margin }
            });
        }

        // Footer
        const pageCount = doc.internal.getNumberOfPages();
        for (let i = 1; i <= pageCount; i++) {
            doc.setPage(i);
            doc.setFontSize(8);
            doc.setTextColor(150, 150, 150);
            doc.text(
                `Page ${i} of ${pageCount} | SubWFour Inventory System | Confidential`,
                pageWidth / 2,
                doc.internal.pageSize.getHeight() - 10,
                { align: 'center' }
            );
        }

        // Save the PDF
        const fileName = `SubWFour_Report_${summaryData.month.replace(' ', '_')}.pdf`;
        doc.save(fileName);

        btn.innerHTML = '<i class="bi bi-check-circle"></i> Downloaded!';
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }, 2000);

    } catch (error) {
        console.error('PDF Generation Error:', error);
        btn.innerHTML = '<i class="bi bi-x-circle"></i> Error';
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }, 2000);
        alert('Failed to generate PDF. Please try again.');
    }
}

/**
 * Wait for jsPDF library to load
 */
function waitForJsPDF() {
    return new Promise((resolve, reject) => {
        if (window.jspdf) {
            resolve();
            return;
        }
        
        let attempts = 0;
        const maxAttempts = 50;
        
        const checkInterval = setInterval(() => {
            attempts++;
            if (window.jspdf) {
                clearInterval(checkInterval);
                resolve();
            } else if (attempts >= maxAttempts) {
                clearInterval(checkInterval);
                reject(new Error('jsPDF failed to load'));
            }
        }, 100);
    });
}

/**
 * Format number as Philippine Peso currency
 */
function formatCurrency(amount) {
    return '₱' + parseFloat(amount).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}
