<?
function Ora_Logon($usuario, $password)
{
        $con = oci_connect($usuario,$password);
        return $con;
}

function Ora_Open($conexion) {
        $cursor[0]=$conexion;
        return $cursor;
}

function Ora_Parse(&$cursor, $consulta) {
        $cursor[1]=oci_parse($cursor[0],$consulta);
        return $cursor;
}

function Ora_Exec(&$cursor) {
        oci_execute($cursor[1]);
        $cursor[2]=1;
        return $cursor;
}

function Ora_Fetch(&$cursor)
{
        if ($cursor[2] == 1) $cursor[2]=0;
        return oci_fetch($cursor[1]);
}

function Ora_GetColumn(&$cursor, $indice)
{
        if ($cursor[2] == 1) {
                Ora_Fetch($cursor);
                $cursor[2]=0;
        }
        $valor = oci_result($cursor[1],$indice+1);
        return $valor;
}

function Ora_Close(&$cursor)
{
        unset($cursor[1]);
}

function Ora_Logoff($conexion) {
} 

?>
