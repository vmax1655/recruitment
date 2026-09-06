<?php

namespace App\Services;

use App\Models\Applicant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ResumeParserService
{
    protected ResumeTextExtractorService $extractor;
    protected AiProviderClient $aiClient;

    public function __construct(ResumeTextExtractorService $extractor, AiProviderClient $aiClient)
    {
        $this->extractor = $extractor;
        $this->aiClient = $aiClient;
    }

    /**
     * Parse an uploaded resume and return structured candidate profile data.
     */
    public function parse(UploadedFile|string $file): array
    {
        $extracted = $this->extractor->extract($file);
        $rawText = $extracted['text'] ?? '';
        $imageBase64 = $extracted['image_base64'] ?? null;
        $imageMime = $extracted['mime_type'] ?? 'image/jpeg';

        if (empty(trim($rawText)) && empty($imageBase64)) {
            return [
                'success' => false,
                'message' => 'Unable to read the resume file. Please ensure the document is not corrupted.',
                'data' => $this->emptyStructure(),
                'parsed_with' => 'none',
            ];
        }

        // Try AI-powered parsing first if provider is configured
        if ($this->aiClient->isConfigured()) {
            try {
                $aiData = $this->parseWithAi($rawText, $imageBase64, $imageMime);
                if (!empty($aiData) && is_array($aiData)) {
                    $normalized = $this->normalizeData($aiData, $rawText);
                    return [
                        'success' => true,
                        'message' => 'Resume successfully parsed with AI.',
                        'data' => $normalized,
                        'parsed_with' => 'ai',
                        'raw_text' => Str::limit($rawText, 500),
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('AI resume parsing failed, falling back to heuristic parser', ['error' => $e->getMessage()]);
            }
        }

        // Fallback to Rule-Based Heuristic Parser
        $heuristicData = $this->parseWithRules($rawText);

        return [
            'success' => true,
            'message' => 'Resume parsed successfully.',
            'data' => $this->normalizeData($heuristicData, $rawText),
            'parsed_with' => 'heuristic',
            'raw_text' => Str::limit($rawText, 500),
        ];
    }

    /**
     * Apply parsed resume data directly to the applicant's profile in the database.
     */
    public function applyToProfile(Applicant $applicant, array $parsedData, ?string $resumePath = null): array
    {
        $personal = is_array($parsedData['personal'] ?? null) ? $parsedData['personal'] : [];
        $skills = is_array($parsedData['skills'] ?? null) ? $parsedData['skills'] : [];
        $experiences = is_array($parsedData['experiences'] ?? null) ? $parsedData['experiences'] : [];
        $educationList = is_array($parsedData['education'] ?? null) ? $parsedData['education'] : [];
        $certifications = is_array($parsedData['certifications'] ?? null) ? $parsedData['certifications'] : [];

        // 1. Update Personal & Contact Info
        $updateData = [];
        if (!empty($personal['first_name'])) $updateData['first_name'] = substr(trim($personal['first_name']), 0, 255);
        if (!empty($personal['last_name'])) $updateData['last_name'] = substr(trim($personal['last_name']), 0, 255);
        if (!empty($personal['phone'])) $updateData['phone'] = substr(trim($personal['phone']), 0, 50);
        if (!empty($personal['city'])) $updateData['city'] = substr(trim($personal['city']), 0, 255);
        if (!empty($personal['state'])) $updateData['state'] = substr(trim($personal['state']), 0, 255);
        if (!empty($personal['country'])) $updateData['country'] = substr(trim($personal['country']), 0, 255);
        if (!empty($personal['summary'])) $updateData['summary'] = trim($personal['summary']);
        if (!empty($personal['linkedin_url'])) $updateData['linkedin_url'] = substr(trim($personal['linkedin_url']), 0, 255);
        if (!empty($personal['portfolio_url'])) $updateData['portfolio_url'] = substr(trim($personal['portfolio_url']), 0, 255);
        if ($resumePath) $updateData['resume_path'] = $resumePath;

        if (!empty($updateData)) {
            try {
                $applicant->update($updateData);
            } catch (\Throwable $e) {
                Log::warning('Failed to update applicant personal info during resume parse', ['error' => $e->getMessage()]);
            }
        }

        // Also update linked user's name if applicable
        if ($applicant->user && (!empty($personal['first_name']) || !empty($personal['last_name']))) {
            $fullName = trim(($personal['first_name'] ?? $applicant->first_name) . ' ' . ($personal['last_name'] ?? $applicant->last_name));
            if (!empty($fullName)) {
                try {
                    $applicant->user->update(['name' => substr($fullName, 0, 255)]);
                } catch (\Throwable $e) {
                    Log::warning('Failed to update applicant linked user name', ['error' => $e->getMessage()]);
                }
            }
        }

        // 2. Insert Skills (avoid duplicates)
        $addedSkillsCount = 0;
        try {
            $existingSkills = $applicant->skills()->pluck('skill')->map(fn($s) => strtolower(trim($s)))->toArray();
        } catch (\Throwable $e) {
            $existingSkills = [];
        }

        foreach ($skills as $skillItem) {
            $skillName = is_array($skillItem) ? ($skillItem['skill'] ?? '') : $skillItem;
            $skillName = trim((string)$skillName);
            if (empty($skillName)) continue;

            if (!in_array(strtolower($skillName), $existingSkills, true)) {
                $rawProf = is_array($skillItem) ? ($skillItem['proficiency'] ?? 'intermediate') : 'intermediate';
                $prof = strtolower(trim((string)$rawProf));
                $validProf = in_array($prof, ['beginner', 'intermediate', 'advanced', 'expert'], true) ? $prof : 'intermediate';

                $rawYoe = is_array($skillItem) ? ($skillItem['years_of_experience'] ?? 0) : 0;
                $yoe = is_numeric($rawYoe) ? max(0, (int)$rawYoe) : 0;

                try {
                    $applicant->skills()->create([
                        'skill' => substr($skillName, 0, 255),
                        'proficiency' => $validProf,
                        'years_of_experience' => $yoe,
                    ]);
                    $existingSkills[] = strtolower($skillName);
                    $addedSkillsCount++;
                } catch (\Throwable $e) {
                    Log::warning('Failed to insert resume skill', ['skill' => $skillName, 'error' => $e->getMessage()]);
                }
            }
        }

        // 3. Insert Work Experiences
        $addedExpCount = 0;
        foreach ($experiences as $exp) {
            if (!is_array($exp)) continue;
            $company = trim((string)($exp['company'] ?? ''));
            $jobTitle = trim((string)($exp['job_title'] ?? ''));
            if (empty($company) || empty($jobTitle)) continue;

            $companySafe = substr($company, 0, 255);
            $jobTitleSafe = substr($jobTitle, 0, 255);
            $locationSafe = !empty($exp['location']) ? substr(trim((string)$exp['location']), 0, 255) : null;

            try {
                $exists = $applicant->experiences()
                    ->where('company', $companySafe)
                    ->where('job_title', $jobTitleSafe)
                    ->exists();

                if (!$exists) {
                    $applicant->experiences()->create([
                        'company' => $companySafe,
                        'job_title' => $jobTitleSafe,
                        'location' => $locationSafe,
                        'start_date' => $this->parseDate($exp['start_date'] ?? null),
                        'end_date' => !empty($exp['is_current']) ? null : $this->parseDate($exp['end_date'] ?? null),
                        'is_current' => !empty($exp['is_current']),
                        'description' => !empty($exp['description']) ? trim((string)$exp['description']) : null,
                    ]);
                    $addedExpCount++;
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to insert resume experience', ['error' => $e->getMessage()]);
            }
        }

        // 4. Insert Education
        $addedEduCount = 0;
        foreach ($educationList as $edu) {
            if (!is_array($edu)) continue;
            $institution = trim((string)($edu['institution'] ?? ''));
            $degree = trim((string)($edu['degree'] ?? ''));
            if (empty($institution) && empty($degree)) continue;

            $institutionSafe = substr($institution ?: 'Institution', 0, 255);
            $degreeSafe = substr($degree ?: 'Degree', 0, 255);
            $fieldOfStudySafe = !empty($edu['field_of_study']) ? substr(trim((string)$edu['field_of_study']), 0, 255) : null;
            $honorsSafe = !empty($edu['honors']) ? substr(trim((string)$edu['honors']), 0, 255) : null;

            $gpa = null;
            if (isset($edu['gpa']) && is_numeric($edu['gpa'])) {
                $floatGpa = (float)$edu['gpa'];
                if ($floatGpa >= 0 && $floatGpa <= 9.99) {
                    $gpa = round($floatGpa, 2);
                }
            }

            try {
                $exists = $applicant->education()
                    ->where('institution', $institutionSafe)
                    ->where('degree', $degreeSafe)
                    ->exists();

                if (!$exists) {
                    $applicant->education()->create([
                        'institution' => $institutionSafe,
                        'degree' => $degreeSafe,
                        'field_of_study' => $fieldOfStudySafe,
                        'start_date' => $this->parseDate($edu['start_date'] ?? null),
                        'end_date' => $this->parseDate($edu['end_date'] ?? null),
                        'gpa' => $gpa,
                        'honors' => $honorsSafe,
                        'description' => !empty($edu['description']) ? trim((string)$edu['description']) : null,
                    ]);
                    $addedEduCount++;
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to insert resume education', ['error' => $e->getMessage()]);
            }
        }

        // 5. Insert Certifications
        $addedCertCount = 0;
        foreach ($certifications as $cert) {
            if (!is_array($cert)) continue;
            $name = trim((string)($cert['name'] ?? ''));
            if (empty($name)) continue;

            $nameSafe = substr($name, 0, 255);
            $issuerSafe = substr(trim((string)($cert['issuing_organization'] ?? 'Accredited Organization')), 0, 255);
            $credentialIdSafe = !empty($cert['credential_id']) ? substr(trim((string)$cert['credential_id']), 0, 255) : null;
            $credentialUrlSafe = !empty($cert['credential_url']) ? substr(trim((string)$cert['credential_url']), 0, 255) : null;

            try {
                $exists = $applicant->certifications()
                    ->where('name', $nameSafe)
                    ->exists();

                if (!$exists) {
                    $applicant->certifications()->create([
                        'name' => $nameSafe,
                        'issuing_organization' => $issuerSafe,
                        'issue_date' => $this->parseDate($cert['issue_date'] ?? null),
                        'expiry_date' => $this->parseDate($cert['expiry_date'] ?? null),
                        'credential_id' => $credentialIdSafe,
                        'credential_url' => $credentialUrlSafe,
                        'description' => !empty($cert['description']) ? trim((string)$cert['description']) : null,
                    ]);
                    $addedCertCount++;
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to insert resume certification', ['error' => $e->getMessage()]);
            }
        }

        return [
            'skills_added' => $addedSkillsCount,
            'experiences_added' => $addedExpCount,
            'education_added' => $addedEduCount,
            'certifications_added' => $addedCertCount,
        ];
    }

    /**
     * AI-based extraction using structured chat or vision prompt.
     */
    protected function parseWithAi(string $text, ?string $imageBase64 = null, string $imageMime = 'image/jpeg'): ?array
    {
        $instructions = <<<INSTRUCTIONS
You are an expert AI Resume Parser & ATS System. Analyze the provided resume document and extract all candidate information into the exact JSON structure below.

Return ONLY a valid JSON object with this format:
{
  "personal": {
    "first_name": "string (candidate first name)",
    "last_name": "string (candidate last name)",
    "email": "string (email address or null)",
    "phone": "string (phone number or null)",
    "city": "string (city or null)",
    "state": "string (state/province or null)",
    "country": "string (country or null)",
    "summary": "string (2-3 sentence professional summary based on resume)",
    "linkedin_url": "string (url or null)",
    "portfolio_url": "string (website/github/portfolio url or null)"
  },
  "skills": [
    {
      "skill": "string (name of skill, e.g. Customer Service, Problem Solving, PHP)",
      "proficiency": "Beginner | Intermediate | Advanced | Expert",
      "years_of_experience": number or null
    }
  ],
  "experiences": [
    {
      "company": "string (company name)",
      "job_title": "string (role/position)",
      "location": "string or null",
      "start_date": "YYYY-MM-DD or YYYY-MM or YYYY or null",
      "end_date": "YYYY-MM-DD or YYYY-MM or YYYY or null",
      "is_current": boolean,
      "description": "string (responsibilities and achievements)"
    }
  ],
  "education": [
    {
      "institution": "string (university/college/school)",
      "degree": "string (e.g. Bachelor of Science, Master of Business Administration)",
      "field_of_study": "string (e.g. Information Technology, Business Administration)",
      "start_date": "YYYY or YYYY-MM or null",
      "end_date": "YYYY or YYYY-MM or null",
      "gpa": number or null,
      "honors": "string or null",
      "description": "string or null"
    }
  ],
  "certifications": [
    {
      "name": "string (certification name)",
      "issuing_organization": "string (e.g. Microsoft, AWS, Coursera, LinkedIn Learning)",
      "issue_date": "YYYY-MM or YYYY or null",
      "credential_id": "string or null"
    }
  ]
}
INSTRUCTIONS;

        if (strlen(trim($text)) >= 50) {
            $userContent = $instructions . "\n\nResume Text:\n\"\"\"\n{$text}\n\"\"\"";
            $messages = [
                ['role' => 'system', 'content' => 'You are a professional ATS resume parsing engine that extracts resume text into standardized JSON.'],
                ['role' => 'user', 'content' => $userContent],
            ];
        } elseif (!empty($imageBase64)) {
            $messages = [
                ['role' => 'system', 'content' => 'You are a professional ATS resume parsing engine that extracts resume documents into standardized JSON.'],
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => $instructions . "\nPlease extract information from this attached resume document:"],
                        ['type' => 'image_url', 'image_url' => ['url' => "data:{$imageMime};base64,{$imageBase64}"]],
                    ]
                ],
            ];
        } else {
            return null;
        }

        $response = $this->aiClient->chat($messages);
        if (!$response) return null;

        // Try extracting JSON bounded by braces first
        if (preg_match('/\{[\s\S]*\}/', $response, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        $cleanJson = preg_replace('/^```(?:json)?\s*/i', '', trim($response));
        $cleanJson = preg_replace('/\s*```$/', '', $cleanJson);

        $decoded = json_decode($cleanJson, true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Heuristic & Rule-based fallback parser.
     */
    protected function parseWithRules(string $text): array
    {
        $lines = array_values(array_filter(array_map('trim', explode("\n", $text))));
        $fullText = implode("\n", $lines);

        // 1. Extract Email
        $email = null;
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $fullText, $m)) {
            $email = strtolower($m[0]);
        }

        // 2. Extract Phone Number
        $phone = null;
        if (preg_match('/(?:\+?\d{1,3}[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}|\+?\d{10,14}/', $fullText, $m)) {
            $phone = trim($m[0]);
        }

        // 3. Extract Links
        $linkedin = null;
        if (preg_match('/(?:https?:\/\/)?(?:www\.)?linkedin\.com\/in\/[a-zA-Z0-9_-]+/i', $fullText, $m)) {
            $linkedin = Str::startsWith($m[0], 'http') ? $m[0] : 'https://' . $m[0];
        }

        $portfolio = null;
        if (preg_match('/(?:https?:\/\/)?(?:www\.)?(?:github\.com\/[a-zA-Z0-9_-]+|[a-zA-Z0-9_-]+\.(?:me|dev|io|com|org))/i', $fullText, $m)) {
            if (!str_contains($m[0], 'linkedin.com')) {
                $portfolio = Str::startsWith($m[0], 'http') ? $m[0] : 'https://' . $m[0];
            }
        }

        // 4. Extract Candidate Name from Top Lines
        $firstName = 'Candidate';
        $lastName = '';

        for ($i = 0; $i < min(5, count($lines)); $i++) {
            $line = $lines[$i];
            if (str_contains($line, '@') || preg_match('/\d{4}/', $line)) continue;
            if (strlen($line) >= 3 && strlen($line) <= 50 && !preg_match('/(resume|curriculum|cv|profile|contact)/i', $line)) {
                $parts = explode(' ', $line, 2);
                $firstName = $parts[0];
                $lastName = $parts[1] ?? '';
                break;
            }
        }

        // 5. Extract Skills
        $skills = $this->extractSkillsFromText($fullText);

        // 6. Extract Education
        $education = $this->extractEducationFromText($lines);

        // 7. Extract Experience
        $experiences = $this->extractExperienceFromText($lines);

        // 8. Extract Summary
        $summary = $this->extractSummaryFromText($fullText, $lines);

        return [
            'personal' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone,
                'city' => null,
                'state' => null,
                'country' => null,
                'summary' => $summary,
                'linkedin_url' => $linkedin,
                'portfolio_url' => $portfolio,
            ],
            'skills' => $skills,
            'experiences' => $experiences,
            'education' => $education,
            'certifications' => [],
        ];
    }

    /**
     * Dictionary matching for skills in text.
     */
    protected function extractSkillsFromText(string $text): array
    {
        $dictionary = [
            'PHP', 'Laravel', 'JavaScript', 'TypeScript', 'Vue.js', 'React', 'Angular', 'Node.js',
            'Python', 'Django', 'Java', 'Spring Boot', 'C#', '.NET', 'SQL', 'MySQL', 'PostgreSQL',
            'MongoDB', 'HTML5', 'CSS3', 'Tailwind CSS', 'Bootstrap', 'Git', 'Docker', 'Kubernetes',
            'AWS', 'Azure', 'Google Cloud', 'RESTful API', 'GraphQL', 'Linux', 'Agile', 'Scrum',
            'Customer Service', 'Customer Service Excellence', 'Tour Operations', 'Travel Booking',
            'Hospitality Management', 'Project Management', 'Data Analysis', 'UI/UX Design', 'Figma',
            'Problem Solving', 'Communication', 'Leadership', 'Teamwork', 'Time Management',
            'Active Listening', 'Conflict Resolution', 'Booking & Reservation Systems'
        ];

        $matched = [];
        foreach ($dictionary as $skill) {
            if (preg_match('/\b' . preg_quote($skill, '/') . '\b/i', $text)) {
                $matched[] = [
                    'skill' => $skill,
                    'proficiency' => 'intermediate',
                    'years_of_experience' => 2,
                ];
            }
        }

        return $matched;
    }

    /**
     * Heuristic parser for Education entries.
     */
    protected function extractEducationFromText(array $lines): array
    {
        $education = [];
        $degreeKeywords = ['Bachelor', 'Master', 'Doctor', 'Associate', 'BS', 'BA', 'B.S.', 'B.A.', 'MS', 'M.S.', 'MBA', 'Diploma', 'Degree'];

        foreach ($lines as $i => $line) {
            foreach ($degreeKeywords as $keyword) {
                if (stripos($line, $keyword) !== false) {
                    $degree = $line;
                    $institution = $lines[$i - 1] ?? ($lines[$i + 1] ?? 'University / College');

                    $year = null;
                    if (preg_match('/\b(19\d{2}|20\d{2})\b/', $line . ' ' . ($lines[$i + 1] ?? ''), $ym)) {
                        $year = $ym[0];
                    }

                    $education[] = [
                        'institution' => trim($institution),
                        'degree' => trim($degree),
                        'field_of_study' => null,
                        'start_date' => $year ? ($year - 4) . '-01-01' : null,
                        'end_date' => $year ? $year . '-01-01' : null,
                        'gpa' => null,
                        'honors' => null,
                        'description' => null,
                    ];
                    break;
                }
            }
        }

        return array_slice($education, 0, 3);
    }

    /**
     * Heuristic parser for Experience entries.
     */
    protected function extractExperienceFromText(array $lines): array
    {
        $experiences = [];
        $titleKeywords = ['Engineer', 'Developer', 'Manager', 'Specialist', 'Coordinator', 'Officer', 'Consultant', 'Lead', 'Associate', 'Analyst', 'Director', 'Supervisor', 'Intern', 'Representative', 'Staff'];

        foreach ($lines as $i => $line) {
            foreach ($titleKeywords as $keyword) {
                if (stripos($line, $keyword) !== false && strlen($line) < 80) {
                    $title = $line;
                    $company = $lines[$i - 1] ?? ($lines[$i + 1] ?? 'Company');
                    $isCurrent = (bool)preg_match('/\b(present|current|now)\b/i', $line . ' ' . ($lines[$i + 1] ?? ''));

                    $experiences[] = [
                        'company' => trim($company),
                        'job_title' => trim($title),
                        'location' => null,
                        'start_date' => null,
                        'end_date' => null,
                        'is_current' => $isCurrent,
                        'description' => $lines[$i + 2] ?? null,
                    ];
                    break;
                }
            }
        }

        return array_slice($experiences, 0, 4);
    }

    /**
     * Extract or generate summary.
     */
    protected function extractSummaryFromText(string $fullText, array $lines): ?string
    {
        foreach ($lines as $i => $line) {
            if (preg_match('/^(summary|profile|about me|objective|professional summary)/i', $line)) {
                $summaryLines = [];
                for ($j = $i + 1; $j < min($i + 5, count($lines)); $j++) {
                    if (preg_match('/^(experience|education|skills|certifications|projects)/i', $lines[$j])) break;
                    $summaryLines[] = $lines[$j];
                }
                if (!empty($summaryLines)) {
                    return implode(' ', $summaryLines);
                }
            }
        }

        return Str::limit($fullText, 250);
    }

    /**
     * Clean and normalize data structure across all potential AI output variations.
     */
    protected function normalizeData(array $data, string $rawText): array
    {
        $personal = $data['personal'] ?? [];
        $rawContact = $personal['contact'] ?? ($data['contact'] ?? []);

        // 1. First & Last Name
        $firstName = $personal['first_name'] ?? '';
        $lastName = $personal['last_name'] ?? '';
        if (empty($firstName) && !empty($personal['name'])) {
            $parts = explode(' ', trim($personal['name']), 2);
            $firstName = $parts[0] ?? '';
            $lastName = $parts[1] ?? '';
        } elseif (empty($firstName) && !empty($data['name'])) {
            $parts = explode(' ', trim($data['name']), 2);
            $firstName = $parts[0] ?? '';
            $lastName = $parts[1] ?? '';
        }

        // 2. Email & Phone
        $email = $personal['email'] ?? ($rawContact['email'] ?? ($data['email'] ?? ''));
        $phone = $personal['phone'] ?? ($rawContact['phone'] ?? ($data['phone'] ?? ''));

        // 3. Location
        $city = $personal['city'] ?? '';
        $state = $personal['state'] ?? '';
        $country = $personal['country'] ?? ($personal['nationality'] ?? '');
        $locationStr = $personal['location'] ?? ($rawContact['location'] ?? '');
        if (empty($city) && !empty($locationStr)) {
            $locParts = explode(',', $locationStr);
            $city = trim($locParts[0] ?? '');
            if (isset($locParts[1])) {
                $country = trim($locParts[1]);
            }
        }

        // 4. Links
        $linkedin = $personal['linkedin_url'] ?? ($personal['linkedin'] ?? ($rawContact['linkedin'] ?? ($data['linkedin'] ?? '')));
        if ($linkedin && !str_starts_with($linkedin, 'http')) {
            $linkedin = 'https://' . ltrim($linkedin, '/');
        }
        $portfolio = $personal['portfolio_url'] ?? ($personal['portfolio'] ?? ($rawContact['portfolio'] ?? ''));
        if ($portfolio && !str_starts_with($portfolio, 'http')) {
            $portfolio = 'https://' . ltrim($portfolio, '/');
        }

        // 5. Summary
        $summary = $personal['summary'] ?? ($data['summary'] ?? '');

        // 6. Skills
        $rawSkills = is_array($data['skills'] ?? null) ? $data['skills'] : [];
        $skills = [];
        foreach ($rawSkills as $s) {
            if (is_string($s)) {
                $name = trim($s);
                if (!empty($name)) {
                    $skills[] = [
                        'skill' => $name,
                        'proficiency' => 'intermediate',
                        'years_of_experience' => 2,
                    ];
                }
            } elseif (is_array($s) && (!empty($s['skill']) || !empty($s['name']))) {
                $name = trim((string)($s['skill'] ?? $s['name']));
                if (!empty($name)) {
                    $rawProf = strtolower(trim((string)($s['proficiency'] ?? 'intermediate')));
                    $validProf = in_array($rawProf, ['beginner', 'intermediate', 'advanced', 'expert'], true) ? $rawProf : 'intermediate';
                    $rawYoe = $s['years_of_experience'] ?? 2;
                    $yoe = is_numeric($rawYoe) ? max(0, (int)$rawYoe) : 2;

                    $skills[] = [
                        'skill' => $name,
                        'proficiency' => $validProf,
                        'years_of_experience' => $yoe,
                    ];
                }
            }
        }

        // 7. Experiences
        $rawExp = $data['experiences'] ?? ($data['experience'] ?? ($data['work_experience'] ?? []));
        if (isset($rawExp['company'])) $rawExp = [$rawExp]; // if single object
        $experiences = [];
        foreach ($rawExp as $e) {
            if (!is_array($e)) continue;

            $company = $e['company'] ?? ($e['employer'] ?? ($e['organization'] ?? ''));
            $jobTitle = $e['job_title'] ?? ($e['title'] ?? ($e['role'] ?? ($e['position'] ?? '')));
            if (empty($company) && empty($jobTitle)) continue;

            $desc = $e['description'] ?? '';
            if (empty($desc) && !empty($e['responsibilities'])) {
                $desc = is_array($e['responsibilities']) ? implode("\n• ", $e['responsibilities']) : (string)$e['responsibilities'];
            }

            // Duration parsing
            $startDate = $e['start_date'] ?? null;
            $endDate = $e['end_date'] ?? null;
            $isCurrent = !empty($e['is_current']);

            if (empty($startDate) && !empty($e['duration'])) {
                $dur = (string)$e['duration'];
                if (preg_match('/(present|current|now)/i', $dur)) {
                    $isCurrent = true;
                }
                $dates = preg_split('/[–—\-to]+/u', $dur);
                $startDate = trim($dates[0] ?? '');
                if (!$isCurrent && isset($dates[1])) {
                    $endDate = trim($dates[1]);
                }
            }

            $experiences[] = [
                'company' => trim($company),
                'job_title' => trim($jobTitle),
                'location' => $e['location'] ?? null,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'is_current' => $isCurrent,
                'description' => trim($desc),
            ];
        }

        // 8. Education
        $rawEdu = $data['education'] ?? ($data['education_history'] ?? []);
        if (isset($rawEdu['institution']) || isset($rawEdu['degree'])) $rawEdu = [$rawEdu];
        $education = [];
        foreach ($rawEdu as $edu) {
            if (!is_array($edu)) continue;

            $institution = $edu['institution'] ?? ($edu['school'] ?? ($edu['university'] ?? ($edu['college'] ?? '')));
            $degree = $edu['degree'] ?? ($edu['qualification'] ?? ($edu['program'] ?? ''));
            if (empty($institution) && empty($degree)) continue;

            $startDate = $edu['start_date'] ?? null;
            $endDate = $edu['end_date'] ?? null;
            if (empty($endDate) && !empty($edu['years'])) {
                $yDates = preg_split('/[–—\-to]+/u', (string)$edu['years']);
                $startDate = trim($yDates[0] ?? '');
                $endDate = trim($yDates[1] ?? $startDate);
            } elseif (empty($endDate) && !empty($edu['year'])) {
                $endDate = (string)$edu['year'];
            }

            $education[] = [
                'institution' => trim($institution),
                'degree' => trim($degree),
                'field_of_study' => $edu['field_of_study'] ?? ($edu['major'] ?? null),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'gpa' => $edu['gpa'] ?? null,
                'honors' => $edu['honors'] ?? null,
                'description' => $edu['description'] ?? null,
            ];
        }

        // 9. Certifications
        $rawCert = $data['certifications'] ?? ($data['certificates'] ?? []);
        if (isset($rawCert['name'])) $rawCert = [$rawCert];
        $certifications = [];
        foreach ($rawCert as $cert) {
            if (!is_array($cert)) continue;
            $name = $cert['name'] ?? ($cert['title'] ?? ($cert['certification'] ?? ''));
            if (empty($name)) continue;

            $certifications[] = [
                'name' => trim($name),
                'issuing_organization' => $cert['issuing_organization'] ?? ($cert['provider'] ?? ($cert['organization'] ?? ($cert['issuer'] ?? 'Accredited Organization'))),
                'issue_date' => $cert['issue_date'] ?? ($cert['year'] ?? ($cert['date'] ?? null)),
                'credential_id' => $cert['credential_id'] ?? null,
            ];
        }

        return [
            'personal' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone,
                'city' => $city,
                'state' => $state,
                'country' => $country,
                'summary' => $summary,
                'linkedin_url' => $linkedin,
                'portfolio_url' => $portfolio,
            ],
            'skills' => $skills,
            'experiences' => $experiences,
            'education' => $education,
            'certifications' => $certifications,
        ];
    }

    /**
     * Safe date parser helper.
     */
    protected function parseDate(mixed $dateStr): ?string
    {
        if (empty($dateStr)) return null;

        try {
            $dateStr = trim((string)$dateStr);
            if (empty($dateStr)) return null;

            // Handle pure 4-digit year like "2020"
            if (preg_match('/^\d{4}$/', $dateStr)) {
                return $dateStr . '-01-01';
            }
            // Handle year-month like "2020-05" or "2020/05"
            if (preg_match('/^(\d{4})[-\/](\d{1,2})$/', $dateStr, $m)) {
                return sprintf('%04d-%02d-01', (int)$m[1], (int)$m[2]);
            }
            // Ignore ongoing / current keywords
            if (preg_match('/\b(present|current|now|ongoing)\b/i', $dateStr)) {
                return null;
            }

            $parsed = \Carbon\Carbon::parse($dateStr);
            if ($parsed->year < 1900 || $parsed->year > 2100) {
                return null;
            }
            return $parsed->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Empty structure template.
     */
    protected function emptyStructure(): array
    {
        return [
            'personal' => [
                'first_name' => '',
                'last_name' => '',
                'email' => '',
                'phone' => '',
                'city' => '',
                'state' => '',
                'country' => '',
                'summary' => '',
                'linkedin_url' => '',
                'portfolio_url' => '',
            ],
            'skills' => [],
            'experiences' => [],
            'education' => [],
            'certifications' => [],
        ];
    }
}
