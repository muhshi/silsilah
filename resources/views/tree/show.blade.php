<x-layouts::app :title="'Silsilah Keluarga'" class="!p-0">
    <script>
        // Safety net: if opened on a small mobile screen (<768px) without explicit ?view=, redirect to Fokus
        if (window.innerWidth < 768 && !new URLSearchParams(window.location.search).has('view')) {
            window.location.replace('{{ route('tree.vertical', $tree) }}');
        }
    </script>
    <livewire:tree-view :id="$id" />
</x-layouts::app>
