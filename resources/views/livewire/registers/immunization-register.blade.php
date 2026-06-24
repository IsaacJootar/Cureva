<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row g-4">
        <div class="col-12">
            <div class="card border-0 bg-label-primary">
                <div class="card-body d-flex flex-column flex-lg-row justify-content-between gap-3">
                    <div>
                        <h4 class="mb-1">Immunization Register</h4>
                        <p class="mb-0">
                            This is the 4th entry point. Register the mother first, then link the child or children
                            directly so all immunization records still stay tied to a real child profile.
                        </p>
                    </div>
                    <div class="text-lg-end">
                        <div class="small text-muted">Facility</div>
                        <div class="fw-semibold">{{ $facility_name }}</div>
                        <div class="small text-muted">{{ $facility_state }}, {{ $facility_lga }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">DIN Verification</h5>
                </div>
                <div class="card-body">
                    <label class="form-label">Patient DIN</label>
                    <div class="input-group">
                        <input type="text" class="form-control" wire:model="din" maxlength="8"
                            placeholder="Enter 8-digit DIN">
                        <button class="btn btn-primary" type="button" wire:click="verifyPatient"
                            wire:loading.attr="disabled" wire:target="verifyPatient">
                            <span wire:loading.remove wire:target="verifyPatient">Verify</span>
                            <span wire:loading wire:target="verifyPatient">
                                <span class="spinner-border spinner-border-sm me-1" role="status"
                                    aria-hidden="true"></span>Verifying...
                            </span>
                        </button>
                    </div>
                    @error('din')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror

                    <button class="btn btn-dark fw-bold mt-3" type="button"
                        wire:click="startNewPatientRegistration">
                        Register New Patient
                    </button>

                    <hr class="my-4">

                    <div class="small text-muted mb-2">Current status</div>

                    @if ($hasImmunizationRegistration)
                        <div class="alert alert-info mb-3">
                            This patient already has an immunization entry point at
                            <strong>{{ $patient_registration_facility }}</strong>.
                        </div>
                    @elseif($isPatientVerified)
                        <div class="alert alert-success mb-3">
                            Patient verified. You can complete immunization registration and link children below.
                        </div>
                    @elseif($isNewPatient)
                        <div class="alert alert-warning mb-3">
                            New patient flow is active. A fresh DIN will be generated when you save.
                        </div>
                    @else
                        <div class="alert alert-secondary mb-3">
                            Verify an existing DIN or continue with a new patient registration.
                        </div>
                    @endif

                    @if ($patient_id)
                        <div class="border rounded p-3 bg-label-light">
                            <div class="fw-semibold mb-2">
                                {{ trim(($first_name ?? '') . ' ' . ($middle_name ?? '') . ' ' . ($last_name ?? '')) }}
                            </div>
                            <div class="small text-muted">Phone: {{ $patient_phone ?: 'N/A' }}</div>
                            <div class="small text-muted">Gender: {{ $patient_gender ?: 'N/A' }}</div>
                            <div class="small text-muted">DOB:
                                {{ $patient_dob ? \Carbon\Carbon::parse($patient_dob)->format('d M Y') : 'N/A' }}
                            </div>
                            <div class="small text-muted">Previously registered at:
                                {{ $patient_registration_facility ?: 'N/A' }}
                            </div>
                        </div>
                    @endif

                    @if ($hasImmunizationRegistration)
                        <button class="btn btn-success mt-3" type="button" wire:click="openWorkspace">
                            Open Patient Workspace
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Recent Immunization Registrations</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>DIN</th>
                                <th>Patient</th>
                                <th>Children</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentRegistrations as $registration)
                                <tr>
                                    <td>{{ $registration->patient?->din ?? 'N/A' }}</td>
                                    <td>{{ $registration->patient?->full_name ?? 'N/A' }}</td>
                                    <td>{{ $registration->patient?->linkedChildren()->count() ?? 0 }}</td>
                                    <td>{{ $registration->registration_date?->format('d M Y') ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No immunization registrations
                                        yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if (($isPatientVerified || $isNewPatient) && !$hasImmunizationRegistration)
            <div class="col-12">
                <form wire:submit="store">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Immunization Entry Form</h5>
                            <span class="badge bg-label-info">Officer: {{ $officer_name }}</span>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                @if (!$patient_id)
                                    <div class="col-md-4">
                                        <label class="form-label">First Name</label>
                                        <input type="text" class="form-control" wire:model="first_name">
                                        @error('first_name')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Middle Name</label>
                                        <input type="text" class="form-control" wire:model="middle_name">
                                        @error('middle_name')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Last Name <span class="text-muted">(Optional)</span></label>
                                        <input type="text" class="form-control" wire:model="last_name"
                                            placeholder="Leave blank if not provided">
                                        @error('last_name')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Gender</label>
                                        <select class="form-select" wire:model="patient_gender">
                                            <option value="">Select</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                        </select>
                                        @error('patient_gender')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control" wire:model="patient_dob">
                                        @error('patient_dob')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Phone</label>
                                        <input type="text" class="form-control" wire:model="patient_phone">
                                        @error('patient_phone')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" wire:model="patient_email">
                                        @error('patient_email')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">State</label>
                                        <select class="form-select" wire:model.live="state_id">
                                            <option value="">Select state</option>
                                            @foreach ($states as $state)
                                                <option value="{{ $state->id }}">{{ $state->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('state_id')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">LGA</label>
                                        <select class="form-select" wire:model.live="lga_id">
                                            <option value="">Select LGA</option>
                                            @foreach ($lgas as $lga)
                                                <option value="{{ $lga->id }}">{{ $lga->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('lga_id')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Ward</label>
                                        <select class="form-select" wire:model="ward_id">
                                            <option value="">Select ward</option>
                                            @foreach ($wards as $ward)
                                                <option value="{{ $ward->id }}">{{ $ward->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('ward_id')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @else
                                    <div class="col-12">
                                        <div class="alert alert-primary mb-0">
                                            Saving this entry will use the verified patient record above. Core patient
                                            demographics will stay unchanged.
                                        </div>
                                    </div>
                                @endif

                                <div class="col-md-4">
                                    <label class="form-label">Registration Date</label>
                                    <input type="date" class="form-control" wire:model="registration_date">
                                    @error('registration_date')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Follow-up Phone</label>
                                    <input type="text" class="form-control" wire:model="follow_up_phone">
                                    @error('follow_up_phone')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Officer Role</label>
                                    <input type="text" class="form-control" value="{{ $officer_role }}" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Follow-up Address</label>
                                    <textarea class="form-control" rows="2" wire:model="follow_up_address"></textarea>
                                    @error('follow_up_address')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Notes</label>
                                    <textarea class="form-control" rows="2" wire:model="notes"
                                        placeholder="Optional immunization intake notes"></textarea>
                                    @error('notes')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h5 class="mb-1">Linked Child Setup</h5>
                                    <p class="text-muted mb-0">Every immunization record must be tied to a linked child.
                                    </p>
                                </div>
                                <button type="button" class="btn btn-primary" wire:click="addChildRow">
                                    Add Child
                                </button>
                            </div>

                            @if (count($existingLinkedChildren) > 0)
                                <div class="alert alert-success">
                                    <div class="fw-semibold mb-2">Existing linked children</div>
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach ($existingLinkedChildren as $child)
                                            <span class="badge bg-label-success">
                                                {{ $child->first_name }} {{ $child->last_name }}
                                                ({{ $child->linked_child_id }})
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @error('children')
                                <div class="alert alert-danger">{{ $message }}</div>
                            @enderror

                            <div class="row g-3">
                                @foreach ($children as $index => $child)
                                    <div class="col-12">
                                        <div class="border rounded p-3">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h6 class="mb-0">Child {{ $index + 1 }}</h6>
                                                <button type="button" class="btn btn-sm btn-label-danger"
                                                    wire:click="removeChildRow({{ $index }})">
                                                    Remove
                                                </button>
                                            </div>
                                            <div class="row g-3">
                                                <div class="col-md-3">
                                                    <label class="form-label">First Name</label>
                                                    <input type="text" class="form-control"
                                                        wire:model="children.{{ $index }}.first_name">
                                                    @error("children.$index.first_name")
                                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Middle Name</label>
                                                    <input type="text" class="form-control"
                                                        wire:model="children.{{ $index }}.middle_name">
                                                    @error("children.$index.middle_name")
                                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Last Name</label>
                                                    <input type="text" class="form-control"
                                                        wire:model="children.{{ $index }}.last_name"
                                                        placeholder="Defaults to mother surname">
                                                    @error("children.$index.last_name")
                                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Gender</label>
                                                    <select class="form-select"
                                                        wire:model="children.{{ $index }}.gender">
                                                        <option value="">Select</option>
                                                        <option value="Male">Male</option>
                                                        <option value="Female">Female</option>
                                                    </select>
                                                    @error("children.$index.gender")
                                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Date of Birth</label>
                                                    <input type="date" class="form-control"
                                                        wire:model="children.{{ $index }}.date_of_birth">
                                                    @error("children.$index.date_of_birth")
                                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">Birth Order</label>
                                                    <input type="number" min="1" class="form-control"
                                                        wire:model="children.{{ $index }}.birth_order">
                                                    @error("children.$index.birth_order")
                                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">Birth Weight (kg)</label>
                                                    <input type="number" step="0.01" class="form-control"
                                                        wire:model="children.{{ $index }}.birth_weight">
                                                    @error("children.$index.birth_weight")
                                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">Relationship</label>
                                                    <input type="text" class="form-control"
                                                        wire:model="children.{{ $index }}.relationship">
                                                    @error("children.$index.relationship")
                                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Notes</label>
                                                    <input type="text" class="form-control"
                                                        wire:model="children.{{ $index }}.notes">
                                                    @error("children.$index.notes")
                                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-end">
                            <button type="submit" class="btn btn-success">
                                Save Immunization Entry Point
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        @endif
    </div>
</div>
