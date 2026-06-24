<?php

namespace App\Livewire\Registers;

use App\Models\Facility;
use App\Models\Lga;
use App\Models\LinkedChild;
use App\Models\Patient;
use App\Models\Registrations\ImmunizationRegistration;
use App\Models\State;
use App\Models\Ward;
use App\Services\Patients\PatientPortalAccountService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dataOfficerLayout')]
#[Lazy]
class ImmunizationRegister extends Component
{
  public $din;
  public $isPatientVerified = false;
  public $isNewPatient = false;
  public $hasImmunizationRegistration = false;
  public $patient_registration_facility = '';

  public $patient_id;
  public $first_name;
  public $last_name;
  public $middle_name;
  public $patient_gender;
  public $patient_dob;
  public $patient_age;
  public $patient_phone;
  public $patient_email;
  public $state_id;
  public $lga_id;
  public $ward_id;

  public $registration_date;
  public $follow_up_phone;
  public $follow_up_address;
  public $notes;

  public $children = [];
  public $existingLinkedChildren = [];

  public $states = [];
  public $lgas = [];
  public $wards = [];

  public $facility_id;
  public $facility_name;
  public $facility_state;
  public $facility_lga;
  public $facility_ward;
  public $officer_name;
  public $officer_role;
  public $officer_designation;

  protected function rules()
  {
    $rules = [
      'registration_date' => 'required|date',
      'follow_up_phone' => 'nullable|string|max:20',
      'follow_up_address' => 'nullable|string|max:500',
      'notes' => 'nullable|string|max:1000',
    ];

    if (!$this->patient_id) {
      $rules['first_name'] = 'required|string|max:255';
      $rules['last_name'] = 'nullable|string|max:255';
      $rules['middle_name'] = 'nullable|string|max:255';
      $rules['patient_gender'] = 'required|in:Male,Female';
      $rules['patient_dob'] = 'required|date|before:today';
      $rules['patient_phone'] = 'required|string|max:20';
      $rules['patient_email'] = 'nullable|email|max:150';
      $rules['state_id'] = 'required|exists:states,id';
      $rules['lga_id'] = 'required|exists:lgas,id';
      $rules['ward_id'] = 'nullable|exists:wards,id';
    } else {
      $rules['patient_id'] = 'required|exists:patients,id';
    }

    if (count($this->children) > 0) {
      $rules['children.*.first_name'] = 'required|string|max:255';
      $rules['children.*.middle_name'] = 'nullable|string|max:255';
      $rules['children.*.last_name'] = 'nullable|string|max:255';
      $rules['children.*.gender'] = 'required|in:Male,Female';
      $rules['children.*.date_of_birth'] = 'required|date|before_or_equal:today';
      $rules['children.*.birth_order'] = 'nullable|integer|min:1|max:20';
      $rules['children.*.birth_weight'] = 'nullable|numeric|min:0.5|max:10';
      $rules['children.*.relationship'] = 'nullable|string|max:50';
      $rules['children.*.notes'] = 'nullable|string|max:255';
    }

    return $rules;
  }

  protected function messages()
  {
    return [
      'first_name.required' => 'First name is required.',
      'patient_gender.required' => 'Gender is required.',
      'patient_dob.required' => 'Date of birth is required.',
      'patient_phone.required' => 'Phone number is required.',
      'state_id.required' => 'State is required.',
      'lga_id.required' => 'LGA is required.',
      'children.*.first_name.required' => 'Each child needs a first name.',
      'children.*.gender.required' => 'Each child needs a gender.',
      'children.*.date_of_birth.required' => 'Each child needs a date of birth.',
    ];
  }

  public function mount()
  {
    $user = Auth::user();
    if (!$user || $user->role !== 'Data Officer') {
      abort(403, 'Unauthorized: Only Data Officers can access this page.');
    }

    $facility = Facility::with(['stateRelation', 'lgaRelation', 'wardRelation'])->find($user->facility_id);
    if (!$facility) {
      abort(403, 'Invalid facility assignment.');
    }

    $this->facility_id = $facility->id;
    $this->facility_name = $facility->name;
    $this->facility_state = $facility->stateRelation?->name ?? $facility->state ?? 'N/A';
    $this->facility_lga = $facility->lgaRelation?->name ?? $facility->lga ?? 'N/A';
    $this->facility_ward = $facility->wardRelation?->name ?? $facility->ward ?? 'N/A';

    $this->officer_name = $user->full_name ?? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
    $this->officer_role = $user->role ?? $user->designation ?? 'Staff';
    $this->officer_designation = $user->designation ?? 'N/A';
    $this->registration_date = now()->format('Y-m-d');

    $this->states = State::current()->orderBy('name')->get();
    $this->lgas = collect();
    $this->wards = collect();
    $this->existingLinkedChildren = collect();

    $this->prepareChildRowsForFreshRegistration();
  }

