@php
    $attachmentFilenames = $stCommand->attachment_filenames;
    $attachmentUrls = $stCommand->attachment_urls;
    $imageExtensions = ['png', 'jpe', 'jpeg', 'jpg', 'gif', 'bmp', 'ico', 'tif', 'svg', 'svgz', 'webp'];
    $images = [];
    $files = [];

    foreach ($attachmentFilenames as $index => $filename) {
        $fileUrl = $attachmentUrls[$index] ?? '';
        $fileExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $item = ['name' => $filename, 'url' => $fileUrl];

        if (in_array($fileExt, $imageExtensions, true)) {
            $images[] = $item;
        } else {
            $files[] = $item;
        }
    }
@endphp

@if($images !== [])
    <div class="ticket-post__images">
        @foreach($images as $image)
            <a href="{{ $image['url'] }}" data-lightbox="reply-gallery-{{ $stCommand->id }}" data-title="{{ $image['name'] }}">
                <img src="{{ $image['url'] }}" alt="{{ $image['name'] }}">
            </a>
        @endforeach
    </div>
@endif

@if($files !== [])
    <div class="ticket-post__files">
        @foreach($files as $file)
            <a class="ticket-file" href="{{ $file['url'] }}" download="{{ $file['name'] }}">
                <i class="bx bx-file"></i>
                <span>{{ $file['name'] }}</span>
            </a>
        @endforeach
    </div>
@endif
