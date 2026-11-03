<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Xlsx_io - pembaca & penulis .xlsx tanpa library eksternal (hanya ext-zip, xmlreader, dom).
 */
class Xlsx_io
{
    // ------------------------------------------------------------------
    // BACA
    // ------------------------------------------------------------------

    /**
     * Membaca satu sheet. Mengembalikan array baris; tiap baris = array [indeksKolom0 => nilai].
     * Baris tanpa nilai dilewati.
     * $mentah = true: angka dikembalikan persis seperti tertulis di file (teks), dan tiap baris berbentuk
     *               array('r' => nomorBarisExcel, 'v' => [indeksKolom => nilai]).
     */
    public static function baca($path, $nama_sheet, $mentah = false)
    {
        if ( ! class_exists('ZipArchive')) throw new RuntimeException('Ekstensi PHP zip belum aktif (php.ini: extension=zip).');
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) throw new RuntimeException('File bukan .xlsx yang valid.');

        // 1. cari file sheet
        $wb = $zip->getFromName('xl/workbook.xml');
        $rel = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($wb === false || $rel === false) { $zip->close(); throw new RuntimeException('Struktur .xlsx tidak dikenali.'); }

        $rid = null; $tersedia = array();
        $d = new DOMDocument(); $d->loadXML($wb);
        foreach ($d->getElementsByTagName('sheet') as $s)
        {
            $tersedia[] = $s->getAttribute('name');
            if (strcasecmp($s->getAttribute('name'), $nama_sheet) === 0)
            {
                $rid = $s->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id');
            }
        }
        if ($rid === null) { $zip->close(); throw new RuntimeException('Sheet "' . $nama_sheet . '" tidak ditemukan. Sheet yang ada: ' . implode(', ', $tersedia)); }

        $target = null;
        $d = new DOMDocument(); $d->loadXML($rel);
        foreach ($d->getElementsByTagName('Relationship') as $r)
        {
            if ($r->getAttribute('Id') === $rid) $target = $r->getAttribute('Target');
        }
        if ($target === null) { $zip->close(); throw new RuntimeException('Relasi sheet tidak ditemukan.'); }
        $target = ltrim($target, '/');
        $file = (strpos($target, 'xl/') === 0) ? $target : 'xl/' . $target;
        $zip->close();

        // 2. shared strings & gaya sel (untuk sel berformat tanggal)
        $ss = self::shared_strings($path);
        $tgl = self::gaya_tanggal($path);

