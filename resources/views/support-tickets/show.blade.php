@extends('layouts/supportTicketLayout')

@section('title', 'View Support Ticket')

@section('page-style')
<style>
  .ticket-thread {
    display: flex;
    flex-direction: column;
    gap: 0;
  }
  .ticket-post {
    padding: 1rem 0;
    border-bottom: 1px solid #eef1f4;
  }
  .ticket-post:first-child {
    padding-top: 0;
  }
  .ticket-post:last-child {
    border-bottom: 0;
    padding-bottom: 0;
  }
  .ticket-post__head {
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }
  .ticket-post__avatar {
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
  .ticket-post__who {
    min-width: 0;
    display: flex;
    flex-direction: column;
  }
  .ticket-post__name {
    font-weight: 600;
    color: #2f3349;
    line-height: 1.2;
  }
  .ticket-post__time {
    margin-left: auto;
    flex-shrink: 0;
    font-size: 0.75rem;
    color: #8a8d93;
  }
  .ticket-post__subject {
    margin: 0.75rem 0 0.35rem;
  }
  .ticket-post__text {
    margin: 0.65rem 0 0;
    color: #444050;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
  }
  .ticket-post__text.is-rejected { color: #d32f2f; }
  .ticket-post__text.is-exited { color: #b78103; }
  .ticket-post__images {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.75rem;
  }
  .ticket-post__images a {
    display: block;
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid #e6e6eb;
  }
  .ticket-post__images img {
    width: 120px;
    height: 90px;
    object-fit: cover;
    display: block;
  }
  .ticket-post__files {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.75rem;
  }
  .ticket-file {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    max-width: 100%;
    padding: 0.4rem 0.7rem;
    border-radius: 8px;
    background: #f7f7f9;
    color: #444050;
    font-size: 0.8125rem;
    text-decoration: none;
  }
  .ticket-file:hover { color: #3b4cca; }
  .ticket-voice {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-top: 0.75rem;
    padding: 0.55rem 0.75rem;
    border-radius: 10px;
    background: #f7f7f9;
  }
  .ticket-voice__icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #eef2ff;
    color: #3b4cca;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .ticket-voice audio {
    width: min(280px, 100%);
    height: 32px;
  }
  .ticket-voice__meta {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    min-width: 0;
  }
  .ticket-voice__label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #5d596c;
  }

  #recordingControls button {
    min-width: 120px;
}

#audioPreview {
    max-height: 80px;
}

#recordingStatus {
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}
</style>
@endsection

@section('content')

@php $isAssignedTicket=$supportTicket->isAssignedTo(auth()->user()); @endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="layout-menu-toggle navbar-nav align-items-center d-xl-none">
            <a class="nav-item nav-link px-0" href="javascript:void(0)">
                <i class="icon-base bx bx-menu icon-md"></i>
            </a>
        </div>
        <h4 class="mb-0">Support Ticket {{ $supportTicket->displayTicketNumber() }}</h4>
    </div>

    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('support-ticket.show', $supportTicket) }}?export_format=excel" class="btn btn-sm btn-success">Excel</a>
        <a href="{{ route('support-ticket.show', $supportTicket) }}?export_format=pdf" class="btn btn-sm btn-danger">PDF</a>
        <a href="{{ route('support-ticket.index') }}" class="btn btn-secondary">
            <i class="bx bx-arrow-back me-1"></i> Back to List
        </a>
        @include('support-tickets.partials.header-actions')
    </div>
</div>


