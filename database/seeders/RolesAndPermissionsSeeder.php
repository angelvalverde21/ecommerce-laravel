<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar caché de permisos
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $now = Carbon::now();

        /*
        |--------------------------------------------------------------------------
        | 1. PERMISOS (texto → snake_case)
        |--------------------------------------------------------------------------
        */
        $permissions = [
            // Usuarios
            'ver usuarios', 'crear usuarios', 'editar usuarios', 'eliminar usuarios', 'asignar roles',

            // Productos / Producción
            'ver productos', 'crear productos', 'editar productos', 'eliminar productos',
            'ver categorias', 'gestionar variantes', 'aprobar calidad', 'controlar producción',

            // Inventario
            'ver inventario', 'actualizar stock', 'registrar entradas', 'registrar salidas',

            // Ventas
            'ver ventas', 'crear ventas', 'editar ventas', 'anular ventas', 'imprimir boletas',

            // Empaque / Envío
            'ver pedidos pendientes', 'marcar pedidos empaquetados', 'gestionar guías de envío',

            // Contenido
            'crear publicaciones', 'editar publicaciones', 'eliminar publicaciones', 'ver campañas',

            // Compras
            'ver compras', 'registrar compras', 'editar compras', 'eliminar compras',
            'ver proveedores', 'crear proveedores', 'editar proveedores', 'eliminar proveedores',

            // Reportes
            'ver reportes', 'exportar reportes',

            // Configuración
            'ver configuración', 'editar configuración', 'gestionar backups',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name'       => Str::snake($permission),
                'guard_name' => 'api',
            ]);
        }

        $allPermissions = Permission::where('guard_name', 'api')->pluck('name');

        /*
        |--------------------------------------------------------------------------
        | 2. ROLES (EXACTOS A TU TABLA roles)
        |--------------------------------------------------------------------------
        */
        $roles = [
            [
                'id'    => 1,
                'name'  => 'master',
                'title' => 'Master',
                'permissions' => $allPermissions,
            ],
            [
                'id'    => 2,
                'name'  => 'ceo',
                'title' => 'CEO',
                'permissions' => $allPermissions,
            ],
            [
                'id'    => 3,
                'name'  => 'quality_control',
                'title' => 'Control de calidad',
                'permissions' => [
                    'ver productos',
                    'aprobar calidad',
                    'controlar producción',
                ],
            ],
            [
                'id'    => 4,
                'name'  => 'production',
                'title' => 'Producción',
                'permissions' => [
                    'ver productos',
                    'controlar producción',
                ],
            ],
            [
                'id'    => 6,
                'name'  => 'sales',
                'title' => 'Ventas',
                'permissions' => [
                    'ver productos',
                    'ver ventas',
                    'crear ventas',
                    'imprimir boletas',
                ],
            ],
            [
                'id'    => 7,
                'name'  => 'inventory',
                'title' => 'Inventario',
                'permissions' => [
                    'ver inventario',
                    'actualizar stock',
                    'registrar entradas',
                    'registrar salidas',
                ],
            ],
            [
                'id'    => 8,
                'name'  => 'content_maker',
                'title' => 'Creador de contenido',
                'permissions' => [
                    'crear publicaciones',
                    'editar publicaciones',
                    'eliminar publicaciones',
                    'ver campañas',
                ],
            ],
            [
                'id'    => 9,
                'name'  => 'purchasing',
                'title' => 'Compras',
                'permissions' => [
                    'ver compras',
                    'registrar compras',
                    'editar compras',
                    'eliminar compras',
                    'ver proveedores',
                    'crear proveedores',
                    'editar proveedores',
                    'eliminar proveedores',
                ],
            ],
            [
                'id'    => 10,
                'name'  => 'supplier',
                'title' => 'Proveedor',
                'permissions' => [],
            ],
            [
                'id'    => 11,
                'name'  => 'wholesaler',
                'title' => 'Mayorista',
                'permissions' => [],
            ],
            [
                'id'    => 12,
                'name'  => 'packing',
                'title' => 'Empaque',
                'permissions' => [
                    'ver pedidos pendientes',
                    'marcar pedidos empaquetados',
                ],
            ],
            [
                'id'    => 13,
                'name'  => 'shipping',
                'title' => 'Envío',
                'permissions' => [
                    'gestionar guías de envío',
                ],
            ],
        ];

        foreach ($roles as $data) {

            $role = Role::updateOrCreate(
                ['id' => $data['id']],
                [
                    'name'       => $data['name'],
                    'title'      => $data['title'],
                    'guard_name' => 'api',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $snakePermissions = collect($data['permissions'])
                ->map(fn ($p) => Str::snake($p));

            $role->syncPermissions($snakePermissions);
        }

        $this->command->info('✅ Roles y permisos creados respetando name + title');
    }
}
