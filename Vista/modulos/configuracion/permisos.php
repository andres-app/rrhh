<?php
// Solo permitir si es admin
if ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'superadmin') {
    die("Acceso denegado");
}

$db = Conexion::conectar();
$modulos = $db->query("SELECT * FROM modulos")->fetchAll();
$roles = ['admin', 'colaborador', 'rrhh']; // Puedes traer esto de una tabla roles también
?>

<h2>Gestión de Permisos</h2>
<form method="POST" action="procesar_permisos.php">
<table border="1" cellpadding="10" style="border-collapse: collapse; width: 100%;">
    <thead>
        <tr style="background: #f4f4f4;">
            <th>Módulo</th>
            <th>Rol</th>
            <th>Ver (Check)</th>
            <th>Editar (Check)</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($modulos as $m): ?>
            <?php foreach ($roles as $r): ?>
                <tr>
                    <td><?= $m['nombre'] ?></td>
                    <td><?= strtoupper($r) ?></td>
                    <td><input type="checkbox" name="permisos[<?= $m['id'] ?>][<?= $r ?>][view]"></td>
                    <td><input type="checkbox" name="permisos[<?= $m['id'] ?>][<?= $r ?>][edit]"></td>
                </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </tbody>
</table>
<button type="submit" style="margin-top: 20px; padding: 10px 20px; background: #4f46e5; color: white; border: none; border-radius: 5px;">
    Guardar Cambios
</button>
</form>