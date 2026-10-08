{{-- resources/views/layouts/app.blade.php (atau layout admin yang dipakai builder) --}}
<head>
  {{-- $title berasal dari <x-slot:title> di view komponen (atau #[Title]). Tanpa slot: hanya nama aplikasi. --}}
  <title>{{ isset($title) && filled($title) ? $title . ' — ' . config('app.name') : config('app.name') }}</title>
  ...
</head>
