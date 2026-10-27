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
     */
    public static function baca($path, $nama_sheet)
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

        // 2. shared strings
        $ss = self::shared_strings($path);

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
                $v = self::nilai_sel($c, $ss);
                if ($v === null || $v === '') continue;
                $baris[self::kolom_ke_indeks($c->getAttribute('r'))] = $v;
                $ada = true;
            }
            if ($ada) $rows[] = $baris;
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

    private static function nilai_sel($c, $ss)
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
        return is_numeric($v) ? $v + 0 : $v;
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
            $zip->addFromString('xl/worksheets/sheet' . $n . '.xml', self::xml_sheet($sh));
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
          . '<fonts count="3"><font><sz val="10"/><name val="Arial"/></font><font><b/><sz val="11"/><name val="Arial"/></font><font><b/><sz val="11"/><name val="Arial"/></font></fonts>'
          . '<fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
          . '<fill><patternFill patternType="solid"><fgColor rgb="FFD9E1F2"/><bgColor indexed="64"/></patternFill></fill>'
          . '<fill><patternFill patternType="solid"><fgColor rgb="FFFFF9E5"/><bgColor indexed="64"/></patternFill></fill></fills>'
          . '<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border>'
          . '<border><left style="thin"><color rgb="FFBFC7D5"/></left><right style="thin"><color rgb="FFBFC7D5"/></right><top style="thin"><color rgb="FFBFC7D5"/></top><bottom style="thin"><color rgb="FFBFC7D5"/></bottom><diagonal/></border></borders>'
          . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
          . '<cellXfs count="5">'
          . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'                       // 0 biasa
          . '<xf numFmtId="0" fontId="2" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>' // 1 header
          . '<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'  // 2 kuning
          . '<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment vertical="top"/></xf>'  // 3 angka
          . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'         // 4 bergaris
          . '</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
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
