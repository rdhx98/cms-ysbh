<?php

use Livewire\Component;

new class extends Component {
  //
};
?>

<x-slot:tequila>
  Wakumba
</x-slot:tequila>
<x-slot:title>
  {{
    __(
      "ui.header.dashboard",
    )
  }}
</x-slot:title>
<div
  class="mt-4 flex h-full max-h-screen min-h-0 w-full min-w-0 flex-col items-center justify-start gap-6 overflow-x-hidden overflow-y-auto"
>
  <div class="flex w-full max-w-7xl flex-col justify-between gap-4">
    <div class="rounded-xl bg-white p-4">
      <livewire:content.site-stats lazy />
    </div>
    <div class="rounded-xl bg-white p-4">
      <livewire:content.attention-list />
    </div>
  </div>
</div>
