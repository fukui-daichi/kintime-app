# CLAUDE.md - Kintime Application Guide for AI Assistants

## Project Overview

**Kintime** (勤怠管理システム) is a Japanese employee timecard management system built with Laravel 11. It handles daily attendance tracking, overtime/night work calculations, and timecard correction approval workflows.

**Primary Users:**
- **Employees (user)**: Clock in/out, track work hours, request timecard corrections
- **Managers (manager)**: Approve timecard corrections for department members
- **Administrators (admin)**: System-wide management (partially implemented)

**Tech Stack:**
- **Backend**: Laravel 11.31, PHP 8.2+, SQLite (default) / MySQL via Docker
- **Frontend**: Blade templates, Tailwind CSS v3, Alpine.js v3, Flowbite components
- **Auth**: Laravel Breeze (session-based)
- **Dev Tools**: Laravel Sail (Docker), PHPUnit, Laravel Pint

---

## Critical Development Guidelines

### 1. Code Location Awareness

**ALWAYS read files before editing.** This codebase uses:
- Service-Repository pattern
- Role-based view separation
- Japanese language throughout UI

**Common file locations:**
```
app/Models/              # 4 core models (User, Timecard, TimecardUpdateRequest, Department)
app/Services/            # Business logic (TimecardService, TimecardUpdateRequestService)
app/Repositories/        # Data access layer (TimecardRepository, TimecardUpdateRequestRepository)
app/Helpers/             # Utilities (TimeHelper, DateHelper)
app/Constants/           # Business constants (WorkTimeConstants)
app/Http/Controllers/    # Controllers (Dashboard, Timecard, TimecardUpdateRequest)
app/Http/Requests/       # Form validation (TimecardUpdateRequestRequest)
resources/views/         # Blade templates (organized by role: admin/manager/user)
routes/web.php           # All application routes
database/migrations/     # 10 migrations defining schema
```

### 2. Language & Localization

**Japanese-First UI:**
- All user-facing text must be in Japanese
- Code comments should be in Japanese where they exist
- Variable names and code are in English
- Date format: "M月D日（dd）" (e.g., 4月25日（金）)
- Time format: 24-hour HH:MM

**Examples:**
```php
// UI text
'出勤打刻' // Clock In
'退勤打刻' // Clock Out
'承認待ち' // Pending
'承認済み' // Approved

// Date formatting
DateHelper::formatJapanese($date) // "4月25日（金）"
```

### 3. Architecture Patterns

**Service-Repository Pattern:**

```
Controller → Service → Repository → Model
```

**DO:**
- Put business logic in Services (`app/Services/`)
- Put database queries in Repositories (`app/Repositories/`)
- Use Helpers for reusable utilities (`app/Helpers/`)
- Keep Controllers thin (validation, authorization, delegation)

**DON'T:**
- Put business logic in Controllers
- Put database queries directly in Services
- Duplicate calculation logic (use TimeHelper)

**Example Flow:**
```php
// TimecardController
public function clockIn(TimecardService $service) {
    $service->clockIn(auth()->user());  // Delegates to service
}

// TimecardService
public function clockIn(User $user) {
    $this->repository->create([...]);  // Uses repository
}

// TimecardRepository
public function create(array $data) {
    return Timecard::create($data);    // Database operation
}
```

### 4. Role-Based Access Control

**Roles:** admin, manager, user (enum in users table)

**Authorization Pattern:**
```php
// In controllers, use model methods
if (!auth()->user()->isManager()) {
    abort(403, '権限がありません。');
}

// Available methods:
$user->isAdmin();   // Role === 'admin'
$user->isManager(); // Role === 'manager'
$user->isUser();    // Role === 'user'
```

**Route Organization:**
```php
// User routes
Route::get('/timecard/update-requests', ...);
Route::post('/timecard/update-requests', ...);

// Manager/Admin routes (separate prefix)
Route::get('/timecard/approval-requests', ...);
Route::post('/timecard/approval-requests/{id}/approve', ...);
```

**View Organization:**
```
views/
├── dashboard/admin/index.blade.php
├── dashboard/manager/index.blade.php
├── dashboard/user/index.blade.php
├── timecard/user/index.blade.php
├── timecard/manager/index.blade.php
└── components/{admin,manager,user}/sidebar.blade.php
```

---

## Core Domain Models

### User (app/Models/User.php)

