<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\Device;
use App\Models\Family;
use App\Models\Location;
use App\Models\User;
use App\Services\ImageThumbnailService;
use App\Support\AdminGridCell;
use App\Support\AdminGridQuery;
use App\Support\AdminRbac;
use App\Support\LocationMapSyncPins;
use App\Support\PasswordPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ScreenCrudController extends Controller
{
    /** @var int Filas por página en los listados CRUD del admin. */
    private const ADMIN_GRID_PER_PAGE = 20;

    private function getScreen(string $screen): array
    {
        $config = config("admin_screens.$screen");
        abort_unless($config && isset($config['model']), 404);

        return $config;
    }

    private function assertScreenWritable(array $cfg): void
    {
        abort_if(! empty($cfg['readonly']), 404);
    }

    private function authorize(string $screen, string $action): void
    {
        $user = auth('admin')->user();
        abort_unless($user, 403);
        $p = AdminRbac::permissionsForScreen($screen);
        $perm = match ($action) {
            'view' => $p['view'],
            'edit' => $p['edit'],
            'delete' => $p['delete'],
            default => $p['view'],
        };
        abort_unless($user->can($perm), 403);
    }

    public function index(Request $request, string $screen)
    {
        $this->authorize($screen, 'view');
        $cfg = $this->getScreen($screen);
        $model = $cfg['model'];
        $query = $model::query();

        $with = AdminGridCell::eagerRelations($cfg);
        if ($with !== []) {
            $query->with($with);
        }

        if ($screen === 'transactions') {
            $query->withCount('items');
        }

        if ($screen === 'android-users') {
            $query->selectRaw('users.*, '.AdminGridQuery::androidUsersLastActivitySql().' as last_activity_at');
        }

        AdminGridQuery::apply($query, $request, $screen, $cfg);

        $items = $query->paginate(self::ADMIN_GRID_PER_PAGE)->withQueryString();

        $p = AdminRbac::permissionsForScreen($screen);
        $user = auth('admin')->user();

        $transactionExportLocations = [];
        if ($screen === 'transactions') {
            $transactionExportLocations = Location::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(static function (Location $loc): array {
                    return [
                        'id' => (string) $loc->getKey(),
                        'name' => $loc->name,
                    ];
                })
                ->values()
                ->all();
        }

        $locationsMapPins = [];
        if ($screen === 'locations') {
            $mapQuery = Location::query();
            AdminGridQuery::apply($mapQuery, $request, $screen, $cfg);
            $mapQuery->withCount([
                'devices as active_devices_count' => fn ($q) => $q->where('is_enabled', true),
            ]);
            $mapLocations = $mapQuery->get(['id', 'name', 'latitude', 'longitude', 'is_active']);
            $locationsMapPins = LocationMapSyncPins::build(
                $mapLocations,
                $user->can($p['edit']),
            );
        }

        return view('admin.crud.index', [
            'items' => $items,
            'screen' => $screen,
            'cfg' => $cfg,
            'gridFilterOptions' => AdminGridQuery::filterOptions($cfg),
            'canEdit' => $user->can($p['edit']) && empty($cfg['readonly']),
            'canDelete' => $user->can($p['delete']) && empty($cfg['readonly']),
            'locationsMapPins' => $locationsMapPins,
            'showTransactionExcelExport' => $screen === 'transactions' && $user->can($p['view']),
            'transactionExportLocations' => $transactionExportLocations,
        ]);
    }

    public function create(string $screen)
    {
        $this->authorize($screen, 'edit');
        $cfg = $this->getScreen($screen);
        $this->assertScreenWritable($cfg);
        abort_if(! empty($cfg['disable_create']), 404);

        return view('admin.crud.form', [
            'item' => null,
            'screen' => $screen,
            'cfg' => $cfg,
            'foreignSelectOptions' => $this->foreignSelectOptionsForForm($cfg),
            'roles' => Role::where('guard_name', 'admin')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, string $screen)
    {
        $this->authorize($screen, 'edit');
        $cfg = $this->getScreen($screen);
        $this->assertScreenWritable($cfg);
        abort_if(! empty($cfg['disable_create']), 404);
        $data = $this->validatedData($request, $cfg, false, $screen);

        if ($screen === 'families') {
            $this->applyImageUpload($request, $data, null, 'families');
        } elseif ($screen === 'products') {
            $this->applyImageUpload($request, $data, null, 'products');
        }

        $model = $cfg['model'];
        $created = $model::create($data);
        if ($model === AdminUser::class && $request->filled('role_names')) {
            $created->syncRoles($request->input('role_names', []));
        }
        if ($screen === 'android-users' && $created instanceof User) {
            $this->syncAndroidUserLocationPivot($created, $data['location_id'] ?? null);
        }

        return redirect()->route('admin.screens.index', $screen)->with('status', 'Creado correctamente.');
    }

    public function edit(string $screen, string $id)
    {
        $this->authorize($screen, 'edit');
        $cfg = $this->getScreen($screen);
        $this->assertScreenWritable($cfg);
        $model = $cfg['model'];
        $item = $model::findOrFail($id);
        if ($model === AdminUser::class) {
            $item->load('roles');
        }
        if ($screen === 'devices' && $item instanceof Device) {
            $item->load('location');
        }
        if ($screen === 'android-users' && $item instanceof User) {
            $item->load('location');
        }

        return view('admin.crud.form', [
            'item' => $item,
            'screen' => $screen,
            'cfg' => $cfg,
            'foreignSelectOptions' => $this->foreignSelectOptionsForForm($cfg),
            'roles' => Role::where('guard_name', 'admin')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, string $screen, string $id)
    {
        $this->authorize($screen, 'edit');
        $cfg = $this->getScreen($screen);
        $this->assertScreenWritable($cfg);
        $model = $cfg['model'];
        $item = $model::findOrFail($id);

        if ($screen === 'devices' && $item instanceof Device) {
            $request->validate([
                'is_enabled' => ['nullable', 'boolean'],
            ]);
            $item->update(['is_enabled' => $request->boolean('is_enabled')]);

            return redirect()->route('admin.screens.index', $screen)->with('status', 'Actualizado correctamente.');
        }

        $data = $this->validatedData($request, $cfg, true, $screen);

        if ($screen === 'families' && $item instanceof Family) {
            $this->applyImageUpload($request, $data, $item, 'families');
        } elseif ($screen === 'products') {
            $this->applyImageUpload($request, $data, $item, 'products');
        }

        $item->update($data);
        if ($model === AdminUser::class) {
            $item->syncRoles($request->input('role_names', []));
        }
        if ($screen === 'android-users' && $item instanceof User) {
            $this->syncAndroidUserLocationPivot($item, $data['location_id'] ?? null);
        }

        return redirect()->route('admin.screens.index', $screen)->with('status', 'Actualizado correctamente.');
    }

    /**
     * Toggle rápido de `status` desde el grid.
     * - licenses: ACTIVE ↔ INACTIVE (EXPIRED / REVOKED son inmutables)
     * - transactions: PAID ↔ VOIDED
     */
    public function toggleStatus(Request $request, string $screen, string $id)
    {
        $this->authorize($screen, 'edit');
        $cfg = $this->getScreen($screen);
        $model = $cfg['model'];

        $item = $model::findOrFail($id);

        if ($screen === 'licenses') {
            $current = $item->status;
            $next = match ($current) {
                'ACTIVE' => 'INACTIVE',
                'INACTIVE' => 'ACTIVE',
                default => $current, // EXPIRED / REVOKED: inmutables vía toggle
            };
            $item->forceFill(['status' => $next])->save();
        } elseif ($screen === 'transactions') {
            $current = $item->status;
            $next = $current === 'PAID' ? 'VOIDED' : 'PAID';
            $item->forceFill(['status' => $next])->save();
        }

        return back()->with('status', 'Estado actualizado.');
    }

    public function destroy(string $screen, string $id)
    {
        $this->authorize($screen, 'delete');
        $cfg = $this->getScreen($screen);
        $this->assertScreenWritable($cfg);
        $model = $cfg['model'];
        $model::findOrFail($id)->delete();

        return back()->with('status', 'Eliminado correctamente.');
    }

    /**
     * @return array<string, Collection<int, array{id: string, label: string}>>
     */
    private function foreignSelectOptionsForForm(array $cfg): array
    {
        $out = [];
        foreach (array_keys($cfg['foreign_labels'] ?? []) as $field) {
            $out[$field] = AdminGridCell::selectOptions($cfg, $field);
        }

        return $out;
    }

    private function syncAndroidUserLocationPivot(User $user, ?string $locationId): void
    {
        if ($locationId !== null && $locationId !== '') {
            $user->locations()->sync([$locationId]);
        } else {
            $user->locations()->detach();
        }
    }

    /**
     * Subida de imagen genérica para familias y productos.
     * Usa el disco «public» y guarda la URL relativa en `image_url`.
     *
     * @param  array<string,mixed>  $data  Datos validados (se muta para set image_url)
     */
    private function applyImageUpload(Request $request, array &$data, ?Model $existing, string $folder): void
    {
        $request->validate([
            ($folder === 'families' ? 'family_image' : 'product_image') => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'],
        ], [
            ($folder === 'families' ? 'family_image' : 'product_image').'.image' => 'El archivo debe ser una imagen.',
        ]);

        $fieldName = $folder === 'families' ? 'family_image' : 'product_image';

        if ($request->hasFile($fieldName)) {
            if ($existing?->image_url) {
                $this->deleteStoredPublicFile($existing->image_url);
                ImageThumbnailService::deleteFor($existing->image_url);
            }
            $path = $request->file($fieldName)->store($folder, 'public');
            $data['image_url'] = Storage::disk('public')->url($path);
            ImageThumbnailService::generate($path);

            return;
        }

        if ($request->boolean('remove_image') && $existing?->image_url) {
            $this->deleteStoredPublicFile($existing->image_url);
            ImageThumbnailService::deleteFor($existing->image_url);
            $data['image_url'] = null;
        }
    }

    private function deleteStoredPublicFile(?string $publicUrl): void
    {
        if ($publicUrl === null || $publicUrl === '') {
            return;
        }
        if (preg_match('#/storage/(.+)$#', $publicUrl, $m)) {
            Storage::disk('public')->delete($m[1]);
        }
    }

    private function validatedData(Request $request, array $cfg, bool $updating = false, ?string $screen = null): array
    {
        foreach (array_keys($cfg['foreign_labels'] ?? []) as $field) {
            if (! empty($cfg['foreign_labels'][$field]['virtual'])) {
                continue;
            }
            if ($request->input($field) === '') {
                $request->merge([$field => null]);
            }
        }

        foreach (['latitude', 'longitude'] as $coord) {
            if (in_array($coord, $cfg['fields'] ?? [], true) && $request->input($coord) === '') {
                $request->merge([$coord => null]);
            }
        }

        if (in_array('barcode', $cfg['fields'] ?? [], true) && $request->input('barcode') === '') {
            $request->merge(['barcode' => null]);
        }

        $rules = [];
        foreach ($cfg['fields'] as $field) {
            if (! empty($cfg['foreign_labels'][$field]['virtual'])) {
                continue;
            }
            if ($field === 'password') {
                if ($screen === 'android-users') {
                    $rules[$field] = array_merge(
                        [$updating ? 'nullable' : 'required'],
                        PasswordPolicy::baseRules('pos')
                    );
                } elseif ($screen === 'admin-users') {
                    $rules[$field] = array_merge(
                        [$updating ? 'nullable' : 'required'],
                        PasswordPolicy::baseRules('admin')
                    );
                } else {
                    $rules[$field] = [$updating ? 'nullable' : 'required', 'string', 'min:8'];
                }
            } elseif (str_ends_with($field, '_at') || in_array($field, ['valid_from', 'valid_to', 'start_time', 'end_time', 'occurred_at', 'synced_at'], true)) {
                $rules[$field] = ['nullable', 'date'];
            } elseif (str_starts_with($field, 'is_')) {
                $rules[$field] = ['nullable', 'boolean'];
            } elseif ($field === 'latitude') {
                $rules[$field] = ['nullable', 'numeric', 'between:-90,90'];
            } elseif ($field === 'longitude') {
                $rules[$field] = ['nullable', 'numeric', 'between:-180,180'];
            } elseif (isset($cfg['foreign_labels'][$field])) {
                $meta = $cfg['foreign_labels'][$field];
                $modelClass = AdminGridCell::relationModel($meta['relation']);
                $m = new $modelClass;
                $rules[$field] = ['nullable', 'uuid', Rule::exists($m->getTable(), $m->getKeyName())];
            } elseif ($screen === 'products' && $field === 'barcode') {
                $rule = Rule::unique('products', 'barcode');
                if ($updating && $request->route('id')) {
                    $rule = $rule->ignore($request->route('id'));
                }
                $rules[$field] = ['nullable', 'string', 'max:64', $rule];
            } else {
                $rules[$field] = ['nullable'];
            }
        }

        if ($screen === 'licenses') {
            $rules['valid_from'] = ['required', 'date'];
            $rules['valid_to'] = ['required', 'date', 'after_or_equal:valid_from'];
        }

        if ($screen === 'android-users') {
            $roleKeys = array_keys($cfg['select_options']['role'] ?? [
                'CASHIER' => '',
                'MANAGER' => '',
                'ADMIN' => '',
            ]);
            $usernameUnique = Rule::unique('users', 'username');
            if ($updating && $request->route('id')) {
                $usernameUnique = $usernameUnique->ignore($request->route('id'), 'id');
            }
            $rules['username'] = ['required', 'string', 'max:50', $usernameUnique];
            $rules['full_name'] = ['nullable', 'string', 'max:100'];
            $rules['role'] = ['required', Rule::in($roleKeys)];
        }

        $data = $request->validate($rules);

        if (isset($data['password']) && $data['password'] !== '') {
            if ($screen === 'admin-users') {
                PasswordPolicy::assertComplexity($data['password'], 'admin');
            } elseif ($screen === 'android-users') {
                PasswordPolicy::assertComplexity($data['password'], 'pos');
            }
        }

        foreach ($cfg['fields'] as $field) {
            if (str_starts_with($field, 'is_')) {
                $data[$field] = $request->boolean($field);
            }
        }

        foreach (array_keys($cfg['foreign_labels'] ?? []) as $field) {
            if (array_key_exists($field, $data) && $data[$field] === '') {
                $data[$field] = null;
            }
        }

        if ($screen === 'android-users' && ! empty($data['password'] ?? null)) {
            $data['pin_sha384'] = User::pinSha384FromPlain((string) $data['password']);
        }

        if (array_key_exists('password', $data)) {
            if (empty($data['password'])) {
                unset($data['password']);
            } else {
                $data['password'] = Hash::make($data['password']);
            }
        }

        if (($cfg['model'] ?? '') === Role::class || ($cfg['model'] ?? '') === Permission::class) {
            $data['guard_name'] = 'admin';
        }

        return $data;
    }
}
