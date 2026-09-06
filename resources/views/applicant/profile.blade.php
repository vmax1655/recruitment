@extends('layouts.applicant')

@section('title', 'My Candidate Profile')

@section('content')
<div class="space-y-6">
    <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:1rem; text-align:left;">
        <div style="text-align:left;">
            <h1 style="font-size:1.5rem; font-weight:700; color:#111827; text-align:left; margin:0;">My Candidate Profile</h1>
            <p style="margin-top:4px; font-size:0.875rem; color:#6b7280; text-align:left;">Keep your information up-to-date. Recruiters will evaluate this profile when reviewing your job applications.</p>
        </div>
        <div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <i class="fa-solid fa-circle-check text-emerald-500"></i> Visible on Applications
            </span>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-emerald-50 border-l-4 border-emerald-400 p-4 rounded-r-md">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-600"></i>
            <p class="text-sm font-medium text-emerald-800">{{ session('success') }}</p>
        </div>
    </div>
    @endif

    @if($errors->any())
    <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-r-md">
        <ul class="list-disc list-inside text-sm font-medium text-red-800 space-y-1">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <!-- AI Resume Smart Auto-Fill Card -->
    <div class="bg-white rounded-lg shadow-sm border border-indigo-200/80 p-6 relative overflow-hidden">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5">
            <!-- Left Header -->
            <div class="flex items-start gap-3.5 max-w-xl">
                <div class="w-10 h-10 rounded-lg bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center shrink-0 text-base">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="text-base font-bold text-gray-900">Fast-Track Your Profile Setup</h3>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                            <i class="fa-solid fa-bolt text-amber-500 text-[10px]"></i> 1-Click AI Auto-Fill
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                        Upload your CV / Resume (<span class="font-medium text-gray-700">PDF, DOCX, or DOC</span>). Our AI parser will automatically extract your contact info, work experiences, education history, and skills directly into your profile form!
                    </p>
                </div>
            </div>

            <!-- Right Upload Form -->
            <form id="aiAutofillForm" method="POST" action="{{ route('applicant.resume.parse-autofill') }}" enctype="multipart/form-data" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 bg-gray-50 border border-gray-200 rounded-lg p-2.5 shrink-0">
                @csrf
                <div class="relative flex-1 min-w-[240px]">
                    <input type="file"
                           name="resume_file"
                           id="aiResumeInput"
                           accept=".pdf,.docx,.doc,.txt"
                           required
                           class="block w-full text-xs text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-600 file:text-white hover:file:bg-indigo-700 cursor-pointer">
                </div>
                <button type="submit"
                        id="aiAutofillBtn"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-md shadow-sm transition-colors whitespace-nowrap cursor-pointer">
                    <i class="fa-solid fa-bolt"></i>
                    <span>Auto-Fill with AI</span>
                </button>
            </form>
        </div>
    </div>

    <!-- AI Loading Overlay (Hidden by default) -->
    <div id="aiLoadingOverlay" class="hidden fixed inset-0 z-50 bg-gray-900/75 backdrop-blur-sm flex flex-col items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-sm w-full text-center space-y-4 border border-gray-100">
            <div class="w-16 h-16 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto text-2xl relative">
                <i class="fa-solid fa-wand-magic-sparkles animate-pulse"></i>
                <div class="absolute inset-0 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
            </div>
            <div>
                <h3 class="text-base font-bold text-gray-900">AI Parsing in Progress</h3>
                <p class="text-xs text-gray-500 mt-1">Reading resume, extracting work history, education degrees, and skills...</p>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                <div class="bg-indigo-600 h-1.5 rounded-full animate-pulse" style="width: 75%"></div>
            </div>
            <p class="text-[11px] text-gray-400">This usually takes about 2 to 5 seconds</p>
        </div>
    </div>

    <!-- 1. Personal Information Form -->
    <form method="POST" action="{{ route('applicant.profile.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4 pb-3 border-b border-gray-100">
                <i class="fa-solid fa-user-gear text-indigo-600 text-lg"></i>
                <h3 class="text-lg font-semibold text-gray-900">Personal & Contact Information</h3>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">First Name <span class="text-red-500">*</span></label>
                    <input type="text" name="first_name" value="{{ old('first_name', $applicant->first_name) }}" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Last Name <span class="text-red-500">*</span></label>
                    <input type="text" name="last_name" value="{{ old('last_name', $applicant->last_name) }}" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                    <input type="text" name="phone" value="{{ old('phone', $applicant->phone) }}" placeholder="+1 (555) 000-0000" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Date of Birth</label>
                    <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $applicant->date_of_birth ? \Carbon\Carbon::parse($applicant->date_of_birth)->format('Y-m-d') : '') }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Gender</label>
                    <select name="gender" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select Gender</option>
                        <option value="male" @selected(old('gender', $applicant->gender) == 'male')>Male</option>
                        <option value="female" @selected(old('gender', $applicant->gender) == 'female')>Female</option>
                        <option value="other" @selected(old('gender', $applicant->gender) == 'other')>Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nationality</label>
                    <input type="text" name="nationality" value="{{ old('nationality', $applicant->nationality) }}" placeholder="e.g. Filipino, American" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Street Address</label>
                    <input type="text" name="address" value="{{ old('address', $applicant->address) }}" placeholder="123 Main Street" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">City</label>
                    <input type="text" name="city" value="{{ old('city', $applicant->city) }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">State / Province</label>
                    <input type="text" name="state" value="{{ old('state', $applicant->state) }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Country</label>
                    <input type="text" name="country" value="{{ old('country', $applicant->country) }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Postal Code</label>
                    <input type="text" name="postal_code" value="{{ old('postal_code', $applicant->postal_code) }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Professional Summary</label>
                    <textarea name="summary" rows="3" placeholder="Brief statement highlighting your career goals, strengths, and expertise..." class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('summary', $applicant->summary) }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700"><i class="fa-brands fa-linkedin text-blue-600 mr-1"></i>LinkedIn URL</label>
                    <input type="url" name="linkedin_url" value="{{ old('linkedin_url', $applicant->linkedin_url) }}" placeholder="https://linkedin.com/in/username" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700"><i class="fa-solid fa-globe text-indigo-600 mr-1"></i>Portfolio / Website URL</label>
                    <input type="url" name="portfolio_url" value="{{ old('portfolio_url', $applicant->portfolio_url) }}" placeholder="https://myportfolio.com" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Master Resume / CV (PDF, DOC, DOCX — max 5MB)</label>
                    <input type="file" name="resume" accept=".pdf,.doc,.docx" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100">
                    @if($applicant->resume_path)
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                        <div class="flex items-center gap-2 text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded border border-emerald-200">
                            <i class="fa-solid fa-file-circle-check text-emerald-600"></i>
                            <span>Current Master Resume is uploaded and active.</span>
                        </div>
                        <a href="{{ route('applicant.resume.preview') }}" target="_blank"
                           class="inline-flex items-center gap-1 px-3 py-1.5 font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 rounded hover:bg-indigo-100 transition-colors">
                            <i class="fa-solid fa-eye"></i> Preview Current Resume
                        </a>
                    </div>
                    @endif
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit" class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition-colors">
                    <i class="fa-solid fa-floppy-disk"></i> Save Personal Details
                </button>
            </div>
        </div>
    </form>

    <!-- 2. Skills Section -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-wand-magic-sparkles text-purple-600 text-lg"></i>
                <h3 class="text-lg font-semibold text-gray-900">Skills & Competencies</h3>
            </div>
            <button type="button" onclick="document.getElementById('skillModal').classList.remove('hidden')" class="inline-flex items-center gap-1.5 text-xs font-semibold bg-purple-50 text-purple-700 px-3 py-1.5 rounded-md hover:bg-purple-100 border border-purple-200 transition-colors">
                <i class="fa-solid fa-plus"></i> Add Skill
            </button>
        </div>

        <div class="flex flex-wrap gap-2.5">
            @forelse($applicant->skills as $s)
            <div class="inline-flex items-center gap-2 bg-indigo-50 border border-indigo-200 text-indigo-900 px-3 py-1.5 rounded-full text-xs font-medium">
                <span class="font-bold">{{ $s->skill }}</span>
                @if($s->proficiency)
                <span class="px-1.5 py-0.5 rounded bg-indigo-200 text-indigo-800 text-[10px] uppercase font-bold">{{ $s->proficiency }}</span>
                @endif
                @if($s->years_of_experience)
                <span class="text-gray-500 text-[11px]">{{ $s->years_of_experience }} yrs</span>
                @endif
                <form method="POST" action="{{ route('applicant.skills.destroy', $s) }}" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" onclick="return confirm('Remove skill {{ $s->skill }}?')" class="text-gray-400 hover:text-red-600 ml-1 transition-colors">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </form>
            </div>
            @empty
            <p class="text-sm text-gray-500 italic">No skills added yet. Click "Add Skill" to list your technical or interpersonal expertise.</p>
            @endforelse
        </div>
    </div>

    <!-- 3. Work Experience Section -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-briefcase text-blue-600 text-lg"></i>
                <h3 class="text-lg font-semibold text-gray-900">Work Experience</h3>
            </div>
            <button type="button" onclick="document.getElementById('experienceModal').classList.remove('hidden')" class="inline-flex items-center gap-1.5 text-xs font-semibold bg-blue-50 text-blue-700 px-3 py-1.5 rounded-md hover:bg-blue-100 border border-blue-200 transition-colors">
                <i class="fa-solid fa-plus"></i> Add Experience
            </button>
        </div>

        <div class="space-y-4">
            @forelse($applicant->experiences as $exp)
            <div class="p-4 rounded-lg border border-gray-200 bg-gray-50/50 flex items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <h4 class="font-bold text-gray-900">{{ $exp->job_title }}</h4>
                        <span class="text-xs font-semibold text-indigo-700 bg-indigo-50 border border-indigo-100 px-2 py-0.5 rounded">@ {{ $exp->company }}</span>
                        @if($exp->is_current)
                        <span class="text-[10px] font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded">CURRENT ROLE</span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        <i class="fa-regular fa-calendar mr-1"></i>
                        {{ $exp->start_date ? \Carbon\Carbon::parse($exp->start_date)->format('M Y') : 'N/A' }} — 
                        {{ $exp->is_current ? 'Present' : ($exp->end_date ? \Carbon\Carbon::parse($exp->end_date)->format('M Y') : 'N/A') }}
                        @if($exp->location) &bull; <i class="fa-solid fa-location-dot mr-1"></i>{{ $exp->location }} @endif
                    </p>
                    @if($exp->description)
                    <p class="text-xs text-gray-700 mt-2 whitespace-pre-line leading-relaxed">{{ $exp->description }}</p>
                    @endif
                </div>
                <form method="POST" action="{{ route('applicant.experience.destroy', $exp) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" onclick="return confirm('Remove experience entry?')" class="text-gray-400 hover:text-red-600 text-sm transition-colors">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </form>
            </div>
            @empty
            <p class="text-sm text-gray-500 italic">No work experience entries added yet. Click "Add Experience" to add your employment background.</p>
            @endforelse
        </div>
    </div>

    <!-- 4. Education Section -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-graduation-cap text-emerald-600 text-lg"></i>
                <h3 class="text-lg font-semibold text-gray-900">Education</h3>
            </div>
            <button type="button" onclick="document.getElementById('educationModal').classList.remove('hidden')" class="inline-flex items-center gap-1.5 text-xs font-semibold bg-emerald-50 text-emerald-700 px-3 py-1.5 rounded-md hover:bg-emerald-100 border border-emerald-200 transition-colors">
                <i class="fa-solid fa-plus"></i> Add Education
            </button>
        </div>

        <div class="space-y-4">
            @forelse($applicant->education as $edu)
            <div class="p-4 rounded-lg border border-gray-200 bg-gray-50/50 flex items-start justify-between gap-4">
                <div>
                    <h4 class="font-bold text-gray-900">{{ $edu->degree }} {{ $edu->field_of_study ? 'in ' . $edu->field_of_study : '' }}</h4>
                    <p class="text-xs font-semibold text-gray-600 mt-0.5">{{ $edu->institution }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                        <i class="fa-regular fa-calendar mr-1"></i>
                        {{ $edu->start_date ? \Carbon\Carbon::parse($edu->start_date)->format('Y') : '' }} — 
                        {{ $edu->end_date ? \Carbon\Carbon::parse($edu->end_date)->format('Y') : 'Present' }}
                        @if($edu->gpa) &bull; GPA: <span class="font-semibold text-gray-800">{{ $edu->gpa }}</span> @endif
                        @if($edu->honors) &bull; <span class="font-semibold text-amber-700">{{ $edu->honors }}</span> @endif
                    </p>
                    @if($edu->description)
                    <p class="text-xs text-gray-700 mt-2 whitespace-pre-line">{{ $edu->description }}</p>
                    @endif
                </div>
                <form method="POST" action="{{ route('applicant.education.destroy', $edu) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" onclick="return confirm('Remove education entry?')" class="text-gray-400 hover:text-red-600 text-sm transition-colors">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </form>
            </div>
            @empty
            <p class="text-sm text-gray-500 italic">No education entries added yet. Click "Add Education" to list your academic background.</p>
            @endforelse
        </div>
    </div>

    <!-- 5. Certifications Section -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-award text-amber-600 text-lg"></i>
                <h3 class="text-lg font-semibold text-gray-900">Licenses & Certifications</h3>
            </div>
            <button type="button" onclick="document.getElementById('certificationModal').classList.remove('hidden')" class="inline-flex items-center gap-1.5 text-xs font-semibold bg-amber-50 text-amber-700 px-3 py-1.5 rounded-md hover:bg-amber-100 border border-amber-200 transition-colors">
                <i class="fa-solid fa-plus"></i> Add Certification
            </button>
        </div>

        <div class="space-y-4">
            @forelse($applicant->certifications as $cert)
            <div class="p-4 rounded-lg border border-gray-200 bg-gray-50/50 flex items-start justify-between gap-4">
                <div>
                    <h4 class="font-bold text-gray-900">{{ $cert->name }}</h4>
                    <p class="text-xs font-semibold text-gray-600 mt-0.5">Issued by {{ $cert->issuing_organization }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                        <i class="fa-regular fa-calendar mr-1"></i>
                        Issued: {{ $cert->issue_date ? \Carbon\Carbon::parse($cert->issue_date)->format('M Y') : 'N/A' }}
                        @if($cert->expiry_date) &bull; Expires: {{ \Carbon\Carbon::parse($cert->expiry_date)->format('M Y') }} @endif
                        @if($cert->credential_id) &bull; ID: <span class="font-mono text-gray-700">{{ $cert->credential_id }}</span> @endif
                    </p>
                    @if($cert->credential_url)
                    <a href="{{ $cert->credential_url }}" target="_blank" class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800 mt-2">
                        View Credential <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                    </a>
                    @endif
                </div>
                <form method="POST" action="{{ route('applicant.certifications.destroy', $cert) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" onclick="return confirm('Remove certification?')" class="text-gray-400 hover:text-red-600 text-sm transition-colors">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </form>
            </div>
            @empty
            <p class="text-sm text-gray-500 italic">No certifications listed. Click "Add Certification" to feature your professional licenses or courses.</p>
            @endforelse
        </div>
    </div>
</div>

<!-- ================= MODALS ================= -->

<style>
    .modal-body { overflow-y: auto; }
    .modal-wrap { display: flex; flex-direction: column; max-height: 88vh; }
</style>

<!-- Skill Modal -->
<div id="skillModal" class="fixed inset-0 z-50 hidden bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full border border-gray-200 modal-wrap">
        <form method="POST" action="{{ route('applicant.skills.store') }}" class="flex flex-col min-h-0 flex-1">
            @csrf
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gray-50 shrink-0">
                <h3 class="text-base font-bold text-gray-900"><i class="fa-solid fa-wand-magic-sparkles text-purple-600 mr-2"></i>Add Skill</h3>
                <button type="button" onclick="document.getElementById('skillModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 text-lg leading-none">&times;</button>
            </div>
            <div class="p-6 space-y-4 modal-body flex-1">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Skill Name <span class="text-red-500">*</span></label>
                    <input type="text" name="skill" required placeholder="e.g. PHP, Laravel, Customer Service" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Proficiency Level</label>
                    <select name="proficiency" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select Level</option>
                        <option value="Beginner">Beginner</option>
                        <option value="Intermediate">Intermediate</option>
                        <option value="Advanced">Advanced</option>
                        <option value="Expert">Expert</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Years of Experience</label>
                    <input type="number" name="years_of_experience" min="0" max="50" placeholder="e.g. 3" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>
            <div class="px-6 py-3.5 bg-gray-50 border-t border-gray-200 flex items-center justify-between shrink-0">
                <button type="button" onclick="document.getElementById('skillModal').classList.add('hidden')" class="px-4 py-2 text-xs font-semibold text-gray-600 bg-white border border-gray-300 hover:bg-gray-100 rounded-md">Cancel</button>
                <button type="submit" style="background-color:#7c3aed;color:#fff;display:inline-flex;align-items:center;gap:6px;padding:8px 20px;border-radius:6px;font-size:12px;font-weight:700;border:none;cursor:pointer;box-shadow:0 1px 3px rgba(0,0,0,.2);" onmouseover="this.style.backgroundColor='#6d28d9'" onmouseout="this.style.backgroundColor='#7c3aed'">
                    <i class="fa-solid fa-floppy-disk"></i> Save Skill
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Experience Modal -->
<div id="experienceModal" class="fixed inset-0 z-50 hidden bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full border border-gray-200 modal-wrap">
        <form method="POST" action="{{ route('applicant.experience.store') }}" class="flex flex-col min-h-0 flex-1">
            @csrf
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gray-50 shrink-0">
                <h3 class="text-base font-bold text-gray-900"><i class="fa-solid fa-briefcase text-blue-600 mr-2"></i>Add Work Experience</h3>
                <button type="button" onclick="document.getElementById('experienceModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 text-lg leading-none">&times;</button>
            </div>
            <div class="p-6 space-y-4 modal-body flex-1">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Job Title <span class="text-red-500">*</span></label>
                    <input type="text" name="job_title" required placeholder="e.g. Software Engineer, Operations Manager" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Company / Organization <span class="text-red-500">*</span></label>
                    <input type="text" name="company" required placeholder="e.g. Acme Services" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Location</label>
                    <input type="text" name="location" placeholder="e.g. Manila, Philippines or Remote" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Start Date <span class="text-red-500">*</span></label>
                        <input type="date" name="start_date" required class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">End Date</label>
                        <input type="date" name="end_date" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_current" id="is_current" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <label for="is_current" class="text-xs font-semibold text-gray-700">I currently work here</label>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Responsibilities & Achievements</label>
                    <textarea name="description" rows="3" placeholder="Key responsibilities, achievements, technologies used..." class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>
            </div>
            <div class="px-6 py-3.5 bg-gray-50 border-t border-gray-200 flex items-center justify-between shrink-0">
                <button type="button" onclick="document.getElementById('experienceModal').classList.add('hidden')" class="px-4 py-2 text-xs font-semibold text-gray-600 bg-white border border-gray-300 hover:bg-gray-100 rounded-md">Cancel</button>
                <button type="submit" style="background-color:#4f46e5;color:#fff;display:inline-flex;align-items:center;gap:6px;padding:8px 20px;border-radius:6px;font-size:12px;font-weight:700;border:none;cursor:pointer;box-shadow:0 1px 3px rgba(0,0,0,.2);" onmouseover="this.style.backgroundColor='#4338ca'" onmouseout="this.style.backgroundColor='#4f46e5'">
                    <i class="fa-solid fa-floppy-disk"></i> Save Experience
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Education Modal -->
<div id="educationModal" class="fixed inset-0 z-50 hidden bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full border border-gray-200 modal-wrap">
        <form method="POST" action="{{ route('applicant.education.store') }}" class="flex flex-col min-h-0 flex-1">
            @csrf
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gray-50 shrink-0">
                <h3 class="text-base font-bold text-gray-900"><i class="fa-solid fa-graduation-cap text-emerald-600 mr-2"></i>Add Education</h3>
                <button type="button" onclick="document.getElementById('educationModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 text-lg leading-none">&times;</button>
            </div>
            <div class="p-6 space-y-4 modal-body flex-1">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Institution / University <span class="text-red-500">*</span></label>
                    <input type="text" name="institution" required placeholder="e.g. University of the Philippines" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Degree / Qualification <span class="text-red-500">*</span></label>
                    <input type="text" name="degree" required placeholder="e.g. Bachelor of Science" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Field of Study</label>
                    <input type="text" name="field_of_study" placeholder="e.g. Computer Science, Business Administration" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Start Date</label>
                        <input type="date" name="start_date" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">End Date (or Expected)</label>
                        <input type="date" name="end_date" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">GPA (optional)</label>
                        <input type="number" step="0.01" min="0" max="4.00" name="gpa" placeholder="e.g. 3.85" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Honors / Awards</label>
                        <input type="text" name="honors" placeholder="e.g. Cum Laude" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Description / Activities</label>
                    <textarea name="description" rows="2" placeholder="Relevant coursework, thesis title, student org..." class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>
            </div>
            <div class="px-6 py-3.5 bg-gray-50 border-t border-gray-200 flex items-center justify-between shrink-0">
                <button type="button" onclick="document.getElementById('educationModal').classList.add('hidden')" class="px-4 py-2 text-xs font-semibold text-gray-600 bg-white border border-gray-300 hover:bg-gray-100 rounded-md">Cancel</button>
                <button type="submit" style="background-color:#4f46e5;color:#fff;display:inline-flex;align-items:center;gap:6px;padding:8px 20px;border-radius:6px;font-size:12px;font-weight:700;border:none;cursor:pointer;box-shadow:0 1px 3px rgba(0,0,0,.2);" onmouseover="this.style.backgroundColor='#4338ca'" onmouseout="this.style.backgroundColor='#4f46e5'">
                    <i class="fa-solid fa-floppy-disk"></i> Save Education
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Certification Modal -->
<div id="certificationModal" class="fixed inset-0 z-50 hidden bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full border border-gray-200 modal-wrap">
        <form method="POST" action="{{ route('applicant.certifications.store') }}" class="flex flex-col min-h-0 flex-1">
            @csrf
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gray-50 shrink-0">
                <h3 class="text-base font-bold text-gray-900"><i class="fa-solid fa-award text-amber-600 mr-2"></i>Add Certification</h3>
                <button type="button" onclick="document.getElementById('certificationModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 text-lg leading-none">&times;</button>
            </div>
            <div class="p-6 space-y-4 modal-body flex-1">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Certification Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required placeholder="e.g. AWS Certified Solutions Architect, PMP" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Issuing Organization <span class="text-red-500">*</span></label>
                    <input type="text" name="issuing_organization" required placeholder="e.g. Amazon Web Services, PMI, Cisco" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Issue Date</label>
                        <input type="date" name="issue_date" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Expiration Date</label>
                        <input type="date" name="expiry_date" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Credential ID (optional)</label>
                    <input type="text" name="credential_id" placeholder="e.g. ABC-123456" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Credential URL (optional)</label>
                    <input type="url" name="credential_url" placeholder="https://verify.credential.com/123" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>
            <div class="px-6 py-3.5 bg-gray-50 border-t border-gray-200 flex items-center justify-between shrink-0">
                <button type="button" onclick="document.getElementById('certificationModal').classList.add('hidden')" class="px-4 py-2 text-xs font-semibold text-gray-600 bg-white border border-gray-300 hover:bg-gray-100 rounded-md">Cancel</button>
                <button type="submit" class="inline-flex items-center gap-1.5 px-5 py-2 text-xs font-bold text-white bg-amber-600 hover:bg-amber-500 rounded-md shadow-sm">
                    <i class="fa-solid fa-floppy-disk"></i> Save Certification
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const autofillForm = document.getElementById('aiAutofillForm');
    const resumeInput = document.getElementById('aiResumeInput');
    const overlay = document.getElementById('aiLoadingOverlay');

    if (autofillForm && overlay) {
        autofillForm.addEventListener('submit', function(e) {
            if (!resumeInput.files || resumeInput.files.length === 0) {
                alert('Please select a resume file (PDF or DOCX) first.');
                e.preventDefault();
                return;
            }

            e.preventDefault();
            overlay.classList.remove('hidden');

            const formData = new FormData(autofillForm);

            fetch(autofillForm.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(async response => {
                let data;
                try {
                    data = await response.json();
                } catch (jsonErr) {
                    throw new Error('Server responded with an unexpected error (' + response.status + '). Please ensure the file is a valid PDF or DOCX document.');
                }
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Failed to parse resume.');
                }
                return data;
            })
            .then(data => {
                // Show success state in overlay before reload
                overlay.innerHTML = `
                    <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-sm w-full text-center space-y-4 border border-emerald-100">
                        <div class="w-16 h-16 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto text-3xl">
                            <i class="fa-solid fa-circle-check animate-bounce"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900">Auto-Fill Complete!</h3>
                            <p class="text-xs text-gray-600 mt-1.5">${data.message}</p>
                        </div>
                        <p class="text-[11px] text-gray-400">Refreshing your profile form...</p>
                    </div>
                `;
                setTimeout(() => {
                    window.location.reload();
                }, 1200);
            })
            .catch(error => {
                overlay.classList.add('hidden');
                if (resumeInput) resumeInput.value = '';
                alert(error.message || 'An error occurred while parsing your resume. Please try again or fill in manually.');
            });
        });
    }
});
</script>
@endpush

@endsection
