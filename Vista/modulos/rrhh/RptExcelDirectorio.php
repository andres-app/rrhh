<?php
// Vista/modulos/rrhh/RptExcelDirectorio.php

while (ob_get_level()) {
    ob_end_clean();
}

require_once ROOT_PATH . 'Controlador/CtrDirectorio.php';
require_once ROOT_PATH . 'Modelo/MdDirectorio.php';

$controlador = new CtrDirectorio();
$empleados = $controlador->ctrMostrarDirectorio();

if (!is_array($empleados)) {
    $empleados = [];
}

$q = mb_strtolower(trim($_GET['q'] ?? ''), 'UTF-8');

if ($q !== '') {
    $empleados = array_filter($empleados, function ($row) use ($q) {
        $texto = mb_strtolower(implode(' ', array_map('strval', $row)), 'UTF-8');
        return mb_strpos($texto, $q, 0, 'UTF-8') !== false;
    });
}

$columnas = [
    'dni' => 'DNI',
    'nombres_apellidos' => 'Nombres y apellidos',
    'ruc' => 'RUC',
    'licencia_conducir' => 'Licencia conducir',
    'fecha_nacimiento' => 'Fecha nacimiento',
    'lugar_nacimiento' => 'Lugar nacimiento',
    'edad' => 'Edad',
    'sexo' => 'Sexo',
    'estado_civil' => 'Estado civil',
    'grupo_sanguineo' => 'Grupo sanguíneo',
    'talla' => 'Talla',
    'grado_militar' => 'Grado militar',

    'celular' => 'Celular',
    'correo_personal' => 'Correo personal',
    'direccion_residencia' => 'Dirección residencia',
    'distrito' => 'Distrito',

    'correo_institucional' => 'Correo institucional',
    'situacion' => 'Situación',
    'sueldo' => 'Sueldo',
    'modalidad_contrato' => 'Modalidad contrato',
    'puesto_cas' => 'Puesto CAS',
    'tipo_puesto' => 'Tipo puesto',
    'area' => 'Área',
    'procedencia' => 'Procedencia',
    'fecha_ingreso' => 'Fecha ingreso',
    'fecha_cese' => 'Fecha cese',

    'sistema_pension' => 'Sistema pensión',
    'afp' => 'AFP',
    'cuspp' => 'CUSPP',
    'tipo_comision' => 'Tipo comisión',
    'fecha_inscripcion' => 'Fecha inscripción',
    'sin_afp_afiliarme' => 'Sin AFP / por afiliar',

    'banco_haberes' => 'Banco haberes',
    'numero_cuenta' => 'Número de cuenta',
    'numero_cuenta_cci' => 'CCI',
];

$totalColumnas = count($columnas) + 1;
$filename = 'DIRECTORIO_RRHH_' . date('Ymd_His') . '.xls';

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

function e_excel($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body>

<table border="1">
    <tr>
        <td colspan="<?= $totalColumnas ?>" style="background:#7f1d1d;color:#ffffff;font-size:20px;font-weight:bold;text-align:center;">
            DIRECTORIO DE PERSONAL - RRHH
        </td>
    </tr>

    <tr>
        <td colspan="<?= $totalColumnas ?>" style="background:#f8fafc;color:#475569;text-align:center;font-weight:bold;">
            Exportado el <?= date('d/m/Y H:i:s') ?> | Total: <?= count($empleados) ?> colaboradores
        </td>
    </tr>

    <tr style="background:#991b1b;color:#ffffff;font-weight:bold;text-align:center;">
        <th>N°</th>
        <?php foreach ($columnas as $titulo): ?>
            <th><?= e_excel($titulo) ?></th>
        <?php endforeach; ?>
    </tr>

    <?php if (empty($empleados)): ?>
        <tr>
            <td colspan="<?= $totalColumnas ?>" style="text-align:center;">No se encontraron registros.</td>
        </tr>
    <?php else: ?>
        <?php $i = 1; ?>
        <?php foreach ($empleados as $row): ?>
            <tr style="<?= $i % 2 === 0 ? 'background:#f8fafc;' : '' ?>">
                <td style="text-align:center;"><?= $i ?></td>

                <?php foreach ($columnas as $campo => $titulo): ?>
                    <?php
                    $valor = $row[$campo] ?? '';

                    if ($campo === 'sin_afp_afiliarme') {
                        $valor = ((int)$valor === 1) ? 'SÍ' : 'NO';
                    }

                    $style = "mso-number-format:'\@';";

                    if ($campo === 'situacion') {
                        $situacion = strtoupper(trim((string)$valor));
                        $style .= in_array($situacion, ['INACTIVO', 'CESADO', 'BAJA'], true)
                            ? 'background:#fee2e2;color:#991b1b;font-weight:bold;text-align:center;'
                            : 'background:#dcfce7;color:#166534;font-weight:bold;text-align:center;';
                    }
                    ?>
                    <td style="<?= $style ?>"><?= e_excel($valor) ?></td>
                <?php endforeach; ?>
            </tr>
            <?php $i++; ?>
        <?php endforeach; ?>
    <?php endif; ?>

</table>

</body>
</html>
<?php exit; ?>