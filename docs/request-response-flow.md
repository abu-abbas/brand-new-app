# Alur Request & Response — Vue ↔ Laravel API

## Legenda Warna

| Warna | Keterangan |
|---|---|
| 🟦 **Biru** | **Ditulis programmer** — file yang di-maintain manual |
| 🟧 **Oranye** | **Auto-generated** — dihasilkan oleh Orval atau Scramble, **jangan diedit manual** |
| 🟩 **Hijau** | **Library/Framework** — package yang sudah terinstal (Axios, TanStack Query, Laravel) |

---

## Diagram Alur (Request → Response)

```mermaid
flowchart TD
    subgraph FRONTEND["🖥️ Frontend - Vue 3 + TypeScript"]
        direction TB

        A["🟦 Vue Component\nDataTableExample.vue"]
        B["🟦 Query Hook\nuse-users-query.ts"]
        C["🟦 API Facade\nuser-management.facade.ts"]
        D["🟧 Generated Client\nusers.ts"]
        E["🟦 Axios Instance\nlib/axios.ts"]
        F["🟧 Generated Types\nmodels/*.ts"]

        A -->|"useUsersQuery(params)"| B
        B -->|"UserManagementFacade.getUsers()"| C
        C -->|"usersIndex(params)"| D
        D -->|"customAxiosInstance(config)"| E
        F -.->|"type import"| A
        F -.->|"type import"| B
        F -.->|"type import"| C
        F -.->|"type import"| D
    end

    subgraph NETWORK["🌐 HTTP Request"]
        G["GET /api/users?page=1&per_page=10"]
    end

    subgraph BACKEND["⚙️ Backend - Laravel"]
        direction TB

        H["🟦 Route\nroutes/api.php"]
        I["🟦 FormRequest\nListUserRequest.php"]
        J["🟦 Controller\nUserController.php"]
        K["🟦 Service\nUserService.php"]
        L["🟦 API Resource\nUserResource.php"]
        M["🟧 OpenAPI Spec\ndocs/openapi.json"]

        H -->|"middleware + dispatch"| I
        I -->|"validated()"| J
        J -->|"getPaginatedUsers()"| K
        K -->|"return paginator"| J
        J -->|"UserResource::collection()"| L
        L -->|"Scramble export"| M
    end

    E -->|"HTTP Request"| G
    G -->|"Route matching"| H
    L -->|"JSON Response"| G
    G -->|"Axios Response"| E
    M -->|"Orval generate"| D
    M -->|"Orval generate"| F

    style A fill:#4A90D9,color:#fff
    style B fill:#4A90D9,color:#fff
    style C fill:#4A90D9,color:#fff
    style E fill:#4A90D9,color:#fff
    style H fill:#4A90D9,color:#fff
    style I fill:#4A90D9,color:#fff
    style J fill:#4A90D9,color:#fff
    style K fill:#4A90D9,color:#fff
    style L fill:#4A90D9,color:#fff

    style D fill:#E8833A,color:#fff
    style F fill:#E8833A,color:#fff
    style M fill:#E8833A,color:#fff

    style G fill:#2ECC71,color:#fff
```

---

## Detail Per Layer

### 1️⃣ Vue Component (🟦 Manual)

**File**: `resources/js/components/custom-ui/data-table/DataTableExample.vue`

Component memanggil query hook atau facade. **Tidak boleh** import generated client atau Axios langsung.

```vue
<!-- ✅ Benar: lewat query hook -->
const { data, isLoading } = useUsersQuery(paramsRef);

<!-- ✅ Benar: lewat facade (untuk non-query) -->
await UserManagementFacade.getUsers(params, signal);

<!-- ❌ Salah: langsung import generated -->
import { usersIndex } from '@/api/generated/users';
```

---

### 2️⃣ Query Hook (🟦 Manual)

**File**: `resources/js/modules/user-management/queries/use-users-query.ts`

