@php
    $oldPermissionIds = $errors->any() ? null : old('permission_ids');
    $oldPermissionIds = is_array($oldPermissionIds) ? array_map('strval', $oldPermissionIds) : null;
@endphp

<x-app-layout>
    <x-slot name="breadcrumbParent">{{ __('Roles & Permissions') }}</x-slot>
    <x-slot name="breadcrumbParentUrl">{{ route('roles.index') }}</x-slot>
    <x-slot name="breadcrumb">{{ __('Edit :role', ['role' => $role->name]) }}</x-slot>

    <x-slot name="header">
        <div>
            <h1 class="h3 mb-1">{{ __('Edit :role Permissions', ['role' => $role->name]) }}</h1>
            <p class="text-body-secondary mb-0">{{ __('Choose permissions within the approved boundary for this fixed role.') }}</p>
        </div>
    </x-slot>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <div class="fw-semibold mb-1">{{ __('The mapping was not updated.') }}</div>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="role-permission-form" method="POST" action="{{ route('roles.permissions.update', $role) }}">
        @csrf
        @method('put')
        <input type="hidden" name="mapping_fingerprint" value="{{ $fingerprint }}">

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                @forelse ($permissionsByModule as $module => $permissions)
                    <fieldset class="p-3 p-lg-4 @unless($loop->last) border-bottom @endunless">
                        <legend class="h5 mb-3">{{ $module }}</legend>
                        <div class="row g-3">
                            @foreach ($permissions as $permission)
                                @php
                                    $essential = in_array($permission->code, $essentialCodes, true);
                                    $checked = $essential || ($oldPermissionIds !== null
                                        ? in_array((string) $permission->id, $oldPermissionIds, true)
                                        : in_array($permission->code, $selectedCodes, true));
                                @endphp
                                <div class="col-md-6 col-xl-4">
                                    @if ($essential)
                                        <input type="hidden" name="permission_ids[]" value="{{ $permission->id }}">
                                    @endif
                                    <div class="form-check">
                                        <input
                                            id="permission-{{ $permission->id }}"
                                            name="permission_ids[]"
                                            class="form-check-input permission-checkbox"
                                            type="checkbox"
                                            value="{{ $permission->id }}"
                                            data-code="{{ $permission->code }}"
                                            data-label="{{ $permission->name }}"
                                            data-originally-checked="{{ in_array($permission->code, $selectedCodes, true) ? 'true' : 'false' }}"
                                            @checked($checked)
                                            @disabled($essential)
                                        >
                                        <label class="form-check-label" for="permission-{{ $permission->id }}">
                                            <span class="fw-medium">{{ $permission->name }}</span>
                                            @if ($essential)
                                                <span class="badge text-bg-secondary ms-1">{{ __('Essential') }}</span>
                                            @endif
                                            <span class="d-block"><code class="small">{{ $permission->code }}</code></span>
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </fieldset>
                @empty
                    <p class="text-center text-body-secondary p-4 mb-0">{{ __('No configurable permissions are available.') }}</p>
                @endforelse

                <div class="border-top p-3 p-lg-4 d-flex flex-column flex-sm-row gap-2">
                    <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#confirmMappingModal">
                        <i class="bi bi-check2-circle me-1" aria-hidden="true"></i>{{ __('Review Changes') }}
                    </button>
                    <a class="btn btn-link" href="{{ route('roles.show', $role) }}">{{ __('Cancel') }}</a>
                </div>
            </div>
        </div>

        <div class="modal fade" id="confirmMappingModal" tabindex="-1" aria-labelledby="confirmMappingLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="confirmMappingLabel">{{ __('Confirm Permission Changes') }}</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <p>{{ __('Review the changes for :role before saving.', ['role' => $role->name]) }}</p>
                        <div class="mb-3">
                            <h3 class="h6 text-success">{{ __('Permissions Granted') }}</h3>
                            <ul id="granted-permissions" class="small mb-0"></ul>
                        </div>
                        <div>
                            <h3 class="h6 text-danger">{{ __('Permissions Revoked') }}</h3>
                            <ul id="revoked-permissions" class="small mb-0"></ul>
                        </div>
                        <p id="no-permission-changes" class="text-body-secondary small mb-0 d-none">{{ __('No permission changes are selected.') }}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Continue Editing') }}</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1" aria-hidden="true"></i>{{ __('Save Mapping') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script>
        (() => {
            const modal = document.getElementById('confirmMappingModal');
            const grantedList = document.getElementById('granted-permissions');
            const revokedList = document.getElementById('revoked-permissions');
            const emptyMessage = document.getElementById('no-permission-changes');

            const renderList = (list, items) => {
                list.replaceChildren(...items.map((item) => {
                    const element = document.createElement('li');
                    element.textContent = `${item.label} (${item.code})`;
                    return element;
                }));
                list.parentElement.classList.toggle('d-none', items.length === 0);
            };

            modal.addEventListener('show.bs.modal', () => {
                const granted = [];
                const revoked = [];

                document.querySelectorAll('.permission-checkbox').forEach((checkbox) => {
                    const originallyChecked = checkbox.dataset.originallyChecked === 'true';
                    const item = { code: checkbox.dataset.code, label: checkbox.dataset.label };

                    if (checkbox.checked && ! originallyChecked) {
                        granted.push(item);
                    } else if (! checkbox.checked && originallyChecked) {
                        revoked.push(item);
                    }
                });

                renderList(grantedList, granted);
                renderList(revokedList, revoked);
                emptyMessage.classList.toggle('d-none', granted.length + revoked.length > 0);
            });
        })();
    </script>
</x-app-layout>