  public function startNewPatientRegistration()
  {
    $this->resetPatientSelection();
    $this->isNewPatient = true;
    $this->patient_registration_facility = 'New Patient';
    $this->prepareChildRowsForFreshRegistration();
    toastr()->info('You can proceed with direct immunization intake for a new patient.');
  }

  public function verifyPatient()
  {
    if (strlen((string) $this->din) !== 8 || !ctype_digit((string) $this->din)) {
      toastr()->error('Please enter a valid 8-digit DIN.');
      $this->isPatientVerified = false;
      $this->isNewPatient = false;
      $this->hasImmunizationRegistration = false;
      return;
    }

    $patient = Patient::where('din', $this->din)
      ->with(['facility', 'linkedChildren', 'immunizationRegistration.facility'])
      ->first();

    if (!$patient) {
      $this->startNewPatientRegistration();
      toastr()->warning('DIN not found. You can continue as a new immunization registration.');
      return;
    }

    $this->patient_id = $patient->id;
    $this->patient_registration_facility = $patient->immunizationRegistration?->facility?->name
      ?? $patient->facility?->name
      ?? 'N/A';

    if ($patient->immunizationRegistration) {
      $this->isPatientVerified = false;
      $this->isNewPatient = false;
      $this->hasImmunizationRegistration = true;
      $this->prefillPatientDetails($patient);
      $this->existingLinkedChildren = $patient->linkedChildren;
      toastr()->info('Patient already has an immunization entry point. Open the workspace to continue care.');
      return;
    }

    $this->isPatientVerified = true;
    $this->isNewPatient = false;
    $this->hasImmunizationRegistration = false;
    $this->prefillPatientDetails($patient);
    $this->existingLinkedChildren = $patient->linkedChildren;
    $this->children = [];

    if ($this->existingLinkedChildren->isEmpty()) {
      $this->addChildRow();
    }

    toastr()->info('Patient verified. Add child details and complete immunization registration.');
  }

  public function updatedPatientDob()
  {
    $this->patient_age = $this->patient_dob
      ? Carbon::parse($this->patient_dob)->age
      : null;
  }

  public function updatedStateId($value)
  {
    $this->lgas = $value
      ? Lga::current()->where('state_id', $value)->orderBy('name')->get()
      : collect();

    $this->lga_id = null;
    $this->ward_id = null;
    $this->wards = collect();
  }

  public function updatedLgaId($value)
  {
    $this->wards = $value
      ? Ward::current()->where('lga_id', $value)->orderBy('name')->get()
      : collect();

    $this->ward_id = null;
  }

  public function addChildRow()
  {
    $this->children[] = $this->defaultChildRow(count($this->existingLinkedChildren) + count($this->children) + 1);
  }

  public function removeChildRow($index)
  {
    unset($this->children[$index]);
    $this->children = array_values($this->children);

    if (empty($this->children) && count($this->existingLinkedChildren) === 0) {
      $this->children[] = $this->defaultChildRow(1);
    }
  }

  public function store()
  {
    DB::beginTransaction();

    try {
      $this->validate();
      $this->ensureChildPresence();

      if ($this->patient_id) {
        $patient = Patient::findOrFail($this->patient_id);
      } else {
        $patient = Patient::create([
          'din' => Patient::generateDIN(),
          'first_name' => $this->first_name,
          'middle_name' => $this->middle_name,
          'last_name' => $this->resolvePatientLastName(),
          'gender' => $this->patient_gender,
          'date_of_birth' => $this->patient_dob,
          'phone' => $this->patient_phone,
          'email' => $this->patient_email,
          'state_id' => $this->state_id,
          'lga_id' => $this->lga_id,
          'ward_id' => $this->ward_id,
          'facility_id' => $this->facility_id,
          'registration_date' => $this->registration_date,
          'is_active' => true,
        ]);

        $this->patient_id = $patient->id;
      }

      app(PatientPortalAccountService::class)->ensureForPatient($patient);

      if (ImmunizationRegistration::where('patient_id', $patient->id)->exists()) {
        throw ValidationException::withMessages([
          'patient_id' => 'Patient already has an immunization registration. Use the workspace for additional child health activity.',
        ]);
      }

      ImmunizationRegistration::create([
        'patient_id' => $patient->id,
        'facility_id' => $this->facility_id,
        'registration_date' => $this->registration_date,
        'follow_up_phone' => $this->follow_up_phone ?: $patient->phone,
        'follow_up_address' => $this->follow_up_address,
        'notes' => $this->notes,
        'officer_name' => $this->officer_name,
        'officer_role' => $this->officer_role,
        'officer_designation' => $this->officer_designation,
      ]);

      $this->createLinkedChildren($patient);
      DB::commit();

      toastr()->info("Immunization entry point created successfully. DIN: {$patient->din}");

      return redirect()->route('workspace-dashboard', ['patientId' => $patient->id]);
    } catch (ValidationException $e) {
      DB::rollBack();
      $errors = $e->validator->errors()->all();
      foreach ($errors as $error) {
        toastr()->error($error);
      }
      throw $e;
    } catch (Exception $e) {
      DB::rollBack();
      toastr()->error('An error occurred while creating the immunization registration.');
      throw $e;
    }
  }

