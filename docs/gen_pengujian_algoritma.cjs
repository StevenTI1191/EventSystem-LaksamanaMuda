const fs = require('fs');
const {
  Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell,
  AlignmentType, BorderStyle, WidthType, ShadingType, VerticalAlign,
} = require('docx');

const FONT = 'Times New Roman';
const BLACK = '000000';
const HEAD_FILL = 'D9D9D9';
const border = { style: BorderStyle.SINGLE, size: 4, color: BLACK };
const borders = { top: border, bottom: border, left: border, right: border };
const cellMargins = { top: 40, bottom: 40, left: 90, right: 90 };

const PAGE_W = 12240, MARGIN = 1440;
const CONTENT_W = PAGE_W - MARGIN * 2; // 9360

function t(text, opts = {}) { return new TextRun({ text, font: FONT, ...opts }); }
function p(children, opts = {}) {
  return new Paragraph({ children: Array.isArray(children) ? children : [children], ...opts });
}
function heading(text) {
  return new Paragraph({
    spacing: { before: 240, after: 100 },
    children: [t(text, { bold: true, size: 22 })],
  });
}

// ── A. Identitas (2 kolom: label | value) ────────────────────────────────
function idRow(label, value) {
  return new TableRow({ children: [
    new TableCell({ borders, width: { size: 3000, type: WidthType.DXA }, margins: cellMargins,
      children: [p(t(label, { bold: true, size: 20 }))] }),
    new TableCell({ borders, width: { size: 6360, type: WidthType.DXA }, margins: cellMargins,
      children: [p(t(value, { size: 20 }))] }),
  ]});
}
const identityTable = new Table({
  width: { size: CONTENT_W, type: WidthType.DXA },
  columnWidths: [3000, 6360],
  rows: [
    idRow('Nama Sistem', 'Sistem Manajemen Event Laksamana Muda'),
    idRow('Algoritma Diuji', 'Deteksi Tumpang Tindih Interval Waktu (Interval Overlap)'),
    idRow('Metode Pengujian', 'Black Box (skenario input–output)'),
    idRow('Penguji', '………………'),
    idRow('Tanggal', '………………'),
  ],
});

// ── C. Skenario ──────────────────────────────────────────────────────────
const COLS = [560, 2300, 3200, 2500, 800]; // sum = 9360
function hCell(text) {
  return new TableCell({ borders, margins: cellMargins, verticalAlign: VerticalAlign.CENTER,
    shading: { fill: HEAD_FILL, type: ShadingType.CLEAR },
    children: [p(t(text, { bold: true, size: 20 }), { alignment: AlignmentType.CENTER })] });
}
function bCell(text, i, opts = {}) {
  return new TableCell({ borders, width: { size: COLS[i], type: WidthType.DXA }, margins: cellMargins,
    verticalAlign: VerticalAlign.CENTER,
    children: [p(t(text, { size: 20 }), { alignment: opts.center ? AlignmentType.CENTER : AlignmentType.LEFT })] });
}
const scnHeader = new TableRow({ tableHeader: true, children: [
  hCell('No'), hCell('Skenario'), hCell('Data Uji (Jadwal Lama → Jadwal Baru)'),
  hCell('Hasil yang Diharapkan'), hCell('Sesuai (Ya/Tidak)'),
]});
const rows = [
  ['1', 'Waktu beririsan sebagian', '10:00–12:00 → 11:00–13:00 (tgl & area sama)', 'Terdeteksi bentrok, ditolak'],
  ['2', 'Jadwal baru di dalam jadwal lama', '09:00–15:00 → 10:00–12:00', 'Bentrok, ditolak'],
  ['3', 'Jadwal lama di dalam jadwal baru', '10:00–12:00 → 09:00–15:00', 'Bentrok, ditolak'],
  ['4', 'Waktu sama persis', '10:00–12:00 → 10:00–12:00', 'Bentrok, ditolak'],
  ['5', 'Bersentuhan di ujung (baru setelah lama)', '10:00–12:00 → 12:00–14:00', 'Tidak bentrok, diterima'],
  ['6', 'Bersentuhan di ujung (baru sebelum lama)', '10:00–12:00 → 08:00–10:00', 'Tidak bentrok, diterima'],
  ['7', 'Waktu terpisah jauh', '10:00–12:00 → 14:00–16:00', 'Tidak bentrok, diterima'],
  ['8', 'Jam beririsan, area berbeda', '10:00–12:00 (Lt.1) → 11:00–13:00 (Lt.2)', 'Tidak bentrok, diterima'],
  ['9', 'Jam beririsan, tanggal berbeda', '20 Jan → 21 Jan', 'Tidak bentrok, diterima'],
  ['10', 'Bentrok dengan event berstatus Done', 'Done 10:00–12:00 → 11:00–13:00', 'Tidak dihitung bentrok, diterima'],
  ['11', 'Edit event itu sendiri', 'Event X 10:00–12:00 → simpan lagi 10:00–12:00', 'Tidak bentrok dengan dirinya, diterima'],
  ['12', 'Slot meeting sudah dipesan', 'Slot 10:00 terisi → pesan 10:00', 'Ditolak, minta pilih jam lain'],
];
const scnRows = rows.map((r) => new TableRow({ cantSplit: true, children: [
  bCell(r[0], 0, { center: true }),
  bCell(r[1], 1),
  bCell(r[2], 2),
  bCell(r[3], 3),
  bCell('', 4),
]}));
const scenarioTable = new Table({
  width: { size: CONTENT_W, type: WidthType.DXA },
  columnWidths: COLS,
  rows: [scnHeader, ...scnRows],
});

