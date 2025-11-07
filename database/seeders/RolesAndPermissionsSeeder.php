<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 🔐 1. Permisos globales del ERP
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

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'api']);
        }

        // 👥 2. Roles del ERP y sus permisos específicos
        $roles = [
            'Administrador del sistema' => Permission::all(),

            'CEO' => [
                'ver reportes', 'exportar reportes', 'ver ventas',
                'ver usuarios', 'ver inventario', 'ver configuración',
            ],

            'Control de calidad' => [
                'ver productos', 'aprobar calidad', 'controlar producción',
            ],

            'Producción' => [
                'ver productos', 'controlar producción',
            ],

            'Despacho' => [
                'ver pedidos pendientes', 'marcar pedidos empaquetados', 'gestionar guías de envío',
            ],

            'Ventas' => [
                'ver productos', 'crear ventas', 'ver ventas', 'imprimir boletas',
            ],

            'Inventario' => [
                'ver inventario', 'actualizar stock', 'registrar entradas', 'registrar salidas',
            ],

            'Creador de contenido' => [
                'crear publicaciones', 'editar publicaciones', 'eliminar publicaciones', 'ver campañas',
            ],

            'Compras' => [
                'ver compras', 'registrar compras', 'editar compras', 'eliminar compras',
                'ver proveedores', 'crear proveedores', 'editar proveedores', 'eliminar proveedores',
            ],
        ];

        foreach ($roles as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'api']);
            $role->givePermissionTo($perms);
        }

        $this->command->info('✅ Roles y permisos (incluyendo Compras) creados correctamente.');
    }
}
