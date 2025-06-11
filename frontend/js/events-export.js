// Export and Search functionality for events table
// Location: frontend/js/events-export.js

// Export functionality for the events table
const ExportManager = {
    exportToCSV() {
        try {
            const table = $('#eventsTable').DataTable();
            const data = table.data().toArray();
            let csv = 'Title,Start Date/Time,End Date/Time,Duration,Owner,Status\n';
            
            data.forEach(row => {
                // Extract text content from HTML elements
                const title = this.extractText(row[0]);
                const start = this.extractText(row[1]);
                const end = this.extractText(row[2]);
                const duration = this.extractText(row[3]);
                const owner = this.extractText(row[4]);
                const status = this.extractText(row[5]);
                
                csv += `"${title}","${start}","${end}","${duration}","${owner}","${status}"\n`;
            });
            
            this.downloadFile(csv, 'events_export.csv', 'text/csv');
        } catch (error) {
            console.error('CSV export error:', error);
            alert('Failed to export CSV. Please try again.');
        }
    },
    
    exportToExcel() {
        try {
            // Use DataTables built-in export functionality if available
            const table = $('#eventsTable').DataTable();
            const buttons = table.buttons();
            const excelButton = buttons.filter(button => button.text() === '📊 Export Excel');
            if (excelButton.length > 0) {
                excelButton.trigger();
            } else {
                // Fallback to CSV export
                this.exportToCSV();
            }
        } catch (error) {
            console.error('Excel export error:', error);
            this.exportToCSV(); // Fallback
        }
    },
    
    exportToPDF() {
        try {
            const table = $('#eventsTable').DataTable();
            const buttons = table.buttons();
            const pdfButton = buttons.filter(button => button.text() === '📑 Export PDF');
            if (pdfButton.length > 0) {
                pdfButton.trigger();
            } else {
                // Fallback to print
                this.printTable();
            }
        } catch (error) {
            console.error('PDF export error:', error);
            this.printTable(); // Fallback
        }
    },
    
    printTable() {
        try {
            const table = $('#eventsTable').DataTable();
            const buttons = table.buttons();
            const printButton = buttons.filter(button => button.text() === '🖨️ Print');
            if (printButton.length > 0) {
                printButton.trigger();
            } else {
                // Fallback to window print
                window.print();
            }
        } catch (error) {
            console.error('Print error:', error);
            window.print(); // Fallback
        }
    },
    
    extractText(html) {
        if (typeof html === 'string') {
            const div = document.createElement('div');
            div.innerHTML = html;
            return div.textContent || div.innerText || '';
        }
        return html || '';
    },
    
    downloadFile(content, filename, type) {
        const blob = new Blob([content], { type: type });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }
};

// Initialize export and search functionality when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Export button event listeners
    const exportCSVBtn = document.querySelector('.export-csv');
    if (exportCSVBtn) {
        exportCSVBtn.addEventListener('click', () => ExportManager.exportToCSV());
    }
    
    const exportExcelBtn = document.querySelector('.export-excel');
    if (exportExcelBtn) {
        exportExcelBtn.addEventListener('click', () => ExportManager.exportToExcel());
    }
    
    const exportPDFBtn = document.querySelector('.export-pdf');
    if (exportPDFBtn) {
        exportPDFBtn.addEventListener('click', () => ExportManager.exportToPDF());
    }
    
    const exportPrintBtn = document.querySelector('.export-print');
    if (exportPrintBtn) {
        exportPrintBtn.addEventListener('click', () => ExportManager.printTable());
    }
    
    // Search box functionality
    const searchInput = document.getElementById('searchEvents');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            try {
                const table = $('#eventsTable').DataTable();
                table.search(this.value).draw();
            } catch (error) {
                console.error('Search error:', error);
            }
        });
    }
    
    // Logout button functionality
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to logout?')) {
                // Basic logout - redirect to login page
                window.location.href = './login.html';
            }
        });
    }
});