**Fields:**
```php
employee_number      // Unique employee ID
last_name, first_name
email, password
department_id        // FK to departments
employment_type      // 正社員, 契約社員, パートタイム, 派遣社員
role                 // enum: admin, manager, user
is_active            // boolean
joined_at, leaved_at // Employment dates
```

**Key Methods:**
```php
isAdmin(), isManager(), isUser()  // Role checks
isActive()                        // Check is_active flag
isEmployed()                      // Check if currently employed
getFullNameAttribute()            // last_name + first_name
```

**Relationships:**
```php
department()  // belongsTo Department
timecards()   // hasMany Timecard
```

### Timecard (app/Models/Timecard.php)

**Fields:**
```php
user_id              // FK to users
date                 // Date of work (YYYY-MM-DD)
clock_in             // HH:MM:SS (start time)
clock_out            // HH:MM:SS (end time)
break_start          // HH:MM:SS
break_end            // HH:MM:SS
status               // pending, completed
notes                // Optional notes
overtime_minutes     // Auto-calculated (work_hours > 8)
night_minutes        // Auto-calculated (22:00-05:00 work)
```

**Key Points:**
- One timecard per user per date
- Overtime calculated when work hours exceed 8 hours
- Night work calculated for 22:00-05:00 period
- Status changes from 'pending' to 'completed' after clock_out

**Relationships:**
```php
user()  // belongsTo User
```

### TimecardUpdateRequest (app/Models/TimecardUpdateRequest.php)

**Fields:**
```php
user_id              // Requester (FK to users)
timecard_id          // Timecard to correct (FK to timecards)
approver_id          // Who approved (FK to users, nullable)
original_clock_in    // Original values (for reference)
original_clock_out
original_break_start
original_break_end
corrected_clock_in   // Requested corrections
corrected_clock_out
corrected_break_start
corrected_break_end
reason               // Why correction needed (max 500 chars)
status               // pending, approved, rejected
approved_at          // When approved/rejected
```

**Scopes:**
```php
TimecardUpdateRequest::pending()  // Only pending requests
```

**Relationships:**
```php
user()      // belongsTo User (requester)
approver()  // belongsTo User (as 'approver_id')
timecard()  // belongsTo Timecard
```

### Department (app/Models/Department.php)

**Fields:**
```php
name        // Department name (管理部, 人事部, 開発部)
code        // Unique code
parent_id   // Self-referencing for hierarchy (nullable)
```

**Relationships:**
```php
users()  // hasMany User
```

---

## Business Logic Components

### TimeHelper (app/Helpers/TimeHelper.php)

**Critical calculations** - ALWAYS use these, never reimplement:

```php
// Calculate total work time (excluding breaks)
TimeHelper::calculateWorkMinutes($clockIn, $clockOut, $breakStart, $breakEnd)
// Returns: int (minutes worked)

// Calculate overtime (hours > 8)
TimeHelper::calculateOvertimeMinutes($workMinutes)
// Returns: int (overtime minutes, or 0)

// Calculate night work (22:00-05:00)
TimeHelper::calculateNightMinutes($clockIn, $clockOut, $breakStart, $breakEnd)
// Returns: int (night minutes worked)

// Format minutes to HH:MM
TimeHelper::formatMinutesToHours(480)  // "8:00"

// Calculate monthly totals
TimeHelper::calculateMonthlyTotals($timecards)
// Returns: ['total_minutes' => int, 'overtime_minutes' => int, 'night_minutes' => int]
```

**Constants:**
```php
WorkTimeConstants::DEFAULT_WORK_HOURS = 8
WorkTimeConstants::NIGHT_START_HOUR = 22
WorkTimeConstants::NIGHT_END_HOUR = 5
```

### DateHelper (app/Helpers/DateHelper.php)

```php
// Format date in Japanese
DateHelper::formatJapanese('2025-04-25')  // "4月25日（金）"

// Generate month date list
DateHelper::getMonthDates(2025, 4)  // Array of dates in April 2025
```

### TimecardService (app/Services/TimecardService.php)

**Clock Operations:**
```php
clockIn(User $user)          // Creates today's timecard with clock_in
clockOut(User $user)         // Sets clock_out, calculates overtime/night
startBreak(User $user)       // Sets break_start
endBreak(User $user)         // Sets break_end
```

**Dashboard Data:**
```php
getDashboardData(User $user)  // Role-based dashboard data
// Returns different data based on user role:
// - user: today's timecard, button states, pending requests count
// - manager: same + department pending approvals
// - admin: system statistics (TODO)
```

