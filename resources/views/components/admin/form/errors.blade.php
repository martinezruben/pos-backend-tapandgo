@if($errors->any())
    <div class="mb-5 rounded-xl border border-red-500/40 bg-red-50 px-4 py-3 text-sm text-red-700">
        <p class="font-medium">Revisa estos puntos:</p>
        <ul class="mt-1 list-disc pl-5">
            @foreach($errors->all() as $msg)
                <li>{{ $msg }}</li>
            @endforeach
        </ul>
    </div>
@endif
