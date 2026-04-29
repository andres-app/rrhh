<?php
while (ob_get_level()) {
    ob_end_clean();
}

require_once ROOT_PATH . 'Controlador/CtrDirectorio.php';
require_once ROOT_PATH . 'Modelo/MdDirectorio.php';

$controlador = new CtrDirectorio();

if (!method_exists($controlador, 'ctrMostrarDirectorioExcel')) {
    exit('Falta el método ctrMostrarDirectorioExcel() en CtrDirectorio.');
}

$empleados = $controlador->ctrMostrarDirectorioExcel();

if (!is_array($empleados)) {
    $empleados = [];
}

$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $qLower = mb_strtolower($q, 'UTF-8');

    $empleados = array_values(array_filter($empleados, function ($row) use ($qLower) {
        $texto = mb_strtolower(implode(' ', array_map(function ($v) {
            return is_scalar($v) || $v === null ? (string)$v : '';
        }, $row)), 'UTF-8');

        return mb_strpos($texto, $qLower) !== false;
    }));
}

$columnas = [
    'dni'                    => 'DNI',
    'nombres_apellidos'      => 'NOMBRES Y APELLIDOS',
    'ruc'                    => 'RUC',
    'licencia_conducir'      => 'LICENCIA',
    'fecha_nacimiento'       => 'FECHA NAC.',
    'lugar_nacimiento'       => 'LUGAR NAC.',
    'edad'                   => 'EDAD',
    'sexo'                   => 'SEXO',
    'estado_civil'           => 'ESTADO CIVIL',
    'grupo_sanguineo'        => 'GRUPO SANG.',
    'talla'                  => 'TALLA',
    'grado_militar'          => 'GRADO MILITAR',
    'celular'                => 'CELULAR',
    'correo_personal'        => 'CORREO PERSONAL',
    'direccion_residencia'   => 'DIRECCIÓN',
    'distrito'               => 'DISTRITO',
    'nsa_cip'                => 'NSA-CIP',
    'correo_institucional'   => 'CORREO INSTITUCIONAL',
    'situacion'              => 'SITUACIÓN',
    'sueldo'                 => 'SUELDO',
    'modalidad_contrato'     => 'MODALIDAD',
    'puesto_cas'             => 'PUESTO CAS',
    'tipo_puesto'            => 'TIPO PUESTO',
    'area'                   => 'ÁREA',
    'procedencia'            => 'PROCEDENCIA',
    'fecha_ingreso'          => 'FECHA INGRESO',
    'fecha_cese'             => 'FECHA CESE',
    'n_hijos'                => 'N° DE HIJOS',
    'conyuge'                => 'CONYUGE',
    'onomastico_conyuge'     => 'ONOMÁSTICO CONYUGE',
    'profesion'              => 'PROFESIÓN',
    'institucion'            => 'INSTITUCIÓN',
    'grado'                  => 'GRADO',
    'curso_especializacion'  => 'CURSO DE ESPECIALIZACIÓN',
    'sistema_pension'        => 'SISTEMA PENSIÓN',
    'afp'                    => 'AFP',
    'cuspp'                  => 'CUSPP',
    'tipo_comision'          => 'TIPO COMISIÓN',
    'fecha_inscripcion'      => 'FECHA INSCRIPCIÓN',
    'sin_afp_afiliarme'      => 'SIN AFP',
    'banco_haberes'          => 'BANCO',
    'numero_cuenta'          => 'N° CUENTA',
    'numero_cuenta_cci'      => 'CCI',
];

$camposTextoForzado = [
    'dni',
    'ruc',
    'licencia_conducir',
    'celular',
    'nsa_cip',
    'cuspp',
    'numero_cuenta',
    'numero_cuenta_cci',
];

