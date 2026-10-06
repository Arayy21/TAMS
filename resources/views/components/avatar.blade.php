@props(['user', 'size' => 40])

<span class="avatar-frame"
      style="width:{{ $size }}px; height:{{ $size }}px; font-size:{{ round($size * 0.38) }}px; background:{{ $user->avatar_color }}"
      aria-hidden="true">{{ $user->initials }}</span>