Membungkus TanStack Query `useQuery` dengan facade sebagai `queryFn`. Menangani caching, stale time, dan placeholder data.

```typescript
export function useUsersQuery(paramsRef: Ref<UsersIndexParams>) {
  return useQuery({
    queryKey: computed(() => ['users', paramsRef.value]),
    queryFn: ({ signal }) => UserManagementFacade.getUsers(paramsRef.value, signal),
  });
}
```

---

### 3️⃣ API Facade (🟦 Manual)

**File**: `resources/js/modules/user-management/api/user-management.facade.ts`

**Satu-satunya tempat** yang boleh import generated client. Berfungsi sebagai abstraction layer — kalau generated client berubah, cukup update di sini.

```typescript
import { usersIndex } from '@/api/generated/users';

export class UserManagementFacade {
  static async getUsers(params?, signal?): Promise<UsersIndex200> {
    return usersIndex(params, { signal });
  }
}
```

---

### 4️⃣ Generated Client (🟧 Auto-generated oleh Orval)

**File**: `resources/js/api/generated/users.ts`

Di-generate dari `docs/openapi.json`. Berisi fungsi HTTP call yang sudah type-safe.

```typescript
// ⚠️ JANGAN EDIT — file ini di-generate ulang setiap `npm run generate:api`
export const usersIndex = (params?: UsersIndexParams, options?) => {
  return customAxiosInstance<UsersIndex200>({
    url: '/users', method: 'GET', params
  }, options);
};
```

---

### 5️⃣ Generated Types (🟧 Auto-generated oleh Orval)

**Folder**: `resources/js/api/generated/models/`

Type TypeScript yang match 1:1 dengan OpenAPI spec. Contoh:

| File | Isi | Asal |
|---|---|---|
| `usersIndex200.ts` | `{ data, links, meta }` | Response `GET /api/users` |
| `usersIndexParams.ts` | `{ page, per_page, search, sort_by, ... }` | Query params |
| `paginationMeta.ts` | `{ current_page, total, ... }` | Shared pagination |
| `paginationLinks.ts` | `{ first, last, prev, next }` | Shared pagination |
| `userResource.ts` | `{ id, name, email, ... }` | `UserResource.php` |

---

### 6️⃣ Axios Instance (🟦 Manual)

**File**: `resources/js/lib/axios.ts`

Konfigurasi Axios global: `baseURL`, `withCredentials`, interceptor untuk `X-Request-Id` dan error handling.

```typescript
export const customAxiosInstance = <T>(config, options?): Promise<T> => {
  return axiosInstance({ ...config, ...options }).then(({ data }) => data);
};
```

---

### 7️⃣ Route (🟦 Manual)

**File**: `routes/api.php`

```php
Route::get('/users', [UserController::class, 'index'])->name('api.users.index');
```

---

### 8️⃣ FormRequest (🟦 Manual)

**File**: `app/Http/Requests/User/ListUserRequest.php`

Validasi input request. Scramble membaca rules di sini untuk generate parameter schema di OpenAPI.

---

### 9️⃣ Controller (🟦 Manual)

**File**: `app/Http/Controllers/Api/UserController.php`

Orkestrasi saja — tidak ada business logic. Panggil service, return resource.

```php
public function index(ListUserRequest $request): AnonymousResourceCollection
{
    $users = $this->userService->getPaginatedUsers($request->validated());
    return UserResource::collection($users);
}
```

---

### 🔟 Service (🟦 Manual)

**File**: `app/Services/UserService.php`

Business logic murni: query builder, filtering, sorting, pagination.

---

### 1️⃣1️⃣ API Resource (🟦 Manual)

**File**: `app/Http/Resources/UserResource.php`

Mendefinisikan shape response JSON. **Ini yang menjadi source of truth** — Scramble membaca ini untuk generate OpenAPI spec.