**Button States:**
```php
getButtonStates(?Timecard $timecard)
// Returns:
[
    'canClockIn' => bool,
    'canClockOut' => bool,
    'canStartBreak' => bool,
    'canEndBreak' => bool
]
```

### TimecardUpdateRequestService (app/Services/TimecardUpdateRequestService.php)

**Key Methods:**
```php
create(User $user, Timecard $timecard, array $data)  // Create correction request
approve(TimecardUpdateRequest $request, User $approver)  // Approve request
reject(TimecardUpdateRequest $request, User $approver)   // Reject request
```

---

## Routing Structure

**File:** `routes/web.php`

### Public Routes
```php
GET  /                   # Role-based dashboard redirect
GET  /dashboard          # DashboardController@index
```

### Timecard Routes (Authenticated)
```php
# Clock operations
POST /timecard/clock-in      # TimecardController@clockIn
POST /timecard/clock-out     # TimecardController@clockOut
POST /timecard/break-start   # TimecardController@startBreak
POST /timecard/break-end     # TimecardController@endBreak

# Timecard management
GET  /timecard               # Monthly list (user/manager specific view)
GET  /timecard/{id}/edit     # Edit timecard (MANAGER ONLY)
PUT  /timecard/{id}          # Update timecard (MANAGER ONLY)
```

### Correction Request Routes (User)
```php
GET  /timecard/update-requests                # User's request list
GET  /timecard/update-requests/create/{id}    # Create request form
POST /timecard/update-requests                # Submit request
GET  /timecard/update-requests/{id}           # View request details
```

### Approval Routes (Manager/Admin)
```php
GET  /timecard/approval-requests              # Pending approvals list
POST /timecard/approval-requests/{id}/approve # Approve/reject request
```

---

## Key Workflows

### 1. Daily Attendance Flow

```
1. Employee arrives → Clicks "出勤打刻" (Clock In)
   ↓
2. Timecard created with clock_in timestamp, status='pending'
   ↓
3. (Optional) Employee takes break
   - Clicks "休憩開始" → break_start set
   - Clicks "休憩終了" → break_end set
   ↓
4. Employee leaves → Clicks "退勤打刻" (Clock Out)
   ↓
5. System calculates:
   - Work minutes (clock_out - clock_in - break)
   - Overtime minutes (if work > 8 hours)
   - Night minutes (22:00-05:00 period)
   - Status changed to 'completed'
```

**Implementation:**
- `TimecardController@clockIn/clockOut/startBreak/endBreak`
- `TimecardService` handles business logic
- `TimeHelper` performs calculations
- `TimecardRepository` persists to database

### 2. Timecard Correction Workflow

```
1. User views monthly timecard → Clicks "申請" (Apply) for specific date
   ↓
2. Fill correction form (TimecardUpdateRequestRequest validates)
   - Select corrected times
   - Enter reason (required, max 500 chars)
   ↓
3. Submit → TimecardUpdateRequest created with status='pending'
   ↓
4. Manager sees request in approval dashboard
   ↓
5. Manager reviews and approves/rejects
   - If approved: timecard_id, approver_id, approved_at set
   - If rejected: status='rejected'
   ↓
6. User sees result in their request list
```

**Implementation:**
- User routes: `TimecardUpdateRequestController@create/store/show`
- Manager routes: `TimecardUpdateRequestController@index/approve`
- Service: `TimecardUpdateRequestService`
- Repository: `TimecardUpdateRequestRepository`

### 3. Role-Based Dashboard Rendering

```
User logs in → DashboardController@index
   ↓
Check user role
   ├─ admin → dashboard.admin.index
   ├─ manager → dashboard.manager.index
   └─ user → dashboard.user.index

Data prepared by TimecardService::getDashboardData($user)
   ├─ user: today's timecard, button states, pending count
   ├─ manager: same + department approvals pending
   └─ admin: system stats (TODO)
```

---

## Database Schema Reference

### Users Table
```sql
employee_number   VARCHAR(255) UNIQUE NOT NULL
last_name         VARCHAR(255) NOT NULL
first_name        VARCHAR(255) NOT NULL
email             VARCHAR(255) UNIQUE NOT NULL
password          VARCHAR(255) NOT NULL
department_id     BIGINT UNSIGNED (FK departments.id)
employment_type   ENUM('正社員','契約社員','パートタイム','派遣社員')
role              ENUM('admin','manager','user') DEFAULT 'user'
is_active         BOOLEAN DEFAULT 1
joined_at         DATE
leaved_at         DATE NULLABLE
```

