@extends('layouts.employee')
@section('title', 'My Profile')
@section('page-title', 'My Profile')
@push('styles')
<style>
.profile-hero{background:linear-gradient(135deg,#0f172a 0%,#4F39F6 100%);border-radius:16px;padding:28px 32px;display:flex;align-items:center;gap:24px;margin-bottom:24px;color:#fff}
.profile-avatar-circle{width:80px;height:80px;background:linear-gradient(135deg,#6366f1,#4F39F6);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:32px;font-weight:800;color:#fff;flex-shrink:0;border:3px solid rgba(255,255,255,.2)}
.profile-hero-name{font-size:22px;font-weight:800;color:#fff}
.profile-hero-sub{font-size:13px;color:#cbd5e1;margin-top:4px}
.profile-hero-badges{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}
.hero-badge{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);border-radius:7px;padding:4px 10px;font-size:11.5px;font-weight:600;color:#e0e7ff;display:flex;align-items:center;gap:5px}
.info-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px}
.info-field{padding:14px 16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px}
.info-field-label{font-size:10.5px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#94a3b8;margin-bottom:4px}
.info-field-value{font-size:14px;font-weight:600;color:#0f172a}
.info-field-empty{color:#cbd5e1;font-style:italic;font-weight:400}
.section-title{font-size:15px;font-weight:700;color:#0f172a;margin-bottom:14px;display:flex;align-items:center;gap:8px}
.section-title i{color:#4F39F6}
.status-badge{display:inline-flex;align-items:center;padding:4px 12px;border-radius:99px;font-size:12px;font-weight:700}
.status-active{background:#d1fae5;color:#065f46}
.status-inactive{background:#fee2e2;color:#991b1b}
.status-probationary{background:#fef3c7;color:#92400e}
</style>
@endpush
@section('content')
{{-- Hero --}}
<div class="profile-hero">
    <div class="profile-avatar-circle">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
    <div>
        <div class="profile-hero-name">{{ $profile ? $profile->full_name : auth()->user()->name }}</div>
        <div class="profile-hero-sub">{{ $profile && $profile->jobPosition ? $profile->jobPosition->title : 'Employee' }} &bull; {{ $profile && $profile->department ? $profile->department->name : 'Unassigned' }}</div>
        <div class="profile-hero-badges">
            @if($profile)
                <div class="hero-badge"><i class="fa-solid fa-id-badge"></i> {{ $profile->employee_id ?? '—' }}</div>
                @if($profile->hire_date)<div class="hero-badge"><i class="fa-solid fa-calendar-day"></i> Hired {{ $profile->hire_date->format('M d, Y') }}</div>@endif
                <div class="hero-badge">
                    @php $es = $profile->employment_status ?? 'active'; @endphp
                    <i class="fa-solid fa-circle"></i> {{ ucfirst($es) }}
                </div>
            @else
                <div class="hero-badge"><i class="fa-solid fa-clock"></i> Profile being set up by HR</div>
            @endif
        </div>
    </div>
</div>

@if($profile)
<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-user" style="color:#4F39F6;margin-right:6px"></i>Personal Information</div>
        <span class="status-badge {{ match($profile->status ?? 'active'){ 'active'=>'status-active','inactive'=>'status-inactive',default=>'status-probationary' } }}">
            {{ ucfirst($profile->status ?? 'Active') }}
        </span>
    </div>
    <div class="card-body">
        <div class="info-grid">
            <div class="info-field">
                <div class="info-field-label">First Name</div>
                <div class="info-field-value">{!! $profile->first_name ? e($profile->first_name) : '<span class="info-field-empty">Not set</span>' !!}</div>
            </div>
            <div class="info-field">
                <div class="info-field-label">Last Name</div>
                <div class="info-field-value">{!! $profile->last_name ? e($profile->last_name) : '<span class="info-field-empty">Not set</span>' !!}</div>
            </div>
            <div class="info-field">
                <div class="info-field-label">Email</div>
                <div class="info-field-value">{!! $profile->email ? e($profile->email) : '<span class="info-field-empty">Not set</span>' !!}</div>
            </div>
            <div class="info-field">
                <div class="info-field-label">Phone</div>
                <div class="info-field-value">{!! $profile->phone ? e($profile->phone) : '<span class="info-field-empty">Not set</span>' !!}</div>
            </div>
            <div class="info-field">
                <div class="info-field-label">Date of Birth</div>
                <div class="info-field-value">{!! $profile->date_of_birth ? e($profile->date_of_birth->format('M d, Y')) : '<span class="info-field-empty">Not set</span>' !!}</div>
            </div>
            <div class="info-field">
                <div class="info-field-label">Gender</div>
                <div class="info-field-value">{!! $profile->gender ? e(ucfirst($profile->gender)) : '<span class="info-field-empty">Not set</span>' !!}</div>
            </div>
            <div class="info-field">
                <div class="info-field-label">Nationality</div>
                <div class="info-field-value">{!! $profile->nationality ? e($profile->nationality) : '<span class="info-field-empty">Not set</span>' !!}</div>
            </div>
            <div class="info-field">
                <div class="info-field-label">Address</div>
                <div class="info-field-value">{!! $profile->address ? e($profile->address . ($profile->city ? ', ' . $profile->city : '')) : '<span class="info-field-empty">Not set</span>' !!}</div>
            </div>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-briefcase" style="color:#4F39F6;margin-right:6px"></i>Employment Details</div>
    </div>
    <div class="card-body">
        <div class="info-grid">
            <div class="info-field">
                <div class="info-field-label">Employee ID</div>
                <div class="info-field-value">{!! $profile->employee_id ? e($profile->employee_id) : '<span class="info-field-empty">Not assigned</span>' !!}</div>
            </div>
            <div class="info-field">
                <div class="info-field-label">Department</div>
                <div class="info-field-value">{!! $profile->department ? e($profile->department->name) : '<span class="info-field-empty">Not assigned</span>' !!}</div>
            </div>
            <div class="info-field">
                <div class="info-field-label">Job Position</div>
                <div class="info-field-value">{!! $profile->jobPosition ? e($profile->jobPosition->title) : '<span class="info-field-empty">Not assigned</span>' !!}</div>
            </div>
            <div class="info-field">
                <div class="info-field-label">Employment Status</div>
                <div class="info-field-value">
                    @php $es = $profile->employment_status ?? 'active'; @endphp
                    <span class="status-badge {{ match($es){ 'active','regular'=>'status-active','probationary'=>'status-probationary',default=>'status-inactive' } }}">{{ ucfirst($es) }}</span>
                </div>
            </div>
            <div class="info-field">
                <div class="info-field-label">Hire Date</div>
                <div class="info-field-value">{!! $profile->hire_date ? e($profile->hire_date->format('M d, Y')) : '<span class="info-field-empty">Not set</span>' !!}</div>
            </div>
            @if($profile->regularization_date)
            <div class="info-field">
                <div class="info-field-label">Regularization Date</div>
                <div class="info-field-value">{{ $profile->regularization_date->format('M d, Y') }}</div>
            </div>
            @endif
        </div>
    </div>
</div>
@else
<div class="card">
    <div class="card-body" style="text-align:center;padding:48px 24px">
        <i class="fa-solid fa-id-card-clip" style="font-size:48px;color:#cbd5e1;display:block;margin-bottom:14px"></i>
        <h3 style="font-size:16px;font-weight:700;color:#334155;margin-bottom:6px">Employee profile not set up yet</h3>
        <p style="font-size:13.5px;color:#94a3b8">Your HR administrator is currently setting up your employee profile. Please check back later.</p>
    </div>
</div>
@endif
@endsection