function e($valor): string
{
    if ($valor === null) {
        return '';
    }

    $valor = (string)$valor;

    if (function_exists('iconv')) {
        $tmp = @iconv('UTF-8', 'UTF-8//IGNORE', $valor);
        if ($tmp !== false) {
            $valor = $tmp;
        }
    }

    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function e_excel_multilinea($valor): string
{
    if ($valor === null) {
        return '';
    }

    $valor = (string)$valor;

    if (function_exists('iconv')) {
        $tmp = @iconv('UTF-8', 'UTF-8//IGNORE', $valor);
        if ($tmp !== false) {
            $valor = $tmp;
        }
    }

    $valor = htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
    $valor = nl2br($valor, false);

    return $valor;
}

$filename = 'DIRECTORIO_RRHH_' . date('Ymd_His') . '.xls';

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Pragma: public');
header('Expires: 0');

echo "\xEF\xBB\xBF";
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:x="urn:schemas-microsoft-com:office:excel"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

    <!--[if gte mso 9]>
    <xml>
        <x:ExcelWorkbook>
            <x:ExcelWorksheets>
                <x:ExcelWorksheet>
                    <x:Name>Directorio RRHH</x:Name>
                    <x:WorksheetOptions>
                        <x:DisplayGridlines/>
                        <x:FreezePanes/>
                        <x:FrozenNoSplit/>
                        <x:SplitHorizontal>4</x:SplitHorizontal>
                        <x:TopRowBottomPane>4</x:TopRowBottomPane>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
    <![endif]-->

    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #0f172a;
        }

        br {
            mso-data-placement: same-cell;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            table-layout: fixed;
        }

        th, td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            vertical-align: top;
            word-wrap: break-word;
        }

        .titulo {
            background: #7f1d1d;
            color: #ffffff;
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            padding: 12px;
        }

        .subtitulo {
            background: #f8fafc;
            color: #475569;
            font-weight: bold;
            text-align: center;
            padding: 8px;
        }

        .head {
            background: #991b1b;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
        }

        .par {
            background: #f8fafc;
        }

        .impar {
            background: #ffffff;
        }

        .num {
            text-align: center;
        }

        .money {
            text-align: right;
            mso-number-format:"\0022S/\0022\ #,##0.00";
        }

        .fecha {
            mso-number-format:"dd\/mm\/yyyy";
        }

        .texto {
            mso-number-format:"\@";
            white-space: nowrap;
        }

        .multilinea {
            white-space: normal;
            line-height: 1.35;
        }

        .estado {
            background: #dcfce7;
            text-align: center;
            font-weight: bold;
        }

        .vacio {
            text-align: center;
            color: #64748b;
        }

        col.col-n       { width: 50px; }
        col.col-dni     { width: 90px; }
        col.col-nombre  { width: 260px; }
        col.col-ruc     { width: 110px; }
        col.col-lic     { width: 110px; }
        col.col-fnac    { width: 95px; }
        col.col-lnac    { width: 180px; }
        col.col-edad    { width: 55px; }
        col.col-sexo    { width: 60px; }
        col.col-ecivil  { width: 110px; }
        col.col-gsang   { width: 90px; }
        col.col-talla   { width: 70px; }
        col.col-grado   { width: 120px; }
        col.col-cel     { width: 110px; }
        col.col-cper    { width: 220px; }
        col.col-dir     { width: 380px; }
        col.col-dist    { width: 120px; }
        col.col-nsacip  { width: 110px; }
        col.col-cinst   { width: 220px; }
        col.col-sit     { width: 90px; }
        col.col-sueldo  { width: 100px; }
        col.col-mod     { width: 120px; }
        col.col-pcas    { width: 140px; }
        col.col-tpuesto { width: 120px; }
        col.col-area    { width: 140px; }
        col.col-proc    { width: 140px; }
        col.col-fing    { width: 95px; }
        col.col-fcese   { width: 95px; }
        col.col-hijos   { width: 90px; }
        col.col-cony    { width: 180px; }
        col.col-ocon    { width: 120px; }
        col.col-prof    { width: 230px; }
        col.col-instf   { width: 230px; }
        col.col-grf     { width: 150px; }
        col.col-esp     { width: 260px; }
        col.col-spen    { width: 130px; }
        col.col-afp     { width: 100px; }
        col.col-cuspp   { width: 120px; }
        col.col-tcom    { width: 110px; }
        col.col-fins    { width: 100px; }
        col.col-sinafp  { width: 70px; }
        col.col-banco   { width: 140px; }
        col.col-cuenta  { width: 130px; }
        col.col-cci     { width: 220px; }
    </style>
