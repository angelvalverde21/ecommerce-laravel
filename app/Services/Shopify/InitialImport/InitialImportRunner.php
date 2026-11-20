<?php

namespace App\Services\Shopify\InitialImport;

use App\Models\ImportShopifyRun;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InitialImportRunner
{
    public function __construct(private InitialImportSource $source, private InitialImportWriter $writer) {}

    public function tick(ImportShopifyRun $run): bool
    {
        if ($run->shop_domain !== self::domain() || $run->api_version !== config('shopify.initial_import_version')) throw new RuntimeException('La conexión Shopify cambió. Restaura la conexión original antes de reanudar.');
        if (!$run->access_verified) {
            $this->source->verifyAll();
            $run->update(['access_verified' => true]);
        }
        $updates = app(InitialImportOrderUpdates::class);
        $update = $updates->active($run);
        if ($update && in_array($update->status, ['queued', 'scanning'], true)) {
            $updates->scan($run, $update, $this->source, $this->writer);
            return false;
        }
        $task = DB::table('import_shopify_tasks')->where('run_id', $run->id)->whereNotIn('status', ['completed', 'blocked'])->orderBy('id')->first();
        if (!$task) {
            $blocked = DB::table('import_shopify_tasks')->where('run_id', $run->id)->where('status', 'blocked')->count();
            if ($blocked) throw new RuntimeException('Quedan '.$blocked.' adjuntos sin respaldar. Reanuda para reintentar; la carga no se considera completa.');
            DB::transaction(function () use ($run, $update) {
                $run->update(['status' => 'completed', 'completed_at' => now()]);
                $update?->update(['status' => 'completed', 'completed_at' => now(), 'error_message' => null]);
            });
            return true;
        }
        $run->update(['status' => 'running', 'current_task' => $task->kind, 'heartbeat_at' => now()]);
        if (isset(InitialImportQueries::SNAPSHOTS[$task->kind])) {
            $this->snapshot($run, $task);
        } elseif ($task->kind === 'event_attachments') {
            $this->attachment($run, $task);
        } elseif (isset(InitialImportQueries::ROOTS[$task->kind])) {
            $this->bulk($run, $task);
        } else {
            $batch = $task->cursor === null ? DB::table('import_shopify_tasks')->where('run_id', $run->id)->where('kind', $task->kind)->where('status', '!=', 'completed')->whereNull('cursor')->orderBy('id')->limit(250)->get() : collect([$task]);
            $pages = $batch->count() > 1 ? $this->source->batch($task->kind, $batch->pluck('owner_id')->all())
                : [$task->owner_id => $this->source->page($task->kind, $task->owner_id, $task->cursor)];
            DB::transaction(function () use ($run, $batch, $pages, $update) {
                foreach ($batch as $item) $this->savePage($run, $item, $pages[$item->owner_id], $update !== null);
            });
        }
        $run->update(['throttle' => $this->source->throttle, 'heartbeat_at' => now()]);
        return false;
    }

    private function snapshot(ImportShopifyRun $run, object $task): void
    {
        $batch = DB::table('import_shopify_tasks')->where('run_id', $run->id)->where('kind', $task->kind)->where('status', '!=', 'completed')->orderBy('id')->limit(50)->get();
        $records = $this->source->snapshots($task->kind, $batch->pluck('owner_id')->all());
        DB::transaction(function () use ($run, $task, $batch, $records) {
            $entity = InitialImportQueries::SNAPSHOTS[$task->kind];
            foreach ($batch as $item) {
                $record = $records[$item->owner_id];
                $this->writer->save($run, $entity, $record, $entity === 'variants' ? ($record['product']['id'] ?? null) : null, includeDependencies: true);
                DB::table('import_shopify_tasks')->where('id', $item->id)->update(['status' => 'completed', 'processed_rows' => 1, 'updated_at' => now()]);
            }
        });
    }

    private function attachment(ImportShopifyRun $run, object $task): void
    {
        try {
            app(InitialImportAttachments::class)->archive($run, $task->owner_id);
            DB::table('import_shopify_tasks')->where('id', $task->id)->update(['status' => 'completed', 'processed_rows' => 1, 'updated_at' => now()]);
        } catch (RuntimeException $error) {
            DB::transaction(function () use ($run, $task, $error) {
                DB::table('import_shopify_event_attachments')->where('run_id', $run->id)->where('task_key', $task->owner_id)->update([
                    'status' => 'failed', 'error_message' => $error->getMessage(), 'updated_at' => now(),
                ]);
                DB::table('import_shopify_tasks')->where('id', $task->id)->update(['status' => 'blocked', 'updated_at' => now()]);
            });
        }
    }

    private function savePage(ImportShopifyRun $run, object $task, array $page, bool $includeDependencies = false): void
    {
        $next = $page['pageInfo']['endCursor'] ?? null;
        $more = $page['pageInfo']['hasNextPage'];
        if ($more && (!$next || $next === $task->cursor || !$page['edges'])) throw new RuntimeException('La paginación Shopify no avanzó; se conserva el último punto de lectura.');
        $entity = InitialImportQueries::CONNECTIONS[$task->kind][2];
        foreach ($page['edges'] as $edge) $this->writer->save($run, $entity, $edge['node'], $task->owner_id, $includeDependencies);
        $audit = [];
        if ($task->kind === 'events') {
            $previous = $task->cursor ? json_decode($task->metadata ?? '{}', true, 512, JSON_THROW_ON_ERROR) : [];
            $comments = ($previous['comment_count'] ?? 0) + count(array_filter($page['edges'], fn ($edge) => ($edge['node']['__typename'] ?? null) === 'CommentEvent'));
            $expected = $page['hasTimelineComment'] ?? $previous['has_timeline_comment'] ?? null;
            $audit = ['metadata' => json_encode(['has_timeline_comment' => $expected, 'comment_count' => $comments,
                'availability' => $more ? 'checking' : ($expected === true && $comments === 0 ? 'unavailable_comments' : ($expected === null ? 'unknown' : 'available')),
                'checked_at' => now()->toIso8601String()], JSON_THROW_ON_ERROR)];
        }
        DB::table('import_shopify_tasks')->where('id', $task->id)->update(['cursor' => $next,
            'processed_rows' => $task->processed_rows + count($page['edges']), 'status' => $more ? 'running' : 'completed', 'updated_at' => now(), ...$audit]);
    }

    private function bulk(ImportShopifyRun $run, object $task): void
    {
        if (!$task->operation_id) {
            $id = $this->source->startDataset($task->kind);
            DB::table('import_shopify_tasks')->where('id', $task->id)->update(['operation_id' => $id, 'status' => 'exporting', 'updated_at' => now()]);
            return;
        }
        $path = $this->path($run->id, $task->id);
        if ($task->expected_rows === null || !is_file($path)) {
            $operation = $this->source->operation($task->operation_id);
            if (in_array($operation['status'], ['FAILED', 'CANCELED', 'EXPIRED'], true)) {
                DB::table('import_shopify_tasks')->where('id', $task->id)->update(['operation_id' => null]);
                throw new RuntimeException('La exportación '.$task->kind.' terminó en '.$operation['status'].'. Reanuda para solicitarla de nuevo.');
            }
            if ($operation['status'] !== 'COMPLETED') return;
            $task->expected_rows = (int) $operation['objectCount'];
            $task->expected_roots = (int) $operation['rootObjectCount'];
            if ($task->expected_rows === 0) {
                if ($task->expected_roots !== 0) throw new RuntimeException('Conteos Shopify inconsistentes.');
                DB::table('import_shopify_tasks')->where('id', $task->id)->update(['status' => 'completed', 'expected_rows' => 0, 'expected_roots' => 0]);
                return;
            }
            if (!is_file($path)) {
                if (empty($operation['url'])) throw new RuntimeException('La exportación completa no tiene archivo descargable.');
                $this->source->download($operation['url'], $path.'.download');
                if (!rename($path.'.download', $path)) throw new RuntimeException('No se pudo guardar el archivo de importación.');
            }
            DB::table('import_shopify_tasks')->where('id', $task->id)->update(['expected_rows' => $task->expected_rows, 'expected_roots' => $task->expected_roots, 'status' => 'processing']);
        }
        $stream = fopen($path, 'rb');
        if (!$stream) throw new RuntimeException('No se encuentra el archivo privado de la carga.');
        try {
            if (fseek($stream, (int) $task->file_offset) !== 0) throw new RuntimeException('No se pudo reanudar el archivo.');
            $batch = [];
            while (count($batch) < 250 && ($line = fgets($stream)) !== false) {
                if (trim($line) === '') continue;
                $row = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                $type = ['orders' => 'Order', 'products' => 'Product', 'variants' => 'ProductVariant', 'customers' => 'Customer'][$task->kind];
                if (empty($row['id']) || isset($row['__parentId']) || ($row['__typename'] ?? null) !== $type) throw new InitialImportFileException('Formato inesperado en la exportación raíz.');
                $batch[] = $row;
            }
            $offset = ftell($stream);
            $finished = feof($stream);
            if (!$finished && !$batch) throw new InitialImportFileException('La lectura del archivo no avanzó.');
            $processed = $task->processed_rows + count($batch);
            if ($processed > $task->expected_rows || ($finished && ($processed !== $task->expected_rows || $processed !== $task->expected_roots))) throw new InitialImportFileException('El archivo Shopify está incompleto o no coincide con sus conteos.');
            DB::transaction(function () use ($run, $task, $batch, $offset, $processed, $finished) {
                foreach ($batch as $record) $this->writer->save($run, $task->kind, $record, $task->kind === 'variants' ? ($record['product']['id'] ?? null) : null);
                DB::table('import_shopify_tasks')->where('id', $task->id)->update(['file_offset' => $offset, 'processed_rows' => $processed,
                    'processed_roots' => $processed, 'status' => $finished ? 'completed' : 'processing', 'updated_at' => now()]);
            });
        } catch (\JsonException|InitialImportFileException $error) {
            fclose($stream); $stream = null;
            if (is_file($path) && !unlink($path)) throw new RuntimeException('No se pudo reemplazar el archivo incompleto.');
            DB::table('import_shopify_tasks')->where('id', $task->id)->update(['file_offset' => 0, 'processed_rows' => 0, 'processed_roots' => 0, 'expected_rows' => null, 'expected_roots' => null, 'status' => 'exporting']);
            throw new RuntimeException('El archivo recibido está incompleto o dañado. Al reanudar se descargará nuevamente sin duplicar los registros guardados.', previous: $error);
        } finally { if (is_resource($stream)) fclose($stream); }
    }

    public function path(string $run, int $task): string
    {
        if (!\Illuminate\Support\Str::isUuid($run)) throw new RuntimeException('Identificador de carga inválido.');
        $directory = storage_path('app/private/import-shopify/'.$run);
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) throw new RuntimeException('No se pudo crear el directorio privado.');
        return $directory.'/'.$task.'.jsonl';
    }

    public static function domain(): string
    {
        $domain = strtolower(trim((string) config('shopify.store')));
        return str_ends_with($domain, '.myshopify.com') ? $domain : $domain.'.myshopify.com';
    }
}
