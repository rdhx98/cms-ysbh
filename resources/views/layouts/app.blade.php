<!DOCTYPE html>
<html lang="id">
<head>
  @include ("partials.head")
</head>
<body
  class="bg-misty overflow-x-hidden font-sans antialiased"
  x-init="
    console.log('✅ Alpine.js Berhasil Dimuat dan Aktif dari app layout!')
  "
>
  <div
    class="flex h-dvh w-full flex-col md:flex-row"
    x-data="{
      isExpanded: Alpine.$persist(true),
      userMenuExpand: false,
      isAuditOpen: false,
    }"
  >
    <x-layouts::app.sidebar />
    <main
      class="flex min-w-0 flex-1 flex-col space-y-2 p-[0.5rem_0.5rem_0.5rem_0.5rem] md:p-[0.5rem_0.5rem_0.5rem_0rem]"
    >
      <x-layouts::app.header :header="$header ?? ''" :title="$title ?? ''" />
      {{ $slot }}
    </main>
  </div>

  {{-- @livewireScripts --}}
  <x-layouts::app.floating-notifications mobileTop="top-16" />
  <x-editor.icon-sprite />

  @unless (request()->routeIs("files.index") || request()->is("*files*"))
    <livewire:file-manager :forceModal="true" />
  @endunless
</body>
</html>
