<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Nodo.php';
require_once __DIR__ . '/../controllers/AlertaController.php';

class NodoController {

    public static function listar(): array { return Nodo::todos(); }

    public static function obtener(int $id): ?array { return Nodo::porId($id); }

    public static function registrar(array $datos): array {
        if (empty($datos['nombre'])||empty($datos['identificador']))
            return ['ok'=>false,'error'=>'Nombre e identificador son obligatorios'];
        $id = Nodo::insertar($datos);
        return $id ? ['ok'=>true,'id'=>$id,'mensaje'=>'Nodo registrado'] : ['ok'=>false,'error'=>'Error al insertar nodo'];
    }

    public static function actualizar(int $id, array $datos): array {
        if (!Nodo::porId($id)) return ['ok'=>false,'error'=>'Nodo no encontrado'];
        return Nodo::actualizar($id,$datos) ? ['ok'=>true,'mensaje'=>'Nodo actualizado'] : ['ok'=>false,'error'=>'Error al actualizar'];
    }

    public static function eliminar(int $id): array {
        if (!Nodo::porId($id)) return ['ok'=>false,'error'=>'Nodo no encontrado'];
        return Nodo::eliminar($id) ? ['ok'=>true,'mensaje'=>'Nodo eliminado'] : ['ok'=>false,'error'=>'Error al eliminar'];
    }

    public static function resumen(): array { return Nodo::resumen(); }
}