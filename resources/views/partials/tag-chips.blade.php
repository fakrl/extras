@foreach ($tagGroups as $grup => $tags)
    <div class="tag-grup">{{ $grup }}</div>
    <div class="tag-chips">
        @foreach ($tags as $tag)
            <label class="tag-chip">
                <input type="checkbox" class="sr-only" name="{{ $name }}" value="{{ $tag->id }}" @checked(in_array($tag->id, $selected ?? []))>
                #{{ $tag->nama }}
            </label>
        @endforeach
    </div>
@endforeach