        // 3. baca sheet (streaming)
        $xr = new XMLReader();
        if ( ! $xr->open('zip://' . $path . '#' . $file)) throw new RuntimeException('Gagal membuka sheet.');
        $doc = new DOMDocument();
        $rows = array();
        while ($xr->read())
        {
            if ($xr->nodeType !== XMLReader::ELEMENT || $xr->localName !== 'row') continue;
            if ($xr->isEmptyElement) continue;
            $node = $xr->expand($doc);
            $baris = array(); $ada = false;
            foreach ($node->childNodes as $c)
            {
                if ($c->localName !== 'c') continue;
                $v = self::nilai_sel($c, $ss, $mentah, $tgl);
                if ($v === null || $v === '') continue;
                $baris[self::kolom_ke_indeks($c->getAttribute('r'))] = $v;
                $ada = true;
            }
            if ($ada) $rows[] = $mentah ? array('r' => (int) $node->getAttribute('r'), 'v' => $baris) : $baris;
        }
        $xr->close();
        return $rows;
    }

    private static function shared_strings($path)
    {
        $out = array();
        $zip = new ZipArchive();
        $zip->open($path);
        $ada = $zip->locateName('xl/sharedStrings.xml') !== false;
        $zip->close();
        if ( ! $ada) return $out;

        $xr = new XMLReader();
        $xr->open('zip://' . $path . '#xl/sharedStrings.xml');
        $doc = new DOMDocument();
        while ($xr->read())
        {
            if ($xr->nodeType === XMLReader::ELEMENT && $xr->localName === 'si')
            {
                $out[] = $xr->isEmptyElement ? '' : self::teks_si($xr->expand($doc));
            }
        }
        $xr->close();
        return $out;
    }

    private static function teks_si($si)
    {
        $t = '';
        foreach ($si->getElementsByTagName('t') as $n)
        {
            // abaikan teks fonetik (rPh)
            if ($n->parentNode->localName === 'rPh') continue;
            $t .= $n->textContent;
        }
        return $t;
    }

    private static function nilai_sel($c, $ss, $mentah = false, $tgl = array())
    {
        $tipe = $c->getAttribute('t');
        if ($tipe === 'inlineStr')
        {
            foreach ($c->childNodes as $n) if ($n->localName === 'is') return self::teks_si($n);
            return null;
        }
        $v = null;
        foreach ($c->childNodes as $n) if ($n->localName === 'v') { $v = $n->textContent; break; }
        if ($v === null || $v === '') return null;
        if ($tipe === 's') return isset($ss[(int) $v]) ? $ss[(int) $v] : null;
        if ($tipe === 'str' || $tipe === 'e') return $v;
        if ($tipe === 'b') return $v === '1';
        // sel berformat tanggal: tampilkan seperti yang terlihat di Excel (mis. kualitas "3/4" yang berubah jadi "3-Apr")
        if (is_numeric($v) && $tgl && isset($tgl[(int) $c->getAttribute('s')]))
        {
            return self::format_tanggal((float) $v, $tgl[(int) $c->getAttribute('s')]);
        }
        if ($mentah) return $v;
        return is_numeric($v) ? $v + 0 : $v;
    }

    /** Nama semua sheet pada file, sesuai urutan tab di Excel. */
    public static function daftar_sheet($path)
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) throw new RuntimeException('File bukan .xlsx yang valid.');
        $wb = $zip->getFromName('xl/workbook.xml');
        $zip->close();
        if ($wb === false) throw new RuntimeException('Struktur .xlsx tidak dikenali.');
        $d = new DOMDocument(); $d->loadXML($wb);
        $out = array();
        foreach ($d->getElementsByTagName('sheet') as $s) $out[] = $s->getAttribute('name');
        return $out;
    }

    /** Peta indeks gaya sel => kode format, hanya untuk gaya yang berformat tanggal/waktu */
    private static function gaya_tanggal($path)
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) return array();
        $x = $zip->getFromName('xl/styles.xml');
        $zip->close();
        if ($x === false) return array();

        $d = new DOMDocument();
        if ( ! @$d->loadXML($x)) return array();
        $kode = array();
        foreach ($d->getElementsByTagName('numFmt') as $n) $kode[(int) $n->getAttribute('numFmtId')] = $n->getAttribute('formatCode');
        $bawaan = array(14 => 'm/d/yyyy', 15 => 'd-mmm-yy', 16 => 'd-mmm', 17 => 'mmm-yy', 18 => 'h:mm AM/PM', 19 => 'h:mm:ss AM/PM',
                        20 => 'h:mm', 21 => 'h:mm:ss', 22 => 'm/d/yyyy h:mm', 45 => 'mm:ss', 46 => '[h]:mm:ss', 47 => 'mm:ss.0');

        $out = array();
        $xfs = $d->getElementsByTagName('cellXfs')->item(0);
        if ( ! $xfs) return $out;
        $i = 0;
        foreach ($xfs->getElementsByTagName('xf') as $xf)
        {
            $id = (int) $xf->getAttribute('numFmtId');
            $c = isset($kode[$id]) ? $kode[$id] : (isset($bawaan[$id]) ? $bawaan[$id] : null);
            if ($c !== null && self::kode_tanggal($c)) $out[$i] = $c;
            $i++;
        }
        return $out;
    }

    private static function kode_tanggal($c)
    {
        $c = preg_replace('/"[^"]*"|\\\\.|\[[^\]]*\]|_.|\*./', '', $c);
        return (bool) preg_match('/[dmyhs]/i', $c);
    }

    /** Menampilkan angka seri tanggal Excel sesuai kode formatnya (mis. d-mmm => 3-Apr) */
    private static function format_tanggal($serial, $kode)
    {
        $kode = preg_replace('/\[[^\]]*\]|_.|\*./', '', $kode);
        $kode = explode(';', $kode);
        $kode = $kode[0];
        $dt = new DateTime('1899-12-30 00:00:00');
        $dt->modify('+' . (int) floor($serial) . ' days');
        $detik = (int) round(($serial - floor($serial)) * 86400);
        if ($detik > 0) $dt->modify('+' . $detik . ' seconds');

        $pakai_ampm = (bool) preg_match('/AM\/PM|A\/P/i', $kode);
        $out = ''; $n = strlen($kode); $i = 0; $terakhir_jam = false;
        while ($i < $n)
        {
            $ch = $kode[$i];
            if ($ch === '"') { $j = strpos($kode, '"', $i + 1); if ($j === false) $j = $n; $out .= substr($kode, $i + 1, $j - $i - 1); $i = $j + 1; continue; }
            if ($ch === '\\') { $out .= isset($kode[$i + 1]) ? $kode[$i + 1] : ''; $i += 2; continue; }
            $l = strtolower($ch);
            if (in_array($l, array('d', 'm', 'y', 'h', 's'), true))
            {
                $j = $i; while ($j < $n && strtolower($kode[$j]) === $l) $j++;
                $len = $j - $i; $i = $j;
                if ($l === 'd') $out .= $len === 1 ? $dt->format('j') : ($len === 2 ? $dt->format('d') : ($len === 3 ? $dt->format('D') : $dt->format('l')));
                elseif ($l === 'y') $out .= $len <= 2 ? $dt->format('y') : $dt->format('Y');
                elseif ($l === 'h') { $terakhir_jam = true; $out .= $pakai_ampm ? ($len === 1 ? $dt->format('g') : $dt->format('h')) : ($len === 1 ? $dt->format('G') : $dt->format('H')); }
                elseif ($l === 's') { $terakhir_jam = false; $out .= $len === 1 ? (string) (int) $dt->format('s') : $dt->format('s'); }
                else // m: bulan, atau menit bila setelah jam
                {
                    if ($terakhir_jam) { $out .= $len === 1 ? (string) (int) $dt->format('i') : $dt->format('i'); $terakhir_jam = false; }
                    else $out .= $len === 1 ? $dt->format('n') : ($len === 2 ? $dt->format('m') : ($len === 3 ? $dt->format('M') : $dt->format('F')));
                }
                continue;
            }
            if (preg_match('/^(AM\/PM|A\/P)/i', substr($kode, $i), $m)) { $out .= $dt->format('A'); $i += strlen($m[1]); continue; }
            $out .= $ch; $i++;
        }
        return $out;
    }

    private static function kolom_ke_indeks($ref)
    {
        $n = 0;
        for ($i = 0, $l = strlen($ref); $i < $l; $i++)
        {
            $ch = ord($ref[$i]);
            if ($ch < 65 || $ch > 90) break;
            $n = $n * 26 + ($ch - 64);
        }
        return $n - 1;
    }

    // ------------------------------------------------------------------
    // TULIS
    // ------------------------------------------------------------------

    /**
     * @param array $sheets  [ ['nama'=>, 'baris'=>[[sel,...],...], 'lebar'=>[..], 'header'=>bool,
     *                         'kolom_kuning'=>indeks|null, 'filter'=>bool, 'beku'=>bool], ... ]
     *   nilai sel: string | angka | null
     */
    public static function tulis($path, array $sheets)
    {
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new RuntimeException('Tidak dapat membuat file Excel.');

        $ct = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        $wbx = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';
        $rl  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';

        foreach ($sheets as $i => $sh)
        {
            $n = $i + 1;
            $ct  .= '<Override PartName="/xl/worksheets/sheet' . $n . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $wbx .= '<sheet name="' . self::x(mb_substr($sh['nama'], 0, 31)) . '" sheetId="' . $n . '" r:id="rId' . $n . '"/>';
            $rl  .= '<Relationship Id="rId' . $n . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $n . '.xml"/>';
            $zip->addFromString('xl/worksheets/sheet' . $n . '.xml', ! empty($sh['gaya_rh']) ? self::xml_sheet_rh($sh) : self::xml_sheet($sh));
        }
        $jml = count($sheets);
        $rl .= '<Relationship Id="rId' . ($jml + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
        $ct .= '</Types>';
        $wbx .= '</sheets></workbook>';

        $zip->addFromString('[Content_Types].xml', $ct);
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', $wbx);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $rl);
        $zip->addFromString('xl/styles.xml', self::xml_styles());
        $zip->close();
    }

    private static function xml_styles()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
          . '<numFmts count="1"><numFmt numFmtId="164" formatCode="0.00"/></numFmts>'
          . '<fonts count="5"><font><sz val="10"/><name val="Arial"/></font><font><b/><sz val="11"/><name val="Arial"/></font><font><b/><sz val="11"/><name val="Arial"/></font>'
          . '<font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts>'
          . '<fills count="9"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
          . '<fill><patternFill patternType="solid"><fgColor rgb="FFD9E1F2"/><bgColor indexed="64"/></patternFill></fill>'
          . '<fill><patternFill patternType="solid"><fgColor rgb="FFFFF9E5"/><bgColor indexed="64"/></patternFill></fill>'
          . '<fill><patternFill patternType="solid"><fgColor rgb="FFF4B183"/><bgColor indexed="64"/></patternFill></fill>'
          . '<fill><patternFill patternType="solid"><fgColor rgb="FF92D050"/><bgColor indexed="64"/></patternFill></fill>'
          . '<fill><patternFill patternType="solid"><fgColor rgb="FFCA0E63"/><bgColor indexed="64"/></patternFill></fill>'
          . '<fill><patternFill patternType="solid"><fgColor rgb="FFF8CBAD"/><bgColor indexed="64"/></patternFill></fill>'
          . '<fill><patternFill patternType="solid"><fgColor rgb="FF1BBC9B"/><bgColor indexed="64"/></patternFill></fill></fills>'
          . '<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border>'
          . '<border><left style="thin"><color rgb="FFBFC7D5"/></left><right style="thin"><color rgb="FFBFC7D5"/></right><top style="thin"><color rgb="FFBFC7D5"/></top><bottom style="thin"><color rgb="FFBFC7D5"/></bottom><diagonal/></border></borders>'
          . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
          . '<cellXfs count="13">'
          . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'                       // 0 biasa
          . '<xf numFmtId="0" fontId="2" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>' // 1 header
          . '<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'  // 2 kuning
          . '<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment vertical="top"/></xf>'  // 3 angka
          . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'         // 4 bergaris
          . '<xf numFmtId="0" fontId="4" fillId="4" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'   // 5 header oranye (RH)
          . '<xf numFmtId="0" fontId="4" fillId="5" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'   // 6 header hijau
          . '<xf numFmtId="0" fontId="4" fillId="6" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'   // 7 header magenta
          . '<xf numFmtId="0" fontId="3" fillId="7" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="left"/></xf>'   // 8 Kode Baru
          . '<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="left"/></xf>'   // 9 teks
          . '<xf numFmtId="4" fontId="3" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyFont="1"/>'   // 10 #,##0.00
          . '<xf numFmtId="2" fontId="3" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyFont="1"/>'   // 11 0.00
          . '<xf numFmtId="0" fontId="4" fillId="8" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'   // 12 header hijau tosca (tabel bulanan)
          . '</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
          . '<dxfs count="2"><dxf><fill><patternFill><bgColor rgb="FFFFC000"/></patternFill></fill></dxf><dxf><fill><patternFill><bgColor rgb="FF00B0F0"/></patternFill></fill></dxf></dxfs></styleSheet>';
    }

    private static function xml_sheet($sh)
    {
        $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $maxk = 0; foreach ($sh['baris'] as $b) $maxk = max($maxk, count($b));
        $x .= '<sheetViews><sheetView workbookViewId="0">';
        if ( ! empty($sh['beku'])) $x .= '<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>';
        $x .= '</sheetView></sheetViews><sheetFormatPr defaultRowHeight="15"/>';
        if ( ! empty($sh['lebar']))
        {
            $x .= '<cols>';
            foreach ($sh['lebar'] as $i => $w) $x .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $w . '" customWidth="1"/>';
            $x .= '</cols>';
        }
        $x .= '<sheetData>';
        foreach ($sh['baris'] as $ri => $baris)
        {
            $r = $ri + 1;
            $x .= ($r === 1 && ! empty($sh['header'])) ? '<row r="1" ht="34" customHeight="1">' : '<row r="' . $r . '">';
            $kosong_total = true;
            foreach ($baris as $ci => $v)
            {
                $ref = self::indeks_ke_kolom($ci) . $r;
                $gaya = ($r === 1 && ! empty($sh['header'])) ? 1 : 0;
                if ($gaya === 0 && isset($sh['kolom_kuning']) && $ci === $sh['kolom_kuning']) $gaya = 2;
                elseif ($gaya === 0 && ! empty($sh['garis']) && count($baris) > 0) $gaya = 4;
                if ($v === null || $v === '')
                {
                    if ($gaya) $x .= '<c r="' . $ref . '" s="' . $gaya . '"/>';
                    continue;
                }
                $kosong_total = false;
                if (is_int($v) || is_float($v))
                {
                    $x .= '<c r="' . $ref . '" s="' . ($gaya ?: 3) . '"><v>' . $v . '</v></c>';
                }
                else
                {
                    $x .= '<c r="' . $ref . '" s="' . $gaya . '" t="inlineStr"><is><t xml:space="preserve">' . self::x($v) . '</t></is></c>';
                }
            }
            $x .= '</row>';
        }
        $x .= '</sheetData>';
        if ( ! empty($sh['filter']) && $maxk > 0) $x .= '<autoFilter ref="A1:' . self::indeks_ke_kolom($maxk - 1) . count($sh['baris']) . '"/>';
        $x .= '</worksheet>';
        return $x;
    }

    /** Sheet RH: tata letak dan warna mengikuti sheet RH di Excel BPS (baris 1 kosong, judul baris 2, data mulai baris 3, beku di A3) */
    private static function xml_sheet_rh($sh)
    {
        $kepala = array(5, 6, 7, 5, 5, 6, 6, 7, 7, 7, 9, 12, 12, 12, 12);    // oranye, hijau, magenta (A-J), Column1, lalu hijau tosca (L-O dan bulan)
        $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $x .= '<sheetViews><sheetView workbookViewId="0"><pane ySplit="2" topLeftCell="A3" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><sheetFormatPr defaultRowHeight="15"/>';
        $x .= '<cols>';
        foreach ($sh['lebar'] as $i => $w) $x .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $w . '" customWidth="1"/>';
        $x .= '</cols><sheetData><row r="1"/>';
        $x .= '<row r="2" ht="78" customHeight="1">';
        foreach ($sh['baris'][0] as $ci => $v)
            $x .= '<c r="' . self::indeks_ke_kolom($ci) . '2" s="' . (isset($kepala[$ci]) ? $kepala[$ci] : 12) . '" t="inlineStr"><is><t xml:space="preserve">' . self::x($v) . '</t></is></c>';
        $x .= '</row>';
        $r = 2;
        foreach (array_slice($sh['baris'], 1) as $baris)
        {
            $r++;
            $x .= '<row r="' . $r . '">';
            foreach ($baris as $ci => $v)
            {
                $ref = self::indeks_ke_kolom($ci) . $r;
                if ($ci === 1 || $ci === 12 || $ci === 13) $gaya = 8;
                elseif ($ci <= 2 || ($ci >= 10 && $ci <= 14)) $gaya = 9;
                elseif ($ci === 9 || $ci >= 15) $gaya = 11;
                else $gaya = 10;
                if ($v === null || $v === '') { if ($gaya === 8) $x .= '<c r="' . $ref . '" s="8"/>'; continue; }
                if (is_int($v) || is_float($v)) $x .= '<c r="' . $ref . '" s="' . $gaya . '"><v>' . $v . '</v></c>';
                elseif (($ci <= 1 || ($ci >= 11 && $ci <= 13)) && ctype_digit((string) $v) && strlen($v) <= 15 && $v[0] !== '0') $x .= '<c r="' . $ref . '" s="' . $gaya . '"><v>' . $v . '</v></c>';
                else $x .= '<c r="' . $ref . '" s="' . $gaya . '" t="inlineStr"><is><t xml:space="preserve">' . self::x($v) . '</t></is></c>';
            }
            $x .= '</row>';
        }
        $kol_akhir = self::indeks_ke_kolom(max(9, count($sh['baris'][0]) - 1));
        $x .= '</sheetData><autoFilter ref="A2:' . $kol_akhir . max(2, $r) . '"/>';
        if ($r >= 3) $x .= '<conditionalFormatting sqref="J3:J' . $r . '"><cfRule type="cellIs" dxfId="0" priority="2" operator="lessThanOrEqual"><formula>-3</formula></cfRule><cfRule type="cellIs" dxfId="1" priority="1" operator="greaterThanOrEqual"><formula>3</formula></cfRule></conditionalFormatting>';
        $x .= '</worksheet>';
        return $x;
    }

    private static function indeks_ke_kolom($i)
    {
        $s = '';
        for ($i = $i + 1; $i > 0; $i = intdiv($i - 1, 26)) $s = chr(65 + ($i - 1) % 26) . $s;
        return $s;
    }

    private static function x($t)
    {
        $t = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string) $t);
        return htmlspecialchars($t, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}