</head>
<body>
    <table border="1">
        <colgroup>
            <col class="col-n">
            <col class="col-dni">
            <col class="col-nombre">
            <col class="col-ruc">
            <col class="col-lic">
            <col class="col-fnac">
            <col class="col-lnac">
            <col class="col-edad">
            <col class="col-sexo">
            <col class="col-ecivil">
            <col class="col-gsang">
            <col class="col-talla">
            <col class="col-grado">
            <col class="col-cel">
            <col class="col-cper">
            <col class="col-dir">
            <col class="col-dist">
            <col class="col-nsacip">
            <col class="col-cinst">
            <col class="col-sit">
            <col class="col-sueldo">
            <col class="col-mod">
            <col class="col-pcas">
            <col class="col-tpuesto">
            <col class="col-area">
            <col class="col-proc">
            <col class="col-fing">
            <col class="col-fcese">
            <col class="col-hijos">
            <col class="col-cony">
            <col class="col-ocon">
            <col class="col-prof">
            <col class="col-instf">
            <col class="col-grf">
            <col class="col-esp">
            <col class="col-spen">
            <col class="col-afp">
            <col class="col-cuspp">
            <col class="col-tcom">
            <col class="col-fins">
            <col class="col-sinafp">
            <col class="col-banco">
            <col class="col-cuenta">
            <col class="col-cci">
        </colgroup>

        <tr>
            <td class="titulo" colspan="<?php echo count($columnas) + 1; ?>">
                DIRECTORIO DE PERSONAL - RRHH
            </td>
        </tr>
        <tr>
            <td class="subtitulo" colspan="<?php echo count($columnas) + 1; ?>">
                Exportado el <?php echo date('d/m/Y H:i:s'); ?> | Total: <?php echo count($empleados); ?> colaboradores
            </td>
        </tr>
        <tr>
            <td colspan="<?php echo count($columnas) + 1; ?>" style="border:0; height:8px;"></td>
        </tr>
        <tr>
            <th class="head">N°</th>
            <?php foreach ($columnas as $titulo): ?>
                <th class="head"><?php echo e($titulo); ?></th>
            <?php endforeach; ?>
        </tr>

        <?php if (empty($empleados)): ?>
            <tr>
                <td class="vacio" colspan="<?php echo count($columnas) + 1; ?>">
                    No se encontraron registros.
                </td>
            </tr>
        <?php else: ?>
            <?php $n = 1; ?>
            <?php foreach ($empleados as $emp): ?>
                <?php $claseFila = ($n % 2 === 0) ? 'par' : 'impar'; ?>
                <tr>
                    <td class="num <?php echo $claseFila; ?>">
                        <?php echo $n; ?>
                    </td>

                    <?php foreach ($columnas as $campo => $titulo): ?>
                        <?php
                        $valor = $emp[$campo] ?? '';

                        if ($campo === 'sin_afp_afiliarme') {
                            $valor = ((int)$valor === 1) ? 'SÍ' : 'NO';
                        }

                        $class = $claseFila;

                        if ($campo === 'sueldo') {
                            $class .= ' money';
                        }

                        if (in_array($campo, ['fecha_nacimiento', 'fecha_ingreso', 'fecha_cese', 'fecha_inscripcion', 'onomastico_conyuge'], true)) {
                            $class .= ' fecha';
                        }

                        if (in_array($campo, $camposTextoForzado, true)) {
                            $class .= ' texto';
                        }

                        if (in_array($campo, ['profesion', 'institucion', 'grado', 'curso_especializacion'], true)) {
                            $class .= ' multilinea';
                        }

                        if ($campo === 'situacion') {
                            $valor = strtoupper(trim((string)$valor));
                            if ($valor === '') {
                                $valor = 'ACTIVO';
                            }
                            $class = 'estado';
                        }
                        ?>

                        <?php if (in_array($campo, $camposTextoForzado, true)): ?>
                            <td class="<?php echo trim($class); ?>" style='mso-number-format:"\@";' x:str>
                                <?php echo e($valor); ?>
                            </td>
                        <?php elseif (in_array($campo, ['profesion', 'institucion', 'grado', 'curso_especializacion'], true)): ?>
                            <td class="<?php echo trim($class); ?>">
                                <?php echo e_excel_multilinea($valor); ?>
                            </td>
                        <?php else: ?>
                            <td class="<?php echo trim($class); ?>">
                                <?php echo e($valor); ?>
                            </td>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tr>
                <?php $n++; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>
</body>
</html>