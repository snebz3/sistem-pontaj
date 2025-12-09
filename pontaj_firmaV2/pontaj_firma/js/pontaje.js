// js/pontaje.js - Funcționalități specifice pentru modulul Pontaje

class PontajeManager {
    constructor() {
        this.initDatePickers();
        this.initFilters();
        this.initExportButtons();
    }
    
    // Initializează date pickers cu configurare
    initDatePickers() {
        // Dacă folosești datepicker extern (ex: flatpickr)
        if (typeof flatpickr !== 'undefined') {
            flatpickr('input[type="date"]', {
                dateFormat: 'Y-m-d',
                locale: 'ro'
            });
        }
    }
    
    // Gestionează filtrele dinamice
    initFilters() {
        const filterForm = document.getElementById('filterForm');
        if (filterForm) {
            // Resetare rapidă filtre
            document.querySelectorAll('.btn-reset-filter').forEach(btn => {
                btn.addEventListener('click', () => {
                    filterForm.reset();
                    filterForm.submit();
                });
            });
            
            // Auto-submit la schimbare anumite filtre
            document.querySelectorAll('.auto-submit').forEach(select => {
                select.addEventListener('change', () => {
                    filterForm.submit();
                });
            });
        }
    }
    
    // Gestionează butoanele de export
    initExportButtons() {
        document.querySelectorAll('.btn-export').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const format = e.target.dataset.format || 'excel';
                this.exportData(format);
            });
        });
    }
    
    // Exportă datele în formatul specificat
    exportData(format) {
        const form = document.getElementById('filterForm');
        const formData = new FormData(form);
        
        // Adaugă parametrul format
        formData.append('format', format);
        
        // Trimite cererea
        fetch('export.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.blob())
        .then(blob => {
            // Crează link pentru download
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `istoric_pontaje_${new Date().toISOString().slice(0,10)}.${format}`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        })
        .catch(error => {
            console.error('Eroare export:', error);
            alert('A apărut o eroare la export. Vă rugăm încercați din nou.');
        });
    }
    
    // Afișează detalii pontaj într-un modal
    showDetailsModal(pontajId) {
        // Încarcă detaliile pontajului prin AJAX
        fetch(`api/get_pontaj_details.php?id=${pontajId}`)
            .then(response => response.json())
            .then(data => {
                // Populează și afișează modal
                this.populateDetailsModal(data);
                $('#pontajDetailsModal').modal('show');
            })
            .catch(error => {
                console.error('Eroare încărcare detalii:', error);
                alert('Nu s-au putut încărca detaliile pontajului.');
            });
    }
    
    populateDetailsModal(data) {
        // Implementare pentru popularea modalului cu date
        document.getElementById('modalAngajat').textContent = data.angajat_nume;
        document.getElementById('modalData').textContent = data.data_formatata;
        document.getElementById('modalTip').textContent = data.tip_pontaj === 'in' ? 'Intrare' : 'Ieșire';
        document.getElementById('modalDispozitiv').textContent = data.dispozitiv;
        document.getElementById('modalStatus').textContent = data.status;
        document.getElementById('modalIP').textContent = data.ip_address;
        document.getElementById('modalLocatie').textContent = data.locatie || 'Nespecificat';
    }
    
    // Calculează statistici
    calculateStats(pontajeData) {
        const stats = {
            total: pontajeData.length,
            intrari: pontajeData.filter(p => p.tip_pontaj === 'in').length,
            iesiri: pontajeData.filter(p => p.tip_pontaj === 'out').length,
            angajatiUnici: new Set(pontajeData.map(p => p.angajat_id)).size,
            intarzieri: pontajeData.filter(p => p.status === 'late').length
        };
        
        return stats;
    }
}

// Inițializare când DOM-ul e gata
document.addEventListener('DOMContentLoaded', function() {
    window.pontajeManager = new PontajeManager();
    
    // Adaugă tooltip-uri
    $('[data-toggle="tooltip"]').tooltip();
    
    // Initializează DataTables dacă este disponibil
    if ($.fn.DataTable) {
        $('#pontajeTable').DataTable({
            pageLength: 25,
            order: [[0, 'desc']],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Romanian.json'
            },
            dom: '<"top"f>rt<"bottom"lip><"clear">'
        });
    }
});

// Funcții globale pentru acces ușor
function showPontajDetails(id) {
    window.pontajeManager.showDetailsModal(id);
}

function exportPontaje(format) {
    window.pontajeManager.exportData(format);
}

// Utilitate: Formatare dată
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('ro-RO', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}