### Timecards Table
```sql
user_id           BIGINT UNSIGNED (FK users.id)
date              DATE NOT NULL
clock_in          TIME
clock_out         TIME
break_start       TIME
break_end         TIME
status            ENUM('pending','completed') DEFAULT 'pending'
notes             TEXT
overtime_minutes  INT DEFAULT 0
night_minutes     INT DEFAULT 0
UNIQUE(user_id, date)
```

### Timecard_update_requests Table
```sql
user_id              BIGINT UNSIGNED (FK users.id)
timecard_id          BIGINT UNSIGNED (FK timecards.id)
approver_id          BIGINT UNSIGNED (FK users.id) NULLABLE
original_clock_in    TIME
original_clock_out   TIME
original_break_start TIME
original_break_end   TIME
corrected_clock_in   TIME
corrected_clock_out  TIME
corrected_break_start TIME
corrected_break_end  TIME
reason               TEXT NOT NULL
status               ENUM('pending','approved','rejected') DEFAULT 'pending'
approved_at          TIMESTAMP NULLABLE
```

### Departments Table
```sql
name       VARCHAR(255) NOT NULL
code       VARCHAR(50) UNIQUE NOT NULL
parent_id  BIGINT UNSIGNED (FK departments.id) NULLABLE
```

---

## Frontend Guidelines

### Blade Component Organization

**Role-Specific Components:**
```blade
{{-- User sidebar --}}
<x-user.sidebar />
{{-- resources/views/components/user/sidebar.blade.php --}}

{{-- Manager sidebar --}}
<x-manager.sidebar />
{{-- resources/views/components/manager/sidebar.blade.php --}}

{{-- Shared components --}}
<x-header />
<x-main-content />
```

**Layout Structure:**
```blade
{{-- Main layout --}}
@extends('layouts.app')

@section('content')
    {{-- Role-specific content --}}
@endsection
```

### Tailwind CSS Patterns

**Weekend Highlighting:**
```php
@php
$dayOfWeek = Carbon::parse($date)->dayOfWeek;
$bgClass = match($dayOfWeek) {
    6 => 'bg-blue-50',   // Saturday
    0 => 'bg-red-50',    // Sunday
    default => 'bg-white'
};
@endphp
<tr class="{{ $bgClass }}">
```

**Status Badges:**
```blade
@if($status === '承認待ち')
    <span class="px-2 py-1 text-xs font-semibold text-yellow-800 bg-yellow-100 rounded">
        承認待ち
    </span>
@elseif($status === '承認済み')
    <span class="px-2 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded">
        承認済み
    </span>
@endif
```

### Alpine.js Patterns

**Month/Year Selection:**
```blade
<form x-data method="GET" @change="$el.submit()">
    <select name="year" class="...">
        @foreach($years as $y)
            <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>
                {{ $y }}年
            </option>
        @endforeach
    </select>
</form>
```

---

## Testing Guidelines

### Running Tests
```bash
php artisan test                    # All tests
php artisan test --filter TimeHelper  # Specific test
```

### Test Structure
```
tests/
├── Feature/
│   ├── Auth/                # Laravel Breeze auth tests
│   ├── TimecardTest.php     # Timecard feature tests
│   └── ProfileTest.php      # Profile management tests
└── Unit/
    ├── TimeHelperTest.php   # Time calculation tests
    └── Services/            # Service layer tests
```

### Writing Tests

**Unit Test Example:**
```php
public function test_format_minutes_to_hours()
{
    $this->assertEquals('8:00', TimeHelper::formatMinutesToHours(480));
    $this->assertEquals('9:30', TimeHelper::formatMinutesToHours(570));
}
```

**Feature Test Example:**
```php
public function test_user_can_clock_in()
{
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/timecard/clock-in');

    $response->assertRedirect('/dashboard');
    $this->assertDatabaseHas('timecards', [
        'user_id' => $user->id,
        'date' => now()->format('Y-m-d')
    ]);
}
```

---

## Development Workflows

### Local Development Setup

