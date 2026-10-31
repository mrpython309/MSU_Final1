<div class="card mb-6 st-activity-card">
    <div class="card-header">
        <h5 class="card-title m-0">Status Change Logs</h5>
    </div>
    <div class="card-body pt-0">
        @forelse($supportTicket->activities as $activity)
            @php
                $userName = $activity->user->name ?? 'System';
                $initial = strtoupper(substr($userName, 0, 1));
                $event = $activity->event;
                $eventLabel = ucfirst(str_replace('_', ' ', $event));
                $eventIcons = [
                    'status_changed' => 'bx-transfer-alt',
                    'commented' => 'bx-message-rounded-dots',
                    'updated' => 'bx-edit',
                    'assigned' => 'bx-user-plus',
                    'exited' => 'bx-log-out',
                    'created' => 'bx-plus',
                    'accepted' => 'bx-check',
                ];
                $eventIcon = $eventIcons[$event] ?? 'bx-time-five';
            @endphp
            <div class="st-activity">
                <div class="st-activity__avatar" aria-hidden="true">{{ $initial }}</div>
                <div class="st-activity__body">
                    <div class="st-activity__top">
                        <span class="st-activity__name">{{ $userName }}</span>
                        <time class="st-activity__time" datetime="{{ $activity->created_at?->toIso8601String() }}" title="{{ $activity->created_at?->format('d M Y, h:i A') }}">
                            {{ $activity->created_at?->diffForHumans() }}
                        </time>
                    </div>

                    <div class="st-activity__event">
                        <i class="bx {{ $eventIcon }}"></i>
                        {{ $eventLabel }}
                    </div>

                    @if($event === 'status_changed')
                        @php
                            $oldStatus = strtolower($activity->meta['old'] ?? '');
                            $newStatus = strtolower($activity->meta['new'] ?? '');
                            [$oldName, $oldClass] = \App\Models\SupportTicketProgressStatus::presentation($oldStatus);
                            [$newName, $newClass] = \App\Models\SupportTicketProgressStatus::presentation($newStatus);
                        @endphp
                        <div class="st-activity__change">
                            <span class="badge {{ $oldClass }}">{{ $oldName }}</span>
                            <i class="bx bx-right-arrow-alt"></i>
                            <span class="badge {{ $newClass }}">{{ $newName }}</span>
                        </div>
                        @if(!empty($activity->meta['note']))
                            <div class="st-activity__note">{{ $activity->meta['note'] }}</div>
                        @endif
                    @endif

                    @if($event === 'commented' && !empty($activity->meta['message']))
                        <div class="st-activity__note">{{ $activity->meta['message'] }}</div>
                    @endif

                    @if($event === 'updated')
                        <ul class="st-activity__changes">
                            @foreach($activity->meta['changes'] ?? [] as $field => $change)
                                <li>
                                    <strong>{{ ucfirst(str_replace('_', ' ', $field)) }}</strong>
                                    <span>{{ $change['old'] ?? '-' }}</span>
                                    <i class="bx bx-right-arrow-alt"></i>
                                    <span>{{ $change['new'] ?? '-' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if($event === 'assigned')
                        <div class="st-activity__note">Collaborator: {{ $activity->meta['assigned_to'] ?? '-' }}</div>
                    @endif

                    @if($event === 'exited' && !empty($activity->meta['reason']))
                        <div class="st-activity__note">Reason: {{ $activity->meta['reason'] }}</div>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-muted mb-0 py-3">No logs found</p>
        @endforelse
    </div>
</div>

<style>
    .st-activity-card .st-activity {
        display: flex;
        gap: 0.85rem;
        padding: 0.9rem 0;
        border-bottom: 1px solid #eef1f4;
    }
    .st-activity-card .st-activity:last-child {
        border-bottom: 0;
        padding-bottom: 0.25rem;
    }
    .st-activity__avatar {
        flex: 0 0 36px;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #eef2ff;
        color: #3b4cca;
        font-weight: 700;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .st-activity__body {
        flex: 1;
        min-width: 0;
    }
    .st-activity__top {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 0.75rem;
    }
    .st-activity__name {
        font-weight: 600;
        color: #2f3349;
    }
    .st-activity__time {
        flex-shrink: 0;
        font-size: 0.75rem;
        color: #8a8d93;
    }
    .st-activity__event {
        display: flex;
        align-items: center;
        gap: 0.3rem;
        margin-top: 0.15rem;
        font-size: 0.8rem;
        color: #6d6b77;
    }
    .st-activity__change {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.35rem;
        margin-top: 0.55rem;
    }
    .st-activity__change .bx {
        color: #a1a1aa;
    }
    .st-activity__note {
        margin-top: 0.45rem;
        padding: 0.45rem 0.65rem;
        border-radius: 8px;
        background: #f7f7f9;
        color: #5d596c;
        font-size: 0.8125rem;
        line-height: 1.45;
        overflow-wrap: anywhere;
    }
    .st-activity__changes {
        margin: 0.45rem 0 0;
        padding-left: 1rem;
        font-size: 0.8125rem;
        color: #5d596c;
    }
    .st-activity__changes li + li {
        margin-top: 0.25rem;
    }
</style>