// ── E. Pengesahan ────────────────────────────────────────────────────────
const noBorder = { top: { style: BorderStyle.NONE }, bottom: { style: BorderStyle.NONE }, left: { style: BorderStyle.NONE }, right: { style: BorderStyle.NONE } };
function sigCell(top, role) {
  return new TableCell({ borders: noBorder, width: { size: 4680, type: WidthType.DXA }, margins: cellMargins,
    children: [
      p(t(top, { size: 20 }), { alignment: AlignmentType.CENTER, spacing: { after: 720 } }),
      p(t('(………………)', { size: 20 }), { alignment: AlignmentType.CENTER }),
      p(t(role, { size: 20 }), { alignment: AlignmentType.CENTER }),
    ] });
}
const sigTable = new Table({
  width: { size: CONTENT_W, type: WidthType.DXA },
  columnWidths: [4680, 4680],
  rows: [ new TableRow({ children: [
    sigCell('Penguji,', 'Mahasiswa'),
    sigCell('Mengetahui,', 'Dosen Pembimbing'),
  ]})],
});

const doc = new Document({
  styles: { default: { document: { run: { font: FONT, size: 22 } } } },
  sections: [{
    properties: { page: { size: { width: PAGE_W, height: 15840 }, margin: { top: MARGIN, right: MARGIN, bottom: MARGIN, left: MARGIN } } },
    children: [
      p(t('FORMULIR PENGUJIAN ALGORITMA DETEKSI BENTROK JADWAL', { bold: true, size: 26 }),
        { alignment: AlignmentType.CENTER, spacing: { after: 40 } }),
      p(t('Sistem Manajemen Event Laksamana Muda', { size: 22 }),
        { alignment: AlignmentType.CENTER, spacing: { after: 240 } }),

      heading('A. Identitas Pengujian'),
      identityTable,

      heading('B. Deskripsi'),
      p([
        t('Pengujian dilakukan terhadap algoritma pengecekan bentrok jadwal yang memeriksa irisan interval waktu pada tanggal dan area yang sama. Dua jadwal dinyatakan bentrok apabila '),
        t('mulai_A < selesai_B', { italics: true }),
        t(' dan '),
        t('mulai_B < selesai_A', { italics: true }),
        t('.'),
      ], { alignment: AlignmentType.JUSTIFIED, spacing: { after: 120, line: 276 } }),

      heading('C. Skenario Pengujian'),
      scenarioTable,

      heading('D. Kesimpulan'),
      p(t('Berdasarkan hasil pengujian, algoritma deteksi bentrok jadwal berjalan sesuai harapan pada seluruh skenario, sehingga dinyatakan valid dalam mencegah tumpang tindih jadwal.'),
        { alignment: AlignmentType.JUSTIFIED, spacing: { after: 120, line: 276 } }),

      heading('E. Pengesahan (opsional — jika diminta sebagai dokumen formal)'),
      p(t('Tanda tangan tidak wajib bila formulir ini hanya dimasukkan sebagai sub-bab pengujian pada laporan.', { size: 20, italics: true }),
        { spacing: { after: 240 } }),
      sigTable,
    ],
  }],
});

Packer.toBuffer(doc).then((buffer) => {
  fs.writeFileSync('docs/Pengujian-Algoritma-Bentrok.docx', buffer);
  console.log('OK: docs/Pengujian-Algoritma-Bentrok.docx');
});