**Using Laravel Sail (Docker):**
```bash
# First time setup
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate:fresh --seed
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev

# Daily development
./vendor/bin/sail up -d       # Start containers
./vendor/bin/sail artisan ... # Run artisan commands
./vendor/bin/sail composer ... # Run composer
./vendor/bin/sail npm ...      # Run npm
```

**Without Docker:**
```bash
php artisan serve              # Start dev server
php artisan migrate:fresh --seed
npm install && npm run dev
```

**Access:**
- App: http://localhost (Sail) or http://localhost:8000
- Vite: http://localhost:5173

### Database Management

**Migrations:**
```bash
php artisan migrate              # Run migrations
php artisan migrate:fresh        # Drop all tables and re-migrate
php artisan migrate:fresh --seed # With seeders
```

**Seeders:**
```bash
php artisan db:seed              # Run all seeders
php artisan db:seed --class=UserSeeder  # Specific seeder
```

**Seeded Users:**
```
Admin:    admin@example.com / password
Manager1: manager1@example.com / password
Manager2: manager2@example.com / password
User1:    user1@example.com / password
User2:    user2@example.com / password
```

### Code Quality

**Laravel Pint (Code Formatting):**
```bash
./vendor/bin/pint          # Format all files
./vendor/bin/pint app/     # Format specific directory
```

**PHPStan (Static Analysis):**
```bash
./vendor/bin/phpstan analyse  # If configured
```

---

## Common Tasks & Examples

### Adding a New Timecard Field

1. **Create Migration:**
```bash
php artisan make:migration add_field_to_timecards_table
```

2. **Update Migration:**
```php
Schema::table('timecards', function (Blueprint $table) {
    $table->string('new_field')->nullable();
});
```

3. **Update Model:**
```php
// app/Models/Timecard.php
protected $fillable = [
    // ... existing fields
    'new_field',
];
```

4. **Update Views:**
```blade
{{-- resources/views/timecard/user/index.blade.php --}}
<td>{{ $timecard->new_field }}</td>
```

5. **Run Migration:**
```bash
php artisan migrate
```

### Adding a New Dashboard Widget (Manager)

1. **Update Service:**
```php
// app/Services/TimecardService.php
public function getDashboardData(User $user)
{
    if ($user->isManager()) {
        return [
            'todayTimecard' => $this->repository->getTodayTimecard($user),
            'buttonStates' => $this->getButtonStates($todayTimecard),
            'pendingRequestsCount' => $this->updateRequestService->getPendingCount($user),
            'newWidget' => $this->getNewWidgetData($user), // Add this
        ];
    }
}

private function getNewWidgetData(User $user)
{
    // Widget logic here
    return [...];
}
```

2. **Update View:**
```blade
{{-- resources/views/dashboard/manager/index.blade.php --}}
<div class="bg-white rounded-lg shadow p-6">
    <h3 class="text-lg font-semibold mb-4">新しいウィジェット</h3>
    {{-- Widget content using $newWidget --}}
</div>
```

### Adding a New Status to TimecardUpdateRequest

1. **Migration:**
```bash
php artisan make:migration update_status_enum_in_timecard_update_requests
```

```php
DB::statement("ALTER TABLE timecard_update_requests MODIFY status ENUM('pending','approved','rejected','new_status') DEFAULT 'pending'");
```

2. **Update Model Scope (if needed):**
```php
// app/Models/TimecardUpdateRequest.php
public function scopeNewStatus($query)
{
    return $query->where('status', 'new_status');
}
```

3. **Update Service:**
```php
// app/Services/TimecardUpdateRequestService.php
public function setNewStatus(TimecardUpdateRequest $request, User $user)
{
    $request->update(['status' => 'new_status']);
}
```

4. **Update Views:**
```blade
@if($request->status === 'new_status')
    <span class="px-2 py-1 text-xs font-semibold text-purple-800 bg-purple-100 rounded">
        新しいステータス
    </span>
@endif
```

---

## Security Considerations

### Authorization Patterns

**ALWAYS check user permissions:**
```php
// In controllers
public function edit(Timecard $timecard)
{
    // Only managers can edit timecards
    if (!auth()->user()->isManager()) {
        abort(403, '権限がありません。');
    }

    // Managers can only edit their department's timecards
    if ($timecard->user->department_id !== auth()->user()->department_id) {
        abort(403, '権限がありません。');
    }

    return view('timecard.edit', compact('timecard'));
}
```

### Input Validation

