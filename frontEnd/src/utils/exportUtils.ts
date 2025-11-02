import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import { Treatment } from '../types';

export const exportTreatmentsToPDF = (treatments: Treatment[]) => {
  const doc = new jsPDF();
  
  // Title
  doc.setFontSize(20);
  doc.text('Registre des Traitements RGPD', 20, 20);
  
  // Date
  doc.setFontSize(12);
  doc.text(`Généré le ${new Date().toLocaleDateString('fr-FR')}`, 20, 30);
  
  // Table data
  const tableData = treatments.map(treatment => [
    treatment.nomTraitement,
    treatment.service,
    treatment.baseJuridique,
    treatment.etatTraitement,
    new Date(treatment.derniereMiseAJour).toLocaleDateString('fr-FR'),
  ]);
  
  // Generate table
  autoTable(doc, {
    head: [['Nom du traitement', 'Service', 'Base juridique', 'État', 'Dernière MAJ']],
    body: tableData,
    startY: 40,
    styles: {
      fontSize: 8,
      cellPadding: 3,
    },
    headStyles: {
      fillColor: [99, 102, 241], // primary-600
      textColor: 255,
    },
    alternateRowStyles: {
      fillColor: [248, 250, 252], // secondary-50
    },
  });
  
  // Save the PDF
  doc.save(`registre-traitements-${new Date().toISOString().split('T')[0]}.pdf`);
};

export const exportTreatmentsToCSV = (treatments: Treatment[]) => {
  const headers = [
    'Nom du traitement',
    'Service',
    'Finalité',
    'Base juridique',
    'Responsable du traitement',
    'Référent RGPD',
    'État',
    'Dernière MAJ',
  ];
  
  const csvContent = [
    headers,
    ...treatments.map(t => [
      t.nomTraitement,
      t.service,
      t.finalite,
      t.baseJuridique,
      t.responsableTraitement,
      t.referentRGPD,
      t.etatTraitement,
      new Date(t.derniereMiseAJour).toLocaleDateString('fr-FR'),
    ])
  ].map(row => row.map(cell => `"${cell}"`).join(',')).join('\n');

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  link.setAttribute('href', url);
  link.setAttribute('download', `registre-traitements-${new Date().toISOString().split('T')[0]}.csv`);
  link.style.visibility = 'hidden';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

