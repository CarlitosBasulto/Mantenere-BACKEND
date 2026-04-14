<?php
use Illuminate\Support\Facades\DB;

DB::statement("ALTER TABLE mantenimiento_solicitudes MODIFY COLUMN estado ENUM('Pendiente', 'Visita Asignada', 'Diagnosticado', 'Cotizado al Cliente', 'Aprobado por Cliente', 'Cotización Aceptada', 'Cotización Rechazada', 'Trabajo Asignado', 'Finalizado') DEFAULT 'Pendiente'");

echo "Enum updated successfully.";