<div class="row">
  {{-- LEFT --}}
    <div class="col-md-8">
 {{-- Ticket --}}
        <div class="card mb-6">

            <div class="card-body">
                <div class="ticket-thread">
                    <article class="ticket-post">
                        <div class="ticket-post__head">
                            <span class="ticket-post__avatar">{{ strtoupper(substr($supportTicket->creator->name ?? 'S', 0, 1)) }}</span>
                            <div class="ticket-post__who">
                                <span class="ticket-post__name">{{ $supportTicket->creator->name ?? 'System' }}</span>
                            </div>
                            <time class="ticket-post__time" datetime="{{ $supportTicket->created_at?->toIso8601String() }}" title="{{ $supportTicket->created_at?->format('d M Y, h:i A') }}">
                                {{ $supportTicket->created_at?->diffForHumans() }}
                            </time>
                        </div>

                        <h5 class="card-title text-primary ticket-post__subject">{{ $supportTicket->subject }}</h5>
                        <p class="ticket-post__text">{{ $supportTicket->description }}</p>

                        @if($supportTicket->filename)
                            @if($supportTicket->icon == 'images')
                                <div class="ticket-post__images">
                                    <a href="{{ $supportTicket->doc_url }}" data-lightbox="ticket-{{ $supportTicket->id }}">
                                        <img src="{{ $supportTicket->doc_url }}" alt="Ticket attachment">
                                    </a>
                                </div>
                            @else
                                <div class="ticket-post__files">
                                    <a class="ticket-file" href="{{ $supportTicket->doc_url }}" download>
                                        <i class="bx bx-file"></i> {{ $supportTicket->filename }}
                                    </a>
                                </div>
                            @endif
                        @endif
                    </article>

                    @foreach ($supportTicket->commands as $stCommand)
                        @php
                            $replyText = trim((string) ($stCommand->ticket_response ?? ''));
                            $replyTone = str_starts_with($replyText, 'Rejected:') ? 'is-rejected' : (str_starts_with($replyText, 'Exited:') ? 'is-exited' : '');
                            $replyName = $stCommand->user->name ?? 'System';
                            $voiceSeconds = (int) ($stCommand->recording_duration ?? 0);
                        @endphp
                        <article class="ticket-post">
                            <div class="ticket-post__head">
                                <span class="ticket-post__avatar">{{ strtoupper(substr($replyName, 0, 1)) }}</span>
                                <div class="ticket-post__who">
                                    <span class="ticket-post__name">{{ $replyName }}</span>
                                </div>
                                <time class="ticket-post__time" datetime="{{ $stCommand->created_at?->toIso8601String() }}" title="{{ $stCommand->created_at?->format('d M Y, h:i A') }}">
                                    {{ $stCommand->created_at?->diffForHumans() }}
                                </time>
                            </div>

                            @if($replyText !== '')
                                <p class="ticket-post__text {{ $replyTone }}">{{ $replyText }}</p>
                            @endif

                            @if($stCommand->attachment_filenames !== [])
                                @include('support-tickets.partials.reply-files')
                            @endif

                            @if($stCommand->voice_note)
                                <div class="ticket-voice">
                                    <span class="ticket-voice__icon"><i class="bx bx-microphone"></i></span>
                                    <div class="ticket-voice__meta">
                                        <span class="ticket-voice__label">
                                            Voice note
                                            @if($voiceSeconds > 0)
                                                · {{ floor($voiceSeconds / 60) }}:{{ str_pad($voiceSeconds % 60, 2, '0', STR_PAD_LEFT) }}
                                            @endif
                                        </span>
                                        <audio controls preload="none">
                                            <source src="{{ $stCommand->voicenote_url }}" type="audio/webm">
                                            <source src="{{ $stCommand->voicenote_url }}" type="audio/mpeg">
                                            <source src="{{ $stCommand->voicenote_url }}" type="audio/wav">
                                            <source src="{{ $stCommand->voicenote_url }}" type="audio/ogg">
                                        </audio>
                                    </div>
                                    <a class="ticket-file" href="{{ $stCommand->voicenote_url }}" download title="Download voice note">
                                        <i class="bx bx-download"></i>
                                    </a>
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
            </div>




        </div>


        @if(!in_array($supportTicket->status,['closed']))

         {{-- Reply --}}
        <div class="card ">
        <div class="card-header">
          <h5 class="card-tile mb-0">Reply</h5>
        </div>
        <div class="card-body"> 
            <form id="replyForm" method="POST" action="{{ route('support-ticket.replyticket', $supportTicket->id) }}" enctype="multipart/form-data">
              <div class="mb-3">
                <div class="col-12 form-control p-0 mb-5">
                    <textarea name="ticket_response" id="ticket_response" class="form-control" rows="3"></textarea>
                </div>
              </div>

              <div class="col-12 mb-5 mt-3">
                <label class="form-label" for="reply_filenames">Attach Files (Optional)</label>
                <input class="form-control @error('filenames') is-invalid @enderror @error('filenames.*') is-invalid @enderror"
                       type="file"
                       id="reply_filenames"
                       name="filenames[]"
                       accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,image/*"
                       multiple>
                <small class="text-muted">Up to 2 files, max 20 MB each. Images are compressed on upload.</small>
                @error('filenames')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                @error('filenames.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
              </div>



           {{-- Voice Note Recording --}}
            <div class="mb-4">
    <label class="form-label">Voice Note (Optional)</label>
    <div class="border rounded p-3 bg-light">
        <div id="voiceNoteSection">

            <!-- Recording Controls -->
            <div class="d-flex flex-column flex-sm-row gap-2 mb-3" id="recordingControls">
                <button type="button" class="btn btn-outline-danger w-100 w-sm-auto" id="startRecording">
                    <i class="bx bx-microphone me-1"></i> Start Recording
                </button>
                <button type="button" class="btn btn-outline-secondary w-100 w-sm-auto" id="stopRecording" disabled>
                    <i class="bx bx-stop-circle me-1"></i> Stop
                </button>
                <button type="button" class="btn btn-outline-warning w-100 w-sm-auto" id="cancelRecording" disabled>
                    <i class="bx bx-x-circle me-1"></i> Cancel
                </button>
            </div>

            <!-- Recording Status -->
            <div class="mb-3 d-none" id="recordingStatus">
                <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-2">
                    <div class="d-flex align-items-center">
                        <div class="spinner-border spinner-border-sm text-danger me-2" role="status">
                            <span class="visually-hidden">Recording...</span>
                        </div>
                        <span class="text-danger fw-semibold" id="recordingTimer">00:00</span>
                    </div>
                    <span class="badge bg-danger">Recording...</span>
                </div>
                <small class="text-muted">Click "Stop" when finished</small>
            </div>

            <!-- Recording Preview -->
            <div class="d-none" id="recordingPreview">
                <audio controls class="w-100 mb-2" id="audioPreview"></audio>
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
                    <small class="text-muted" id="recordingDuration"></small>
                    <button type="button" class="btn btn-sm btn-outline-danger w-100 w-sm-auto" id="deleteRecording">
                        <i class="bx bx-trash me-1"></i> Delete
                    </button>
                </div>
            </div>

            <!-- Hidden Inputs -->
            <input type="file" name="voice_note" id="voiceNoteInput" class="d-none" accept="audio/*">
            <input type="hidden" name="recording_duration" id="recordingDurationInput">

        </div>
    </div>
</div>

              <div class="col-12 float-end ">
                @csrf
                <button type="submit" class="btn btn-primary" id="submitBtn">Send</button>
              </div>
          </form>
        </div>
      </div>

       @endif

       @include('support-tickets.sections.change-log',['supportTicket'=>$supportTicket])

    </div>

    <div class="col-md-4">

       @include('support-tickets.sections.action', ['supportTicket' => $supportTicket])

       @include('support-tickets.sections.ticket-information',['supportTicket'=>$supportTicket])

     <!-- Assigned Information --> 

     <div class="card mb-6">
        <div class="card-body">          


            <ul class="list-unstyled chat-contact-list py-2 mb-0" id="chat-list">
          <li class="chat-contact-list-item chat-contact-list-item-title mt-0">
            <h5 class="text-primary mb-3">Collaborators</h5>
          </li>

          @forelse ($activeAssignments as $stAssignment)
              <li class="chat-contact-list-item mb-4">
            <a class="d-flex align-items-center">
              <div class="flex-shrink-0 avatar">
               <div class="avatar avatar-md me-2">
              <span class="avatar-initial rounded-circle bg-label-danger">{{ $stAssignment->user->name[0] }}</span>
            </div>

              </div>
              <div class="chat-contact-info flex-grow-1 ms-4">
                <div class="d-flex justify-content-between align-items-center">
                  <h6 class="chat-contact-name text-truncate m-0 fw-normal">{{ $stAssignment->user->name }}</h6>
                  <small class="chat-contact-list-item-time">{{ $stAssignment->created_at->diffForHumans() }}</small>
                </div>
                <small class="chat-contact-status text-truncate">{{ $stAssignment->user->designation->designation_name ?? '--' }}</small>
              </div>
            </a>
          </li>
          @empty
           <li class="chat-contact-list-item chat-list-item-0">
            <h6 class="text-body-secondary mb-0">No Assignments Found</h6>
          </li>
          @endforelse
          
        </ul>

        @if($isAssignedTicket && !(in_array($supportTicket->status,['closed', 'completed'])))

        <form method="POST" action="{{route('support-ticket.assignticket', $supportTicket->id)}}" class="mb-4">

        <div>
        <label for="user_id" class="form-label">Assign another user</label>
        <select class="form-select mb-6" id="user_id" name="user_id" required>
          <option value="">Select</option>
          @foreach($assignAnotherUser as $user)
          <option value="{{ $user->id }}">{{ $user->name }}</option>
          @endforeach 
        </select> 
        </div>

        @method('PUT')

        @csrf

        <button type="submit" class="btn btn-primary d-grid w-100">
          <span class="d-flex align-items-center justify-content-center text-nowrap"><i
            class="icon-base bx bx-paper-plane icon-sm me-2"></i>Assign</span>
        </button>

      </form>

      

       @endif

           
          
        </div>
      </div>

    </div>
</div>

@endsection


@section('page-script')

<script>

document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.exit-ticket-from').forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            Swal.fire({
                title: 'Exit Ticket?',
                text: "Are you sure you want to exit this ticket? Please provide a reason:",
                input: 'textarea',
                inputPlaceholder: 'Type your exit reason here...',
                inputAttributes: {
                    'aria-label': 'Type your exit reason here'
                },
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Leave it!',
                cancelButtonText: 'Cancel',
                preConfirm: (value) => {
                    if (!value || value.trim() === '') {
                        Swal.showValidationMessage('Exit reason is required')
                    }
                    return value;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    let hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'exit_reason';
                    hiddenInput.value = result.value;
                    form.appendChild(hiddenInput);
                    form.submit();
                }
            });
        });
    });
// ===============================
// Voice Note Recording - All Browser Compatible
// ===============================

let mediaRecorder;
let audioChunks = [];
let recordingTimer;
let recordingStartTime;
let audioBlob;
let selectedMimeType = '';
let selectedExtension = 'webm';

// DOM Elements
const startBtn = document.getElementById('startRecording');
const stopBtn = document.getElementById('stopRecording');
const cancelBtn = document.getElementById('cancelRecording');
const deleteBtn = document.getElementById('deleteRecording');
const recordingStatus = document.getElementById('recordingStatus');
const recordingControls = document.getElementById('recordingControls');
const recordingPreview = document.getElementById('recordingPreview');
const audioPreview = document.getElementById('audioPreview');
const recordingTimerDisplay = document.getElementById('recordingTimer');
const recordingDurationDisplay = document.getElementById('recordingDuration');
const voiceNoteInput = document.getElementById('voiceNoteInput');
const recordingDurationInput = document.getElementById('recordingDurationInput');
const replyForm = document.getElementById('replyForm');

// ===============================
// Detect Best Supported Format
// ===============================
function detectSupportedMimeType() {
    if (MediaRecorder.isTypeSupported('audio/webm')) {
        selectedMimeType = 'audio/webm';
        selectedExtension = 'webm';
    } 
    else if (MediaRecorder.isTypeSupported('audio/mp4')) {
        selectedMimeType = 'audio/mp4';
        selectedExtension = 'm4a';
    } 
    else if (MediaRecorder.isTypeSupported('audio/mpeg')) {
        selectedMimeType = 'audio/mpeg';
        selectedExtension = 'mp3';
    } 
    else {
        selectedMimeType = '';
        selectedExtension = 'webm';
    }
}

// ===============================
// Start Recording
// ===============================
startBtn.addEventListener('click', async function () {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || typeof MediaRecorder === 'undefined') {
        alert('Voice recording is not supported in your browser.');
        return;
    }

    try {
        detectSupportedMimeType();

        const stream = await navigator.mediaDevices.getUserMedia({
            audio: {
                echoCancellation: true,
                noiseSuppression: true
            }
        });

        mediaRecorder = new MediaRecorder(
            stream,
            selectedMimeType ? { mimeType: selectedMimeType } : {}
        );

        audioChunks = [];

        mediaRecorder.ondataavailable = event => {
            if (event.data.size > 0) {
                audioChunks.push(event.data);
            }
        };

        mediaRecorder.onstop = () => {

            audioBlob = new Blob(audioChunks, {
                type: mediaRecorder.mimeType
            });

            const audioUrl = URL.createObjectURL(audioBlob);
            audioPreview.src = audioUrl;

            // Duration
            const duration = Math.round((Date.now() - recordingStartTime) / 1000);
            const minutes = Math.floor(duration / 60);
            const seconds = duration % 60;

            recordingDurationDisplay.textContent =
                `Duration: ${minutes}:${seconds.toString().padStart(2, '0')}`;
            recordingDurationInput.value = duration;

            // Create File
            const audioFile = new File(
                [audioBlob],
                `voice_note_${Date.now()}.${selectedExtension}`,
                {
                    type: mediaRecorder.mimeType,
                    lastModified: Date.now()
                }
            );

            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(audioFile);
            voiceNoteInput.files = dataTransfer.files;

            // UI
            recordingPreview.classList.remove('d-none');
            recordingStatus.classList.add('d-none');
            recordingControls.classList.add('d-none');

            // Stop mic
            stream.getTracks().forEach(track => track.stop());
            clearInterval(recordingTimer);
        };

        mediaRecorder.start();
        recordingStartTime = Date.now();

        // UI Update
        startBtn.disabled = true;
        stopBtn.disabled = false;
        cancelBtn.disabled = false;
        recordingStatus.classList.remove('d-none');

        recordingTimer = setInterval(updateRecordingTimer, 1000);

    } catch (err) {
        console.error('Microphone error:', err);
        alert('Please allow microphone access.');
    }
});

// ===============================
// Stop Recording
// ===============================
stopBtn.addEventListener('click', function () {
    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
        mediaRecorder.stop();
        stopBtn.disabled = true;
        cancelBtn.disabled = true;
    }
});

// ===============================
// Cancel Recording
// ===============================
cancelBtn.addEventListener('click', function () {
    if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.stop();
        audioChunks = [];
        resetRecordingUI();

        if (mediaRecorder.stream) {
            mediaRecorder.stream.getTracks().forEach(track => track.stop());
        }

        clearInterval(recordingTimer);
    }
});

// ===============================
// Delete Recording
// ===============================
deleteBtn.addEventListener('click', function () {
    audioChunks = [];
    audioBlob = null;
    audioPreview.src = '';
    voiceNoteInput.value = '';
    recordingDurationInput.value = '';

    resetRecordingUI();

    recordingControls.classList.remove('d-none');
    recordingPreview.classList.add('d-none');
});

// ===============================
// Timer
// ===============================
function updateRecordingTimer() {
    const elapsed = Math.floor((Date.now() - recordingStartTime) / 1000);
    const minutes = Math.floor(elapsed / 60);
    const seconds = elapsed % 60;

    recordingTimerDisplay.textContent =
        `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

    if (elapsed >= 300) {
        stopBtn.click();
    }
}

// ===============================
// Reset UI
// ===============================
function resetRecordingUI() {
    startBtn.disabled = false;
    stopBtn.disabled = true;
    cancelBtn.disabled = true;
    recordingStatus.classList.add('d-none');
    recordingTimerDisplay.textContent = '00:00';
}

// ===============================
// Form Validation
// ===============================
const replyFilenamesInput = document.getElementById('reply_filenames');

if (replyFilenamesInput) {
    replyFilenamesInput.addEventListener('change', function () {
        if (this.files.length > 2) {
            alert('You can upload a maximum of 2 files.');
            this.value = '';
            return;
        }

        const maxBytes = 20 * 1024 * 1024;
        for (const file of this.files) {
            if (file.size > maxBytes) {
                alert('Each file must be 20 MB or smaller.');
                this.value = '';
                return;
            }
        }
    });
}

if (replyForm) {
    replyForm.addEventListener('submit', function (e) {
        const textResponse = document.getElementById('ticket_response').value.trim();
        const hasVoiceNote = voiceNoteInput && voiceNoteInput.files.length > 0;
        const hasFiles = replyFilenamesInput && replyFilenamesInput.files.length > 0;

        if (!textResponse && !hasVoiceNote && !hasFiles) {
            e.preventDefault();
            alert('Please provide text, attach a file, or record a voice note.');
        }
    });
}

});





document.querySelectorAll('.close-ticket-form').forEach(form => {
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        Swal.fire({
            title: 'Close Ticket?',
            text: "Are you sure you want to mark this ticket as Close?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Close it'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});

document.querySelectorAll('.reject-ticket-form').forEach(form => {
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        Swal.fire({
            title: 'Reject Ticket?',
            text: "Are you sure you want to reject this ticket? Please provide a reason:",
            input: 'textarea',
            inputPlaceholder: 'Type your rejection reason here...',
            inputAttributes: {
                'aria-label': 'Type your rejection reason here'
            },
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Save & Reject',
            cancelButtonText: 'Cancel',
            preConfirm: (value) => {
                if (!value || value.trim() === '') {
                    Swal.showValidationMessage('Rejection reason is required')
                }
                return value;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                let hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'reject_reason';
                hiddenInput.value = result.value;
                form.appendChild(hiddenInput);
                form.submit();
            }
        });
    });
});

/*
document.querySelectorAll('.complete-ticket-form').forEach(form => {
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        Swal.fire({
            title: 'Complete Ticket?',
            text: "Are you sure you want to mark this ticket as Completed?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Complete it'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
}); */




document.querySelectorAll('.reopen-ticket-form').forEach(form => {
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        Swal.fire({
            title: 'Reopen Ticket?',
            text: "Are you sure you want to mark this ticket as Reopen?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Reopen it'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});




</script>

@endsection