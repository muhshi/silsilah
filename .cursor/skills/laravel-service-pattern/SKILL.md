---
name: laravel-service-pattern
description: Refactor Fat Controllers (>300 lines) in Laravel applications into a clean Service Layer Architecture (app/Services/). Enforces Slim Controllers, Dependency Injection, and Single Responsibility.
---

# Laravel Service Pattern & Controller Refactoring Skill

This skill provides guidelines and patterns for detecting **Fat Controllers** in Laravel applications and refactoring them into a clean, maintainable **Service Layer Architecture** (`app/Services/`).

## 1. Fat Controller Threshold & Detection

A Laravel Controller is classified as a **Fat Controller (Code Smell)** if it meets ANY of the following criteria:
- **Line Count**: Total lines exceed **300 lines**.
- **Multiple Responsibilities**: Handles HTTP requests, complex database aggregation, business calculations, and third-party file generation (e.g. Excel/PDF) all in one file.
- **Inline Query Pollution**: Contains long raw SQL or complex Eloquent query builder chains directly inside controller methods.

### ⚠️ Developer Notification Protocol (Mandatory Warning)
When analyzing or touching a Controller that meets the Fat Controller criteria, **ALWAYS notify the developer first** with a clear warning:

> ⚠️ **Pemberitahuan Arsitektur Laravel**:
> Controller `[ControllerName].php` telah mencapai **[X] baris** dan menangani beberapa tanggung jawab (Query, Bisnis Logic, Export).
> 
> **Rekomendasi**: Lakukan refactoring ke **Service Layer Pattern** (`app/Services/`) agar Controller tetap ringkas (~50-100 baris) dan mudah di-maintain.
> Apakah Anda ingin saya terapkan arsitektur Service ini?

---

## 2. Target Service Architecture Structure

```
app/
├── Http/
│   └── Controllers/
│       └── [Domain]Controller.php        (~50-100 lines: Slim HTTP coordinator)
└── Services/
    ├── [Domain]DataService.php           (Database queries, aggregations, data fetching)
    ├── [Domain]BusinessService.php       (Core calculations, algorithms, business rules)
    └── [Domain]ExportService.php         (File generation: Excel, PDF, CSV)
```

---

## 3. Refactoring Step-by-Step

### Step 1: Create Dedicated Service Classes in `app/Services/`
Group responsibilities logically:
- **Data/Query Services**: Move complex `DB::table(...)` or Eloquent query chains into dedicated methods.
- **Business/Calculation Services**: Move math, scoring, dynamic targets, status checks, and business logic into pure methods.
- **Export/Report Services**: Move PhpSpreadsheet, DomPDF, or CSV formatting logic.

### Step 2: Inject Services into Controller via Constructor
```php
namespace App\Http\Controllers;

use App\Services\UserDataService;
use App\Services\UserRankingService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        protected UserDataService $dataService,
        protected UserRankingService $rankingService
    ) {}

    public function index(Request $request)
    {
        $records = $this->dataService->getFilteredUsers($request);
        $ranking = $this->rankingService->calculateRanking($records);

        return view('users.index', compact('records', 'ranking'));
    }
}
```

---

## 4. Quality & Testing Checklist
- [ ] Controller file size is reduced to **< 150 lines**.
- [ ] No raw SQL query chains remain directly inside controller actions.
- [ ] Run `php -l` on all new Service files and Controllers to verify syntax.
- [ ] Run `php artisan test` to verify zero regression in feature functionality.