**Use Form Requests:**
```php
// app/Http/Requests/TimecardUpdateRequestRequest.php
public function rules()
{
    return [
        'timecard_id' => 'required|exists:timecards,id',
        'corrected_clock_in' => 'nullable|date_format:H:i',
        'corrected_clock_out' => 'nullable|date_format:H:i',
        'reason' => 'required|max:500',
    ];
}
```

**In Controllers:**
```php
public function store(TimecardUpdateRequestRequest $request)
{
    // $request->validated() is already validated and safe
}
```

### CSRF Protection

**All forms must include:**
```blade
<form method="POST" action="...">
    @csrf
    {{-- Form fields --}}
</form>
```

### SQL Injection Prevention

**Use Query Builder / Eloquent (NOT raw SQL):**
```php
// Good
Timecard::where('user_id', $userId)->where('date', $date)->first();

// Bad
DB::select("SELECT * FROM timecards WHERE user_id = $userId");
```

---

## Known Issues & Limitations

### TODO / Not Yet Implemented

1. **Admin Dashboard Statistics**
   - `TimecardService::getSystemStatistics()` returns empty array
   - Need to implement system-wide metrics

2. **Paid Leave (有給休暇)**
   - Planned feature, not yet implemented
   - Would require new model and migration

3. **Email Notifications**
   - Email verification available but not enforced
   - No notifications for approval workflow

4. **API Endpoints**
   - Currently web-only application
   - No REST API for mobile apps

5. **Timezone Handling**
   - Config uses UTC, should be Asia/Tokyo for production
   - Dates may display incorrectly for Japanese users

### Current Limitations

1. **No Custom Middleware**
   - Authorization done in controllers
   - Consider creating middleware for role checks

2. **Single Department for Managers**
   - Managers can only see their own department
   - No cross-department visibility

3. **No Timecard Deletion**
   - Can only edit via update requests
   - Consider soft deletes

---

## Quick Reference

### File Path Patterns

```
# Models
app/Models/{ModelName}.php

# Controllers
app/Http/Controllers/{Feature}Controller.php

# Services
app/Services/{Feature}Service.php

# Repositories
app/Repositories/{Feature}Repository.php

# Views (role-based)
resources/views/{feature}/{role}/index.blade.php

# Components
resources/views/components/{role}/sidebar.blade.php

# Migrations
database/migrations/YYYY_MM_DD_HHMMSS_create_{table}_table.php

# Seeders
database/seeders/{Model}Seeder.php

# Tests
tests/Unit/{Class}Test.php
tests/Feature/{Feature}Test.php
```

### Common Artisan Commands

```bash
# Development
php artisan serve
php artisan migrate
php artisan db:seed
php artisan migrate:fresh --seed

# Code generation
php artisan make:model ModelName -m
php artisan make:controller ControllerName
php artisan make:migration migration_name
php artisan make:seeder SeederName
php artisan make:request RequestName

# Testing
php artisan test
php artisan test --filter TestName

# Cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Code quality
./vendor/bin/pint
```

### Git Branch Naming

When working on features, use branch names like:
```
feature/add-paid-leave
bugfix/timecard-calculation
hotfix/authorization-error
```

Current development branch: `claude/claude-md-mik2v85xl8cp817n-01TsGhpRL38hsDjtw6iBC9gH`

---

## Getting Help

### Documentation Resources

- **Laravel 11**: https://laravel.com/docs/11.x
- **Tailwind CSS**: https://tailwindcss.com/docs
- **Alpine.js**: https://alpinejs.dev
- **Flowbite**: https://flowbite.com/docs

### Code Examples

Look at existing implementations:
- **Clock operations**: `app/Http/Controllers/TimecardController.php`
- **Approval workflow**: `app/Services/TimecardUpdateRequestService.php`
- **Time calculations**: `app/Helpers/TimeHelper.php`
- **Role-based views**: `resources/views/dashboard/{role}/index.blade.php`

### Testing

Before pushing changes:
1. Run tests: `php artisan test`
2. Format code: `./vendor/bin/pint`
3. Check browser console for JavaScript errors
4. Test all user roles (admin, manager, user)

---

## Version History

- **2025-01-29**: Initial CLAUDE.md created
  - Comprehensive codebase analysis
  - Architecture and pattern documentation
  - Development workflow guidelines
  - Security and testing guidelines

---

**Last Updated**: 2025-01-29
**Laravel Version**: 11.31
**PHP Version**: 8.2+