  public function openWorkspace()
  {
    if (!$this->patient_id) {
      return;
    }

    return redirect()->route('workspace-dashboard', ['patientId' => $this->patient_id]);
  }

  private function prefillPatientDetails(Patient $patient): void
  {
    $this->first_name = $patient->first_name;
    $this->middle_name = $patient->middle_name;
    $this->last_name = $patient->last_name;
    $this->patient_gender = $patient->gender;
    $this->patient_dob = $patient->date_of_birth?->format('Y-m-d');
    $this->patient_age = $patient->age;
    $this->patient_phone = $patient->phone;
    $this->patient_email = $patient->email;
    $this->state_id = $patient->state_id;
    $this->lga_id = $patient->lga_id;
    $this->ward_id = $patient->ward_id;
    $this->follow_up_phone = $patient->phone;

    $this->lgas = $this->state_id
      ? Lga::current()->where('state_id', $this->state_id)->orderBy('name')->get()
      : collect();

    $this->wards = $this->lga_id
      ? Ward::current()->where('lga_id', $this->lga_id)->orderBy('name')->get()
      : collect();
  }

  private function prepareChildRowsForFreshRegistration(): void
  {
    $this->existingLinkedChildren = collect();
    $this->children = [$this->defaultChildRow(1)];
  }

  private function resetPatientSelection(): void
  {
    $this->reset([
      'patient_id',
      'first_name',
      'middle_name',
      'last_name',
      'patient_gender',
      'patient_dob',
      'patient_age',
      'patient_phone',
      'patient_email',
      'state_id',
      'lga_id',
      'ward_id',
      'follow_up_phone',
      'follow_up_address',
      'notes',
    ]);

    $this->isPatientVerified = false;
    $this->hasImmunizationRegistration = false;
    $this->lgas = collect();
    $this->wards = collect();
  }

  private function ensureChildPresence(): void
  {
    $newChildCount = count($this->children);
    $existingChildCount = $this->patient_id
      ? Patient::find($this->patient_id)?->linkedChildren()->count() ?? 0
      : 0;

    if (($newChildCount + $existingChildCount) === 0) {
      throw ValidationException::withMessages([
        'children' => 'Add at least one child before saving this immunization entry point.',
      ]);
    }
  }

  private function createLinkedChildren(Patient $patient): void
  {
    $userId = Auth::id();
    $existingBirthOrder = (int) $patient->linkedChildren()->max('birth_order');

    foreach ($this->children as $index => $child) {
      $firstName = trim((string) ($child['first_name'] ?? ''));
      if ($firstName === '') {
        continue;
      }

      $lastName = trim((string) ($child['last_name'] ?? '')) ?: $patient->last_name;
      $dateOfBirth = $child['date_of_birth'];
      $birthOrder = $child['birth_order'] ?: ($existingBirthOrder + $index + 1);

      $existingChild = LinkedChild::query()
        ->where('parent_patient_id', $patient->id)
        ->where('first_name', $firstName)
        ->where('last_name', $lastName)
        ->whereDate('date_of_birth', $dateOfBirth)
        ->first();

      $payload = [
        'parent_patient_id' => $patient->id,
        'first_name' => $firstName,
        'middle_name' => $child['middle_name'] ?: null,
        'last_name' => $lastName,
        'gender' => $child['gender'],
        'date_of_birth' => $dateOfBirth,
        'relationship' => $child['relationship'] ?: 'Mother',
        'birth_weight' => $child['birth_weight'] ?: null,
        'birth_order' => $birthOrder,
        'facility_id' => $this->facility_id,
        'is_active' => true,
        'updated_by' => $userId,
        'notes' => $child['notes'] ?: 'Linked from immunization registration',
      ];

      if ($existingChild) {
        $existingChild->update($payload);
        continue;
      }

      LinkedChild::create(array_merge($payload, [
        'linked_child_id' => LinkedChild::generateLinkedChildID(),
        'created_by' => $userId,
      ]));
    }
  }

  private function defaultChildRow(int $birthOrder): array
  {
    return [
      'first_name' => '',
      'middle_name' => '',
      'last_name' => '',
      'gender' => '',
      'date_of_birth' => '',
      'birth_order' => $birthOrder,
      'birth_weight' => '',
      'relationship' => 'Mother',
      'notes' => '',
    ];
  }

  private function resolvePatientLastName(): string
  {
    $lastName = trim((string) $this->last_name);

    return $lastName !== '' ? $lastName : 'Not Provided';
  }

  public function render()
  {
    $recentRegistrations = ImmunizationRegistration::with(['patient'])
      ->where('facility_id', $this->facility_id)
      ->latest('registration_date')
      ->latest('id')
      ->limit(20)
      ->get();

    return view('livewire.registers.immunization-register', [
      'recentRegistrations' => $recentRegistrations,
    ]);
  }

  public function placeholder()
  {
    return view('placeholder');
  }
}
