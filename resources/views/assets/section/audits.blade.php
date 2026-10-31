<div class="card card-action mb-6">
    <div class="card-header align-items-center py-6">
        <h5 class="card-action-title mb-0 me-3">Audit History</h5>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table border-top table-border-bottom-0">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th class="text-truncate">Audit Date & Time</th>
                        <th class="text-truncate">Audited By</th>
                        <th class="text-truncate">Status</th>
                        <th class="text-truncate">Location Status</th>
                        <th>Remarks / Observation</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $auditsList = $asset->audits ?? collect();
                    @endphp
                    @forelse ($auditsList as $index => $audit)
                        <tr>
                            <td>
                                <span class="badge badge-center rounded-pill bg-label-secondary font-size-11">
                                    {{ $auditsList->count() - $index }}
                                </span>
                            </td>

                            <td class="text-truncate">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bx bx-calendar-event text-primary"></i>
                                    <span class="fw-medium text-heading">{{ $audit->formattedDate() }}</span>
                                </div>
                            </td>

                            <td class="text-truncate">
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-xs me-2">
                                        <span class="avatar-initial rounded-circle bg-label-primary">
                                            <i class="bx bx-user icon-12px"></i>
                                        </span>
                                    </div>
                                    <span class="fw-medium">{{ $audit->auditorName() }}</span>
                                </div>
                            </td>

                            <td>
                                {!! $audit->auditStatusBadge() !!}
                            </td>

                            <td>
                                <div class="d-flex flex-column">
                                    @if($audit->location_status)
                                        <span class="fw-medium text-dark">{{ ucfirst(str_replace('_', ' ', $audit->location_status)) }}</span>
                                    @endif
                                    @if($audit->location)
                                        <small class="text-muted"><i class="bx bx-map-pin me-1"></i>{{ $audit->location->location_name }}</small>
                                    @elseif($audit->branch)
                                        <small class="text-muted"><i class="bx bx-buildings me-1"></i>{{ $audit->branch->name }}</small>
                                    @else
                                        <small class="text-muted">--</small>
                                    @endif
                                </div>
                            </td>

                            <td>
                                @if(!empty($audit->remarks))
                                    <span class="text-wrap text-muted" style="max-width: 320px; display: inline-block;">
                                        {{ $audit->remarks }}
                                    </span>
                                @else
                                    <span class="text-muted">--</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-6 text-muted">
                                <div class="my-4">
                                    <i class="bx bx-check-shield text-secondary mb-2" style="font-size: 2.8rem;"></i>
                                    <h6 class="text-muted mb-1">No Audit History Found</h6>
                                    <p class="small text-muted mb-0">No physical audits have been performed for this asset yet.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
