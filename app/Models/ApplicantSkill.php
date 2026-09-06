<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicantSkill extends Model
{
    use HasFactory;

    protected $table = 'applicant_skills';

    protected $fillable = [
        'applicant_id', 'skill', 'proficiency', 'years_of_experience',
    ];

    protected $casts = [
        'years_of_experience' => 'integer',
    ];

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }

    public function setProficiencyAttribute($value): void
    {
        $val = strtolower(trim((string)($value ?? 'intermediate')));
        $this->attributes['proficiency'] = in_array($val, ['beginner', 'intermediate', 'advanced', 'expert'], true) ? $val : 'intermediate';
    }

    public function getProficiencyAttribute(?string $value): string
    {
        return ucfirst($value ?? 'intermediate');
    }

    public function setYearsOfExperienceAttribute($value): void
    {
        $this->attributes['years_of_experience'] = is_numeric($value) ? max(0, (int)$value) : 0;
    }
}
