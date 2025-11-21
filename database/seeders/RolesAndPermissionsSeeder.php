<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Resetear caché
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. PERMISOS en texto natural
        $permissions = [
            // Usuarios y roles
            'ver usuarios', 'crear usuarios', 'editar usuarios', 'eliminar usuarios', 'asignar roles',

            // Productos y producción
            'ver productos', 'crear productos', 'editar productos', 'eliminar productos',
            'ver categorias', 'gestionar variantes', 'aprobar calidad', 'controlar producción',

            // Inventario
            'ver inventario', 'actualizar stock', 'registrar entradas', 'registrar salidas',

            // Ventas
            'ver ventas', 'crear ventas', 'editar ventas', 'anular ventas', 'imprimir boletas',

            // Empaquetado
            'ver pedidos pendientes', 'marcar pedidos empaquetados', 'gestionar guías de envío',

            // Contenido / marketing
            'crear publicaciones', 'editar publicaciones', 'eliminar publicaciones', 'ver campañas',

            // Compras
            'ver compras', 'registrar compras', 'editar compras', 'eliminar compras',
            'ver proveedores', 'crear proveedores', 'editar proveedores', 'eliminar proveedores',

            // Reportes
            'ver reportes', 'exportar reportes',

            // Configuración
            'ver configuración', 'editar configuración', 'gestionar backups',
        ];

        // Crear permisos en snake_case
        foreach ($permissions as $name) {
            Permission::firstOrCreate([
                'name' => Str::snake($name),
                'guard_name' => 'api',
            ]);
        }

        // Obtener permisos del guard api
        $apiPermissions = Permission::where('guard_name', 'api')->get();

        // 2. ROLES (ya usando snake_case)
        $roles = [
            'master' => $apiPermissions,
            'ceo' => $apiPermissions,

            'control_de_calidad' => [
                'ver productos', 'aprobar calidad', 'controlar producción',
            ],

            'produccion' => [
                'ver productos', 'controlar producción',
            ],

            'despacho' => [
                'ver pedidos pendientes', 'marcar pedidos empaquetados', 'gestionar guías de envío',
            ],

            'ventas' => [
                'ver productos', 'crear ventas', 'ver ventas', 'imprimir boletas',
            ],

            'inventario' => [
                'ver inventario', 'actualizar stock', 'registrar entradas', 'registrar salidas',
            ],

            'creador_de_contenido' => [
                'crear publicaciones', 'editar publicaciones', 'eliminar publicaciones', 'ver campañas',
            ],

            'compras' => [
                'ver compras', 'registrar compras', 'editar compras', 'eliminar compras',
                'ver proveedores', 'crear proveedores', 'editar proveedores', 'eliminar proveedores',
            ],
        ];

        foreach ($roles as $roleName => $perms) {

            // Crear rol en snake_case
            $role = Role::firstOrCreate([
                'name' => Str::snake($roleName),
                'guard_name' => 'api',
            ]);

            // Convertir permisos del rol a snake_case
            $snakePerms = collect($perms)->map(function ($p) {
                return is_string($p) ? Str::snake($p) : $p;
            });

            $role->syncPermissions($snakePerms);
        }

        $this->command->info('✅ Roles y permisos creados en snake_case (al estilo Shopify).');
    }
}
