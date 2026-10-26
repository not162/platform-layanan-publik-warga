<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use App\Support\ManagedRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CRUD generik: GET / POST / PATCH(PUT) / DELETE pada /api/v1/manage/{resource}
 * yang langsung membaca & menulis ke MySQL. Definisi tiap resource ada di ManagedRegistry.
 */
class ManageController extends Controller
{
    public function __construct(protected AuditService $audit) {}

    public function index(Request $request, string $resource): JsonResponse
    {
        $def = $this->definition($resource);
        $user = $this->actor($request);
        $this->authorizeAbility($def, 'read', $user);

        $query = $def['model']::query();

        if (! empty($def['with'])) {
            $query->with($def['with']);
        }

        if (isset($def['query'])) {
            $def['query']($query, $user, $request);
        }

        foreach ($def['filters'] ?? [] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        if ($term = trim((string) $request->input('q', ''))) {
            $query->where(function ($q) use ($def, $term) {
                foreach ($def['search'] ?? [] as $i => $column) {
                    $q->{$i === 0 ? 'where' : 'orWhere'}($column, 'like', '%'.$term.'%');
                }
            });
        }

        if ($since = $request->input('updated_since')) {
            $query->where('updated_at', '>', $since);
        }

        $perPage = max(1, min((int) $request->input('per_page', 15), 100));
        $page = $query->paginate($perPage);

        return response()->json([
            'data' => $page->getCollection()->map(fn (Model $m) => $def['transform']($m, $user))->values(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'last_page' => $page->lastPage(),
                'server_time' => now()->toIso8601String(),
            ],
            'message' => 'OK',
        ]);
    }

    public function show(Request $request, string $resource, int $id): JsonResponse
    {
        $def = $this->definition($resource);
        $user = $this->actor($request);
        $this->authorizeAbility($def, 'read', $user);

        $model = $this->find($def, $user, $request, $id);

        return response()->json(['data' => $def['transform']($model, $user), 'message' => 'OK']);
    }

    public function store(Request $request, string $resource): JsonResponse
    {
        $def = $this->definition($resource);
        $user = $this->actor($request);
        $this->authorizeAbility($def, 'create', $user);

        $data = $request->validate($def['rules']($request, $user, null));
        if (isset($def['prepare'])) {
            $data = $def['prepare']($data, $user, null);
        }

        $model = DB::transaction(function () use ($def, $data, $user, $resource) {
            $model = isset($def['store']) ? $def['store']($data, $user) : $def['model']::create($data);
            $this->audit->log("{$resource}.created", class_basename($model), (int) $model->getKey(), null, $this->auditable($model));

            return $model;
        });

        if (isset($def['after_store'])) {
            $def['after_store']($model, $user);
        }

        return response()->json(['data' => $def['transform']($this->reload($def, $model), $user), 'message' => 'Data berhasil disimpan.'], 201);
    }

    public function update(Request $request, string $resource, int $id): JsonResponse
    {
        $def = $this->definition($resource);
        $user = $this->actor($request);
        $model = $this->find($def, $user, $request, $id);
        $this->authorizeAbility($def, 'update', $user, $model);
        $this->assertVersion($request, $model);

        $data = $request->validate($def['rules']($request, $user, $model));
        $old = $model->getAttributes();
        if (isset($def['prepare'])) {
            $data = $def['prepare']($data, $user, $model);
        }

        DB::transaction(function () use (&$model, $def, $data, $user, $resource, $old) {
            $model = isset($def['update']) ? $def['update']($model, $data, $user) : tap($model)->update($data);
            $this->audit->log("{$resource}.updated", class_basename($model), (int) $model->getKey(), $this->auditable($old), $this->auditable($model));
        });

        if (isset($def['after_update'])) {
            $def['after_update']($model, $user, $old);
        }

        return response()->json(['data' => $def['transform']($this->reload($def, $model), $user), 'message' => 'Data berhasil diperbarui.']);
    }

    public function destroy(Request $request, string $resource, int $id): JsonResponse
    {
        $def = $this->definition($resource);
        $user = $this->actor($request);
        $model = $this->find($def, $user, $request, $id);
        $this->authorizeAbility($def, 'delete', $user, $model);

        if (isset($def['before_delete'])) {
            $def['before_delete']($model, $user);
        }

        try {
            DB::transaction(function () use ($model, $def, $resource) {
                $snapshot = $this->auditable($model);
                isset($def['destroy']) ? $def['destroy']($model) : $model->delete();
                $this->audit->log("{$resource}.deleted", class_basename($model), (int) $model->getKey(), $snapshot, null);
            });
        } catch (QueryException $e) {
            abort(409, 'Data ini masih dipakai data lain sehingga tidak dapat dihapus.');
        }

        return response()->json(['message' => 'Data berhasil dihapus.']);
    }

    public function action(Request $request, string $resource, int $id, string $action): JsonResponse
    {
        $def = $this->definition($resource);
        $user = $this->actor($request);
        $model = $this->find($def, $user, $request, $id);
        $config = $def['actions'][$action] ?? abort(404, 'Aksi tidak dikenal.');

        $ability = $config['ability'];
        $allowed = match (true) {
            is_string($ability) && in_array($ability, ['read', 'create', 'update', 'delete'], true) => $this->can($def, $ability, $user, $model),
            is_string($ability) => $user->hasPermission($ability),
            is_callable($ability) => (bool) $ability($user, $model),
            default => false,
        };

        abort_unless($allowed, 403, 'Anda tidak berwenang melakukan aksi ini.');

        $result = DB::transaction(fn () => $config['handler']($model, $request, $user));
        $this->audit->log("{$resource}.{$action}", class_basename($model), (int) $model->getKey(), null, ['by' => $user->username]);

        return response()->json(['data' => $def['transform']($this->reload($def, $result instanceof Model ? $result : $model), $user), 'message' => 'Aksi berhasil.']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function definition(string $resource): array
    {
        return ManagedRegistry::get($resource) ?? abort(404, 'Resource tidak ditemukan.');
    }

    protected function actor(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->is_active, 403, 'Akun dinonaktifkan.');

        return $user;
    }

    /**
     * @param  array<string, mixed>  $def
     */
    protected function can(array $def, string $ability, User $user, ?Model $model = null): bool
    {
        $rule = $def['abilities'][$ability] ?? false;

        return match (true) {
            $rule === true => true,
            is_string($rule) => $user->hasPermission($rule),
            is_callable($rule) => (bool) $rule($user, $model),
            default => false,
        };
    }

    /**
     * @param  array<string, mixed>  $def
     */
    protected function authorizeAbility(array $def, string $ability, User $user, ?Model $model = null): void
    {
        abort_unless($this->can($def, $ability, $user, $model), 403, 'Anda tidak berwenang untuk tindakan ini.');
    }

    /**
     * @param  array<string, mixed>  $def
     */
    protected function find(array $def, User $user, Request $request, int $id): Model
    {
        $query = $def['model']::query();
        if (! empty($def['with'])) {
            $query->with($def['with']);
        }
        // Terapkan scope kepemilikan yang sama dengan daftar agar warga tak bisa membuka data orang lain.
        if (isset($def['query'])) {
            $scoped = clone $request;
            $scoped->query->set('mine', $user->isWarga() ? '1' : $request->input('mine'));
            $def['query']($query, $user, $scoped);
        }

        return $query->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $def
     */
    protected function reload(array $def, Model $model): Model
    {
        $model = $model->fresh($def['with'] ?? []) ?? $model;

        return $model;
    }

    protected function assertVersion(Request $request, Model $model): void
    {
        if ($request->filled('version') && array_key_exists('version', $model->getAttributes())
            && (int) $request->input('version') !== (int) $model->version) {
            abort(409, 'Konflik data: data telah diubah pengguna lain. Muat ulang lalu coba lagi.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function auditable(Model|array $model): array
    {
        $attrs = $model instanceof Model ? $model->getAttributes() : $model;
        unset($attrs['password'], $attrs['remember_token'], $attrs['nik'], $attrs['no_kk']);

        return $attrs;
    }
}