```php
return [
    'id'         => $this->id,
    'name'       => $this->name,
    'email'      => $this->email,
    'unit'       => ['name' => $this->unit_name ?? 'Umum'],
    'roles'      => [$this->role ?? 'Staff'],
    'active'     => (bool) $this->is_active,
    'created_at' => $this->created_at?->toIso8601String(),
];
```

---

### 1️⃣2️⃣ OpenAPI Spec (🟧 Auto-generated oleh Scramble)

**File**: `docs/openapi.json`

Di-generate dari kode Laravel (Controller, FormRequest, Resource) oleh Scramble. Ini jadi **input** untuk Orval.

---

## Alur Generate (Code → Spec → Client)

```mermaid
flowchart LR
    subgraph BACKEND_CODE["🟦 Kode Laravel - Manual"]
        A["Controller"]
        B["FormRequest"]
        C["API Resource"]
    end

    subgraph SCRAMBLE["🟧 Scramble - Auto"]
        D["php artisan\nscramble:export"]
    end

    subgraph SPEC["🟧 OpenAPI Spec"]
        E["docs/openapi.json"]
    end

    subgraph ORVAL["🟧 Orval - Auto"]
        F["npx orval"]
    end

    subgraph TS_CLIENT["🟧 TypeScript Client"]
        G["api/generated/users.ts"]
        H["api/generated/models/*.ts"]
    end

    A --> D
    B --> D
    C --> D
    D --> E
    E --> F
    F --> G
    F --> H

    style A fill:#4A90D9,color:#fff
    style B fill:#4A90D9,color:#fff
    style C fill:#4A90D9,color:#fff
    style D fill:#E8833A,color:#fff
    style E fill:#E8833A,color:#fff
    style F fill:#E8833A,color:#fff
    style G fill:#E8833A,color:#fff
    style H fill:#E8833A,color:#fff
```

**Command**: `npm run generate:api` = `php artisan scramble:export` → `npx orval`

---

## Mapping File Lengkap

### Frontend

| Layer | File | Tipe | Lokasi |
|---|---|---|---|
| Component | `DataTableExample.vue` | 🟦 Manual | `resources/js/components/custom-ui/data-table/` |
| Query Hook | `use-users-query.ts` | 🟦 Manual | `resources/js/modules/user-management/queries/` |
| API Facade | `user-management.facade.ts` | 🟦 Manual | `resources/js/modules/user-management/api/` |
| Generated Client | `users.ts` | 🟧 Generated | `resources/js/api/generated/` |
| Generated Types | `models/*.ts` | 🟧 Generated | `resources/js/api/generated/models/` |
| Axios Instance | `axios.ts` | 🟦 Manual | `resources/js/lib/` |

### Backend

| Layer | File | Tipe | Lokasi |
|---|---|---|---|
| Route | `api.php` | 🟦 Manual | `routes/` |
| FormRequest | `ListUserRequest.php` | 🟦 Manual | `app/Http/Requests/User/` |
| Controller | `UserController.php` | 🟦 Manual | `app/Http/Controllers/Api/` |
| Service | `UserService.php` | 🟦 Manual | `app/Services/` |
| API Resource | `UserResource.php` | 🟦 Manual | `app/Http/Resources/` |
| OpenAPI Spec | `openapi.json` | 🟧 Generated | `docs/` |

---

## Aturan Penting

> **⚠️ CAUTION**: File 🟧 oranye JANGAN diedit manual! Setiap perubahan akan hilang saat regenerate.

> **❗ IMPORTANT — Alur perubahan yang benar:**
> 1. Ubah kode Laravel (Controller, FormRequest, Resource) — ini source of truth
> 2. Jalankan `npm run generate:api` — Scramble + Orval regenerate
> 3. Frontend otomatis dapat type baru — tidak perlu tulis type manual

> **💡 TIP**: Component Vue tidak boleh import langsung dari `@/api/generated/`. Semua akses data harus lewat **API Facade** → supaya ada satu titik kontrol jika generated client berubah.
