<?php
declare(strict_types=1);
namespace CitasMedicas\Shared\Utils;

/** PDF 1.4 de texto, multipágina, sin red, HTML ejecutable ni librerías externas. */
final class RecetaPdf
{
    public static function generar(array $r): string
    {
        $d=$r['datosEmision'];
        $cabecera=[$d['clinica'],'RECETA #'.$r['idReceta'].' — '.strtoupper($r['estado']),
            'Emisión: '.$r['fechaEmision'],'Paciente: '.$d['paciente'].' | CI: '.$d['cedulaIdentidad'],
            'Médico: '.$d['medico'].' | Matrícula: '.$d['matricula'],
            $d['direccionClinica'].' '.$d['telefonoClinica']];
        $lineas=[];
        foreach($r['medicamentos'] as $i=>$m){
            $lineas[]=($i+1).'. '.$m['medicamento'];
            $lineas[]='Dosis: '.$m['dosis'].' | Frecuencia: '.$m['frecuencia'];
            $lineas[]='Duración: '.$m['duracion'];$lineas[]='Indicaciones: '.$m['indicaciones'];$lineas[]='';
        }
        if($r['estado']==='anulada')$lineas[]='ANULADA: '.$r['motivoAnulacion'].' | '.$r['fechaAnulacion'];
        if($r['idRecetaAnterior']!==null)$lineas[]='Sustituye receta anulada #'.$r['idRecetaAnterior'];
        $lineas[]='Documento del sistema. No incluye firma digital certificada.';
        $envolver=static function(array $filas):array{
            $salida=[];foreach($filas as $fila){foreach(explode("\n",str_replace(["\r","\t"],['',' '],$fila)) as $linea){
                foreach(mb_str_split($linea?:' ',85) as $parte)$salida[]=$parte;
            }}return $salida;
        };
        $cabecera=$envolver($cabecera);$paginas=array_chunk($envolver($lineas),36);
        $objetos=[1=>'<< /Type /Catalog /Pages 2 0 R >>',2=>'',3=>'<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>'];$hijos=[];
        foreach($paginas as $i=>$pagina){
            $id=4+$i*2;$contenido=$id+1;$hijos[]="$id 0 R";
            $objetos[$id]="<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R >> >> /Contents $contenido 0 R >>";
            $stream="BT /F1 11 Tf 40 800 Td 15 TL\n";
            foreach([...$cabecera,'',...$pagina,'','Página '.($i+1).' de '.count($paginas)] as $texto){
                $original=$texto;$texto=mb_convert_encoding($texto,'Windows-1252','UTF-8');
                if(mb_convert_encoding($texto,'UTF-8','Windows-1252')!==$original){
                    throw new \CitasMedicas\Shared\Errors\ErrorAplicacion('El PDF requiere una fuente Unicode para algunos caracteres de esta receta. Su contenido original se conserva y puede consultarse en pantalla.',422);
                }
                $texto=str_replace(['\\','(',')'],['\\\\','\\(','\\)'],$texto);
                $texto=preg_replace('/[\x00-\x1f\x7f]/',' ',$texto);
                $stream.='('.$texto.") Tj T*\n";
            }
            $stream.="ET\n";$objetos[$contenido]='<< /Length '.strlen($stream).">>\nstream\n".$stream.'endstream';
        }
        $objetos[2]='<< /Type /Pages /Count '.count($hijos).' /Kids ['.implode(' ',$hijos).'] >>';ksort($objetos);
        $pdf="%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";$offset=[0];
        foreach($objetos as $id=>$objeto){$offset[$id]=strlen($pdf);$pdf.="$id 0 obj\n$objeto\nendobj\n";}
        $xref=strlen($pdf);$pdf.="xref\n0 ".(count($objetos)+1)."\n0000000000 65535 f \n";
        foreach($objetos as $id=>$_)$pdf.=sprintf('%010d 00000 n ',$offset[$id])."\n";
        return $pdf.'trailer << /Size '.(count($objetos)+1)." /Root 1 0 R >>\nstartxref\n$xref\n%%EOF\n";
    }
}
