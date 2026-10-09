<?php
namespace App\Services;

/** Small, dependency-free XLSX writer. Strings are always literal, never formulas. */
class TicketWorkbook {
    private function xml($value): string {
        return htmlspecialchars(preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u','',(string)$value)??'',ENT_XML1|ENT_QUOTES,'UTF-8');
    }
    private function column(int $index): string {
        $name=''; do { $name=chr(65+$index%26).$name; $index=intdiv($index,26)-1; } while($index>=0); return $name;
    }
    public function download(string $filename,array $sheets) {
        $path=tempnam(sys_get_temp_dir(),'vos-report-');
        try {
            $zip=new \ZipArchive;
            if ($zip->open($path,\ZipArchive::OVERWRITE)!==true) throw new \RuntimeException('Unable to create report.');
            $types='<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
            $workbook='<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';
            $rels='<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
            $zip->addFromString('_rels/.rels','<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
            foreach(array_values($sheets) as $index=>$sheet) {
                $id=$index+1;
                $types.='<Override PartName="/xl/worksheets/sheet'.$id.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
                $workbook.='<sheet name="'.$this->xml($sheet['name']).'" sheetId="'.$id.'" r:id="rId'.$id.'"/>';
                $rels.='<Relationship Id="rId'.$id.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$id.'.xml"/>';
                $xml='<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><cols>';
                foreach($sheet['headers'] as $c=>$header) $xml.='<col min="'.($c+1).'" max="'.($c+1).'" width="'.($sheet['widths'][$c]??24).'" customWidth="1"/>';
                $xml.='</cols><sheetData>'; $r=0;
                $allRows=(function() use($sheet) { yield $sheet['headers']; yield from $sheet['rows']; })();
                foreach($allRows as $row) {
                    $r++; $xml.='<row r="'.$r.'">';
                    foreach(array_values($row) as $c=>$value) {
                        $numeric=is_int($value)||is_float($value);
                        $style=$r===1?1:($numeric && in_array($c,$sheet['money']??[],true)?2:0);
                        $xml.='<c r="'.$this->column($c).$r.'" s="'.$style.'" t="'.($numeric?'n':'inlineStr').'">'.($numeric?'<v>'.$value.'</v>':'<is><t xml:space="preserve">'.$this->xml($value).'</t></is>').'</c>';
                    }
                    $xml.='</row>';
                }
                $xml.='</sheetData><autoFilter ref="A1:'.$this->column(count($sheet['headers'])-1).$r.'"/><pageSetup orientation="landscape" paperSize="9"/></worksheet>';
                $zip->addFromString('xl/worksheets/sheet'.$id.'.xml',$xml);
            }
            $rels.='<Relationship Id="styles" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
            $zip->addFromString('[Content_Types].xml',$types.'</Types>');
            $zip->addFromString('xl/workbook.xml',$workbook.'</sheets></workbook>');
            $zip->addFromString('xl/_rels/workbook.xml.rels',$rels);
            $zip->addFromString('xl/styles.xml','<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><numFmts count="1"><numFmt numFmtId="164" formatCode="&quot;Rp &quot;#,##0;[Red](&quot;Rp &quot;#,##0)"/></numFmts><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF30213F"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf/></cellStyleXfs><cellXfs count="3"><xf xfId="0"/><xf fontId="1" fillId="2" applyFont="1" applyFill="1" xfId="0"/><xf numFmtId="164" applyNumberFormat="1" xfId="0"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>');
            if (!$zip->close()) throw new \RuntimeException('Unable to finish report.');
            return response()->download($path,$filename,['Content-Type'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','Cache-Control'=>'private, no-store'])->deleteFileAfterSend(true);
        } catch(\Throwable $e) { if (is_file($path)) unlink($path); throw $e; }
    }
}
