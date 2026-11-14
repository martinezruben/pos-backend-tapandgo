@props(['cancel', 'submit' => 'Guardar'])
<div class="mt-6 flex flex-wrap items-center gap-3 border-t border-slate-200 pt-5">
    <button type="submit" class="snow-btn-primary">{{ $submit }}</button>
    <a href="{{ $cancel }}" class="snow-btn-secondary">Cancelar</a>
</div>
